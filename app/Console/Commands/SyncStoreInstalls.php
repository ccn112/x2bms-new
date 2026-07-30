<?php

namespace App\Console\Commands;

use App\Services\Analytics\StoreReports\StoreInstallSyncer;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * Đồng bộ số lượt cài từ Google Play + App Store.
 *
 * Chưa cấu hình credential thì lệnh **vẫn chạy xong bình thường** và in
 * `not_configured` — không fail, để cron không kêu mỗi ngày trong lúc chờ chủ dự
 * án cấp key (chốt 30/07: cấp key sau).
 */
class SyncStoreInstalls extends Command
{
    protected $signature = 'x2:sync-store-installs
                            {--until= : Ngày cuối cần quét (YYYY-MM-DD), mặc định hôm nay}';

    protected $description = 'Lấy số lượt cài app từ Google Play (CSV trên GCS) và App Store Connect';

    public function handle(StoreInstallSyncer $syncer): int
    {
        $until = $this->option('until') ? Carbon::parse((string) $this->option('until')) : null;

        $result = $syncer->sync($until);

        foreach ($result as $source => $r) {
            $line = sprintf('%-12s %-15s %d dòng', $source, $r['status'], $r['rows']);
            match ($r['status']) {
                'ok' => $this->info($line),
                'not_configured' => $this->warn($line.' — '.($r['message'] ?? '')),
                default => $this->error($line.' — '.($r['message'] ?? '')),
            };
        }

        // Lỗi thật (đã cấu hình mà tải/bóc thất bại) mới trả mã lỗi cho cron.
        $hasError = collect($result)->contains(fn ($r) => $r['status'] === 'error');

        return $hasError ? self::FAILURE : self::SUCCESS;
    }
}
