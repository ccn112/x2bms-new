<?php

declare(strict_types=1);

namespace App\Support\Backup;

use App\Support\Storage\TenantStorage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use ZipArchive;

/**
 * Rehydrate (khôi phục) 1 tenant từ bundle backup (.zip do TenantBackupService tạo):
 * nạp lại DB (NDJSON, giữ nguyên id) + đẩy file về vùng lưu trữ tenant.
 *
 * An toàn: bọc transaction; tắt FK check khi xoá+chèn (MySQL/MariaDB); xoá dòng
 * tenant hiện có (reverse order) rồi chèn lại từ bundle (forward order = parent→child).
 * Idempotent theo bundle.
 */
class TenantRestoreService
{
    public function __construct(private readonly TenantStorage $storage) {}

    /** Bundle mới nhất của tenant — ưu tiên sổ đăng ký, fallback quét storage. */
    public function latestBundle(int $tenantId): ?string
    {
        $fromRegistry = \App\Models\TenantBackup::query()
            ->where('tenant_id', $tenantId)->latest()->value('path');
        if ($fromRegistry) {
            return $fromRegistry;
        }

        $prefix = $this->storage->key('_backups', $tenantId);
        $zips = array_values(array_filter(
            $this->storage->disk()->allFiles($prefix),
            fn (string $k): bool => str_ends_with($k, 'backup.zip')
        ));
        sort($zips);

        return $zips === [] ? null : end($zips);
    }

    /**
     * @return array{tables: array<string,int>, files: int}
     */
    public function restore(int $tenantId, string $bundleKey): array
    {
        if (! $this->storage->exists($bundleKey)) {
            throw new RuntimeException("Không thấy bundle: {$bundleKey}");
        }

        $local = $this->storage->localReadablePath($bundleKey);
        $tmp = storage_path('app/tmp/restore_'.$tenantId.'_'.substr(md5($bundleKey), 0, 8));
        $this->cleanupDir($tmp);
        @mkdir($tmp, 0775, true);

        $zip = new ZipArchive;
        if ($zip->open($local) !== true) {
            throw new RuntimeException('Không mở được bundle zip.');
        }
        $zip->extractTo($tmp);
        $zip->close();

        $manifest = json_decode((string) file_get_contents($tmp.'/manifest.json'), true);
        if (! is_array($manifest) || (int) ($manifest['tenant_id'] ?? 0) !== $tenantId) {
            $this->cleanupDir($tmp);
            throw new RuntimeException('Manifest không khớp tenant.');
        }
        $tables = array_keys($manifest['tables'] ?? []);

        // 1) DB: xoá dòng tenant hiện có (reverse) → chèn lại (forward).
        $counts = [];
        DB::transaction(function () use ($tables, $tenantId, $tmp, &$counts): void {
            DB::statement('SET FOREIGN_KEY_CHECKS=0');

            foreach (array_reverse($tables) as $table) {
                if (Schema::hasColumn($table, 'tenant_id')) {
                    DB::table($table)->where('tenant_id', $tenantId)->delete();
                }
            }

            foreach ($tables as $table) {
                $file = $tmp.'/db/'.$table.'.ndjson';
                if (! is_file($file)) {
                    continue;
                }
                $counts[$table] = $this->insertNdjson($table, $file);
            }

            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        });

        // Kích hoạt lại tenant (rehydrate xong).
        DB::table('tenants')->where('id', $tenantId)->update([
            'lifecycle_status' => 'active',
            'dormant_at' => null,
            'retention_until' => null,
            'updated_at' => now(),
        ]);

        // 2) Files: đẩy lại về đúng key trên tenant disk.
        $fileCount = $this->restoreFiles($tmp.'/files');

        $this->cleanupDir($tmp);

        return ['tables' => $counts, 'files' => $fileCount];
    }

    private function insertNdjson(string $table, string $file): int
    {
        // Schema-drift safe: chỉ chèn cột CÒN tồn tại ở schema hiện tại (bundle cũ có thể
        // thừa cột đã xoá/đổi tên; cột mới sẽ nhận default). Xem manifest.app_version.
        $current = array_flip(Schema::getColumnListing($table));

        $fh = fopen($file, 'r');
        $buffer = [];
        $n = 0;

        while (($line = fgets($fh)) !== false) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            $row = json_decode($line, true);
            $buffer[] = array_intersect_key($row, $current);
            $n++;

            if (count($buffer) >= 500) {
                DB::table($table)->insert($buffer);
                $buffer = [];
            }
        }
        fclose($fh);

        if ($buffer !== []) {
            DB::table($table)->insert($buffer);
        }

        return $n;
    }

    private function restoreFiles(string $filesRoot): int
    {
        if (! is_dir($filesRoot)) {
            return 0;
        }

        $disk = $this->storage->disk();
        $count = 0;
        $it = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($filesRoot, \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($it as $item) {
            if ($item->isFile()) {
                $key = ltrim(str_replace('\\', '/', substr($item->getPathname(), strlen($filesRoot) + 1)), '/');
                $disk->put($key, file_get_contents($item->getPathname()));
                $count++;
            }
        }

        return $count;
    }

    private function cleanupDir(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }
        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($items as $item) {
            $item->isDir() ? @rmdir($item->getPathname()) : @unlink($item->getPathname());
        }
        @rmdir($dir);
    }
}
