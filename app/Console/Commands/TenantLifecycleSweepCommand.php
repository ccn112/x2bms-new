<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Models\TenantBackup;
use App\Support\Backup\TenantOffboardService;
use App\Support\Storage\TenantStorage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Sweep vòng đời tenant (state machine tự động — chạy theo lịch):
 *   - active + thuê bao hết hạn quá grace  → OFF (dormant_archived, giữ bundle)
 *   - dormant + quá retention_until        → PURGE (xóa bundle + đánh dấu purged)
 *
 * Mặc định DRY-RUN (chỉ báo cáo). Thêm --commit để thực thi.
 *
 * Tiering nóng→lạnh: khi dùng object storage (S3), đặt LIFECYCLE POLICY của bucket để
 * tự chuyển _backups/ sang lớp Archive/Glacier — cấu hình phía hạ tầng, không ở code.
 */
class TenantLifecycleSweepCommand extends Command
{
    protected $signature = 'tenant:lifecycle-sweep {--commit : Thực thi thật (mặc định chỉ dry-run)}';

    protected $description = 'Tự động off tenant hết hạn (quá grace) và purge tenant dormant quá retention';

    public function handle(TenantOffboardService $offboard, TenantStorage $storage): int
    {
        $commit = (bool) $this->option('commit');
        $graceDays = (int) config('tenant-backup.grace_days', 60);
        $hasSubs = Schema::hasTable('subscriptions');

        $this->info(($commit ? '[COMMIT]' : '[DRY-RUN]')." grace={$graceDays} ngày");

        // 1) OFF: active + hết hạn quá grace.
        $offCount = 0;
        foreach (Tenant::where('lifecycle_status', 'active')->get() as $tenant) {
            if (! $hasSubs) {
                break;
            }
            $hasActive = DB::table('subscriptions')->where('tenant_id', $tenant->id)
                ->where('status', 'active')->where('current_period_end', '>=', now())->exists();
            if ($hasActive) {
                continue;
            }
            $latestEnd = DB::table('subscriptions')->where('tenant_id', $tenant->id)->max('current_period_end');
            if ($latestEnd === null || now()->diffInDays($latestEnd, false) > -$graceDays) {
                continue; // chưa có thuê bao / chưa quá grace
            }

            $this->line("  OFF  #{$tenant->id} {$tenant->name} (hết hạn {$latestEnd})");
            if ($commit) {
                $offboard->offboard($tenant->id, now()->format('Ymd_His'));
            }
            $offCount++;
        }

        // 2) PURGE: dormant + quá retention.
        $purgeCount = 0;
        foreach (Tenant::where('lifecycle_status', 'dormant_archived')
            ->whereNotNull('retention_until')->whereDate('retention_until', '<', now())->get() as $tenant) {
            $this->line("  PURGE #{$tenant->id} {$tenant->name} (giữ đến {$tenant->retention_until})");
            if ($commit) {
                $storage->disk()->deleteDirectory($storage->prefix($tenant->id));
                TenantBackup::where('tenant_id', $tenant->id)->delete();
                $tenant->update(['lifecycle_status' => 'purged']);
            }
            $purgeCount++;
        }

        $this->info("Kết quả: off={$offCount}, purge={$purgeCount}".($commit ? '' : ' (dry-run, chưa thực thi)'));

        return self::SUCCESS;
    }
}
