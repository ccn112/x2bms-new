<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Support\Backup\TenantBackupService;
use Illuminate\Console\Command;

/**
 * Tạo bundle backup cho 1 tenant. Dùng thủ công hoặc lên lịch (scheduler):
 *   php artisan tenant:backup 1
 */
class TenantBackupCommand extends Command
{
    protected $signature = 'tenant:backup {tenant : ID tenant}';

    protected $description = 'Tạo bundle backup (DB NDJSON + files) cho 1 tenant, lưu trong vùng lưu trữ tenant';

    public function handle(TenantBackupService $service): int
    {
        $tenantId = (int) $this->argument('tenant');
        $this->info("Đang backup tenant #{$tenantId}...");

        $key = $service->create($tenantId, now()->format('Ymd_His'));

        $this->info('Xong. Bundle: '.$key);

        return self::SUCCESS;
    }
}
