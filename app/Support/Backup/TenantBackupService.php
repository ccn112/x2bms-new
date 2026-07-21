<?php

declare(strict_types=1);

namespace App\Support\Backup;

use App\Support\Storage\TenantStorage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use ZipArchive;

/**
 * Tạo BUNDLE BACKUP theo từng tenant: dump DB (các bảng có tenant_id → NDJSON) +
 * toàn bộ file trong vùng lưu trữ của tenant → 1 file .zip, lưu chính trong vùng
 * tenant (`{prefix}/_backups/{ts}/backup.zip`).
 *
 * Mục đích kép: (1) cho tenant tự backup/mang dữ liệu đi; (2) nền cho vòng đời churn
 * (giữ bundle ở cold tier khi off, rehydrate khi resume). Backup LOGIC (độc lập DB
 * engine) + manifest có `app_version` để khôi phục bản cũ sau khi nâng version.
 */
class TenantBackupService
{
    public function __construct(private readonly TenantStorage $storage) {}

    /**
     * @param  string  $timestamp  Dấu thời gian (yyyymmdd_His) do caller truyền (an toàn khi chạy nền).
     * @return string  Key của file .zip trên tenant disk.
     */
    public function create(int $tenantId, string $timestamp): string
    {
        $tables = $this->exportableTables();

        // 1) Ghi NDJSON + manifest ra thư mục tạm cục bộ.
        $work = storage_path('app/tmp/backup_'.$tenantId.'_'.$timestamp);
        $dbDir = $work.'/db';
        if (! is_dir($dbDir)) {
            @mkdir($dbDir, 0775, true);
        }

        $counts = [];
        foreach ($tables as $table) {
            $counts[$table] = $this->dumpTable($table, $tenantId, $dbDir.'/'.$table.'.ndjson');
        }

        // 2) Liệt kê file của tenant (để đưa vào zip).
        $prefix = $this->storage->prefix($tenantId);
        $disk = $this->storage->disk();
        $files = array_values(array_filter(
            $disk->allFiles($prefix),
            fn (string $k): bool => ! str_contains($k, '/_backups/') // không tự lồng backup cũ
        ));

        $manifest = [
            'tenant_id' => $tenantId,
            'generated_at' => $timestamp,
            'app_version' => (string) config('app.version', '1.0'),
            'format' => 'ndjson',
            'tables' => $counts,
            'file_count' => count($files),
        ];
        file_put_contents($work.'/manifest.json', json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        // 3) Đóng gói .zip: manifest + db/*.ndjson + files/<key>.
        $zipPath = $work.'/backup.zip';
        $zip = new ZipArchive;
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Không tạo được file zip backup.');
        }
        $zip->addFile($work.'/manifest.json', 'manifest.json');
        foreach ($tables as $table) {
            $f = $dbDir.'/'.$table.'.ndjson';
            if (is_file($f)) {
                $zip->addFile($f, 'db/'.$table.'.ndjson');
            }
        }
        foreach ($files as $key) {
            $zip->addFromString('files/'.$key, (string) $disk->get($key));
        }
        $zip->close();

        // 4) Đưa .zip vào vùng lưu trữ tenant + dọn tạm.
        $bundleKey = $this->storage->key('_backups/'.$timestamp.'/backup.zip', $tenantId);
        $disk->put($bundleKey, file_get_contents($zipPath));

        $this->cleanupDir($work);

        return $bundleKey;
    }

    /** @return list<string> Các bảng cấu hình MÀ có cột tenant_id thật. */
    private function exportableTables(): array
    {
        return array_values(array_filter(
            (array) config('tenant-backup.tables', []),
            fn (string $t): bool => Schema::hasTable($t) && Schema::hasColumn($t, 'tenant_id')
        ));
    }

    /** Ghi 1 bảng ra NDJSON (lọc tenant_id), trả số dòng. */
    private function dumpTable(string $table, int $tenantId, string $outPath): int
    {
        $handle = fopen($outPath, 'w');
        $n = 0;

        DB::table($table)->where('tenant_id', $tenantId)->orderBy('id')
            ->chunk((int) config('tenant-backup.chunk', 1000), function ($rows) use ($handle, &$n): void {
                foreach ($rows as $row) {
                    fwrite($handle, json_encode((array) $row, JSON_UNESCAPED_UNICODE).PHP_EOL);
                    $n++;
                }
            });

        fclose($handle);

        return $n;
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
