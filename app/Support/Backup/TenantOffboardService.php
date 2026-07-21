<?php

declare(strict_types=1);

namespace App\Support\Backup;

use App\Support\Storage\TenantStorage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * "Off" 1 tenant (churn → dormant_archived): tạo bundle backup rồi PURGE dữ liệu
 * sống (DB rows + files) NHƯNG **giữ lại `_backups/`** — đúng mô hình "gói storage":
 * tenant off vẫn được giữ hộ bundle ở vùng lưu trữ, resume thì rehydrate.
 *
 * Đối xứng với TenantRestoreService (rehydrate).
 */
class TenantOffboardService
{
    public function __construct(
        private readonly TenantBackupService $backup,
        private readonly TenantStorage $storage,
    ) {}

    /**
     * @return array{bundle: string, purged_tables: array<string,int>, purged_files: int}
     */
    public function offboard(int $tenantId, string $timestamp): array
    {
        // 1) Backup trước (bundle nằm trong _backups của tenant).
        $bundle = $this->backup->create($tenantId, $timestamp);

        // 2) Purge DB rows (reverse order, FK off).
        $tables = array_values(array_filter(
            (array) config('tenant-backup.tables', []),
            fn (string $t): bool => Schema::hasTable($t) && Schema::hasColumn($t, 'tenant_id')
        ));

        $purged = [];
        DB::transaction(function () use ($tables, $tenantId, &$purged): void {
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
            foreach (array_reverse($tables) as $table) {
                $purged[$table] = DB::table($table)->where('tenant_id', $tenantId)->delete();
            }
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        });

        // 3) Purge files NHƯNG giữ _backups (bundle) lại.
        $prefix = $this->storage->prefix($tenantId);
        $disk = $this->storage->disk();
        $purgedFiles = 0;
        foreach ($disk->allFiles($prefix) as $key) {
            if (str_contains($key, '/_backups/')) {
                continue;
            }
            $disk->delete($key);
            $purgedFiles++;
        }

        return ['bundle' => $bundle, 'purged_tables' => $purged, 'purged_files' => $purgedFiles];
    }
}
