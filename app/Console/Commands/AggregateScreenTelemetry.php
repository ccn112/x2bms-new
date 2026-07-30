<?php

namespace App\Console\Commands;

use App\Models\AppScreenDailyStat;
use App\Models\AppScreenEvent;
use App\Models\AppScreenReport;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Tổng hợp nhật ký màn theo ngày, rồi dọn dữ liệu thô quá hạn lưu.
 *
 * **Thứ tự trong lệnh này là bắt buộc: TỔNG HỢP TRƯỚC, DỌN SAU.** Dọn trước là mất
 * số vĩnh viễn — bảng thô không khôi phục được.
 *
 * Tổng hợp lại cả một khoảng ngày (không chỉ hôm qua) vì app **gom lô gửi định kỳ**:
 * sự kiện của hôm qua có thể tới server hôm nay, khi máy cư dân mới có mạng lại.
 * `updateOrCreate` theo (ngày, màn, tenant, project) nên chạy lại bao nhiêu lần cũng
 * ra một kết quả.
 */
class AggregateScreenTelemetry extends Command
{
    protected $signature = 'x2:aggregate-telemetry
                            {--days=3 : Số ngày gần nhất cần tổng hợp lại}
                            {--no-prune : Chỉ tổng hợp, không dọn dữ liệu quá hạn}';

    protected $description = 'Tổng hợp nhật ký màn của app theo ngày và dọn dữ liệu thô quá hạn lưu';

    public function handle(): int
    {
        $days = max(1, (int) $this->option('days'));
        $until = today();
        $from = $until->copy()->subDays($days - 1);

        $written = 0;
        for ($d = $from->copy(); $d->lte($until); $d->addDay()) {
            $written += $this->aggregateDay($d);
        }
        $this->info("Tong hop: {$written} dong (tu {$from->toDateString()} den {$until->toDateString()})");

        if (! $this->option('no-prune')) {
            $this->prune();
        }

        return self::SUCCESS;
    }

    private function aggregateDay(Carbon $day): int
    {
        // Gộp ngay trong SQL: kéo hàng triệu dòng về PHP để đếm là không khả thi.
        // COALESCE(...,0) để khớp mặc định 0 của bảng tổng hợp (xem docblock model).
        $rows = AppScreenEvent::query()
            ->selectRaw('screen_key')
            ->selectRaw('COALESCE(tenant_id, 0) AS t_id')
            ->selectRaw('COALESCE(project_id, 0) AS p_id')
            ->selectRaw("SUM(CASE WHEN kind = 'view' THEN 1 ELSE 0 END) AS views")
            ->selectRaw("SUM(CASE WHEN kind = 'action' THEN 1 ELSE 0 END) AS actions")
            ->selectRaw('COUNT(DISTINCT device_id) AS unique_devices')
            ->selectRaw('COUNT(DISTINCT user_id) AS unique_users')
            ->selectRaw('AVG(duration_ms) AS avg_duration')
            ->whereDate('occurred_at', $day->toDateString())
            ->groupBy('screen_key', 't_id', 'p_id')
            ->get();

        foreach ($rows as $r) {
            AppScreenDailyStat::updateOrCreate(
                [
                    'stat_date' => $day->toDateString(),
                    'screen_key' => $r->screen_key,
                    'tenant_id' => (int) $r->t_id,
                    'project_id' => (int) $r->p_id,
                ],
                [
                    'views' => (int) $r->views,
                    'actions' => (int) $r->actions,
                    'unique_devices' => (int) $r->unique_devices,
                    'unique_users' => (int) $r->unique_users,
                    'avg_duration_ms' => $r->avg_duration === null ? null : (int) round((float) $r->avg_duration),
                ]
            );
        }

        return $rows->count();
    }

    /**
     * Dọn theo hạn lưu đã công bố trong Điều khoản sử dụng
     * (`config/telemetry.php` — đổi ở đây thì phải sửa cả điều khoản).
     */
    private function prune(): void
    {
        $rawDays = (int) config('telemetry.raw_retention_days', 90);
        $cutoff = now()->subDays($rawDays);

        // Xoá theo lô: một DELETE trên vài chục triệu dòng sẽ khoá bảng rất lâu.
        $deleted = 0;
        do {
            $n = DB::table('app_screen_events')->where('occurred_at', '<', $cutoff)->limit(5000)->delete();
            $deleted += $n;
        } while ($n > 0);

        $this->info("Don nhat ky tho: {$deleted} dong cu hon {$rawDays} ngay");

        $reportDays = (int) config('telemetry.report_retention_days', 730);
        $n = AppScreenReport::where('created_at', '<', now()->subDays($reportDays))->forceDelete();
        $this->info("Don bao loi: {$n} dong cu hon {$reportDays} ngay");
    }
}
