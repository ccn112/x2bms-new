<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Support\Backup\TenantOffboardService;
use Illuminate\Console\Command;

/**
 * Off 1 tenant (churn): backup + purge dữ liệu sống, giữ bundle ở _backups.
 *   php artisan tenant:offboard 5
 */
class TenantOffboardCommand extends Command
{
    protected $signature = 'tenant:offboard {tenant : ID tenant} {--force : Bỏ qua xác nhận}';

    protected $description = 'Backup rồi purge dữ liệu sống của tenant (giữ bundle backup để rehydrate sau)';

    public function handle(TenantOffboardService $service): int
    {
        $tenantId = (int) $this->argument('tenant');

        if (! $this->option('force') && ! $this->confirm("XÓA dữ liệu sống của tenant #{$tenantId} (đã backup, có thể rehydrate)?")) {
            $this->warn('Đã hủy.');

            return self::SUCCESS;
        }

        $r = $service->offboard($tenantId, now()->format('Ymd_His'));

        $this->info('Bundle: '.$r['bundle']);
        $this->info('Đã purge files: '.$r['purged_files'].' · rows: '.array_sum($r['purged_tables']));

        return self::SUCCESS;
    }
}
