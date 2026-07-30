<?php

namespace App\Services\Analytics\StoreReports;

use App\Models\StoreInstallStat;
use Illuminate\Support\Carbon;
use Throwable;

/**
 * Đồng bộ số lượt cài app từ hai store vào `store_install_stats`.
 *
 * ## Con số này KHÁC "số thiết bị đã đăng ký" — đừng trộn
 *
 * `store_install_stats` = số lượt cài **do store báo**. Nó tính cả người tải rồi
 * không đăng nhập, và không chia được theo tenant/dự án (một app dùng chung cho
 * mọi chung cư).
 *
 * `mobile_devices` = thiết bị đã gọi API và đăng ký (`installation_id`, `user_id`
 * nullable). Chia được theo tenant/dự án, nhưng KHÔNG phải số lượt cài: người tải
 * app rồi chưa mở lần nào sẽ không có ở đây.
 *
 * Báo cáo phải gọi đúng tên hai con số. Nói "số lượt cài" trong khi đang đếm thiết
 * bị đăng nhập là báo cáo sai cho chủ dự án.
 *
 * ## Vì sao quét lùi nhiều ngày
 * Cả hai store chốt số chậm và **sửa lại số của những ngày trước**. Chỉ lấy ngày
 * hôm qua thì số sẽ đứng im ở giá trị sai. Nên `updateOrCreate` theo
 * (source, stat_date) và quét lùi `store_reports.backfill_days`.
 */
class StoreInstallSyncer
{
    public function __construct(
        private readonly GooglePlayReportClient $play,
        private readonly AppStoreReportClient $appStore,
    ) {}

    /**
     * @return array{google_play: array<string,mixed>, app_store: array<string,mixed>}
     */
    public function sync(?Carbon $until = null): array
    {
        $until = ($until ?? now())->copy()->startOfDay();
        $days = max(1, (int) config('store_reports.backfill_days', 7));
        $from = $until->copy()->subDays($days - 1);

        return [
            'google_play' => $this->syncGoogle($from, $until),
            'app_store' => $this->syncApple($from, $until),
        ];
    }

    /** @return array<string, mixed> */
    private function syncGoogle(Carbon $from, Carbon $until): array
    {
        if (! $this->play->isConfigured()) {
            return ['status' => 'not_configured', 'rows' => 0,
                'message' => 'Thiếu PLAY_REPORTS_BUCKET / PLAY_SERVICE_ACCOUNT_JSON.'];
        }

        // CSV của Play theo THÁNG nên phải tải từng tháng mà khoảng ngày chạm tới.
        $months = [];
        $cursor = $from->copy()->startOfMonth();
        while ($cursor->lte($until)) {
            $months[] = $cursor->copy();
            $cursor->addMonth();
        }

        $written = 0;
        try {
            foreach ($months as $m) {
                foreach ($this->play->fetchMonth($m) as $row) {
                    $date = Carbon::parse($row['stat_date']);
                    if ($date->lt($from) || $date->gt($until)) {
                        continue;
                    }
                    $this->upsert('google_play', $row);
                    $written++;
                }
            }
        } catch (Throwable $e) {
            report($e);

            return ['status' => 'error', 'rows' => $written, 'message' => $e->getMessage()];
        }

        return ['status' => 'ok', 'rows' => $written];
    }

    /** @return array<string, mixed> */
    private function syncApple(Carbon $from, Carbon $until): array
    {
        if (! $this->appStore->isConfigured()) {
            return ['status' => 'not_configured', 'rows' => 0,
                'message' => 'Thiếu ASC_ISSUER_ID / ASC_KEY_ID / ASC_PRIVATE_KEY_PATH / ASC_VENDOR_NUMBER.'];
        }

        $written = 0;
        try {
            for ($d = $from->copy(); $d->lte($until); $d->addDay()) {
                foreach ($this->appStore->fetchDay($d) as $row) {
                    $this->upsert('app_store', $row);
                    $written++;
                }
            }
        } catch (Throwable $e) {
            report($e);

            return ['status' => 'error', 'rows' => $written, 'message' => $e->getMessage()];
        }

        return ['status' => 'ok', 'rows' => $written];
    }

    /** @param array<string, mixed> $row */
    private function upsert(string $source, array $row): void
    {
        StoreInstallStat::updateOrCreate(
            ['source' => $source, 'stat_date' => $row['stat_date']],
            [
                'installs' => $row['installs'] ?? null,
                'uninstalls' => $row['uninstalls'] ?? null,
                'updates' => $row['updates'] ?? null,
                'active_devices' => $row['active_devices'] ?? null,
                'raw' => $row['raw'] ?? null,
                'synced_at' => now(),
            ]
        );
    }
}
