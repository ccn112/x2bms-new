<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Support\Backup\TenantRestoreService;
use Illuminate\Console\Command;

/**
 * Rehydrate 1 tenant từ bundle backup (mặc định bundle mới nhất):
 *   php artisan tenant:restore 5
 *   php artisan tenant:restore 5 --bundle=tenants/5/_backups/20260721_020910/backup.zip
 */
class TenantRestoreCommand extends Command
{
    protected $signature = 'tenant:restore {tenant : ID tenant} {--bundle= : Key bundle cụ thể (mặc định mới nhất)}';

    protected $description = 'Khôi phục (rehydrate) DB + files của tenant từ bundle backup';

    public function handle(TenantRestoreService $service): int
    {
        $tenantId = (int) $this->argument('tenant');
        $bundle = $this->option('bundle') ?: $service->latestBundle($tenantId);

        if (! $bundle) {
            $this->error("Không tìm thấy bundle backup cho tenant #{$tenantId}.");

            return self::FAILURE;
        }

        $this->info("Rehydrate tenant #{$tenantId} từ: {$bundle}");
        $r = $service->restore($tenantId, $bundle);

        $this->info('Khôi phục rows: '.array_sum($r['tables']).' · files: '.$r['files']);

        return self::SUCCESS;
    }
}
