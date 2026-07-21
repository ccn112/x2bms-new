<?php

declare(strict_types=1);

namespace App\Support\Storage;

use App\Support\Context\CurrentContext;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Cổng truy cập DUY NHẤT tới dữ liệu file của tenant — mọi upload đi qua đây để
 * tự phân vùng theo **folder riêng từng tenant/dự án**:
 *
 *   {root_prefix}/{tenant_id}/projects/{building_id}/<relative>
 *
 * Driver-agnostic: `local` bây giờ, đổi sang `s3`/MinIO chỉ bằng ENV
 * (config/tenant-storage.php) — KHÔNG sửa code. Đây cũng là điểm cô lập chống rò
 * chéo tenant (prefix lấy từ CurrentContext, không tin input) và là nền để sau này
 * backup/export theo từng tenant (nén nguyên prefix).
 */
class TenantStorage
{
    public function __construct(private readonly CurrentContext $context) {}

    public function diskName(): string
    {
        return (string) config('tenant-storage.disk', 'local');
    }

    public function disk(): Filesystem
    {
        return Storage::disk($this->diskName());
    }

    private function isLocal(): bool
    {
        return config("filesystems.disks.{$this->diskName()}.driver") === 'local';
    }

    /** Tiền tố thư mục của tenant (+dự án nếu có). Thiếu tenant → chặn (tránh rò chéo). */
    public function prefix(?int $tenantId = null, ?int $buildingId = null): string
    {
        $tenantId ??= $this->context->tenantId();
        if (! $tenantId) {
            throw new RuntimeException('TenantStorage: không xác định được tenant hiện tại.');
        }

        $root = trim((string) config('tenant-storage.root_prefix', 'tenants'), '/');
        $path = "{$root}/{$tenantId}";

        if ($buildingId !== null) {
            $path .= "/projects/{$buildingId}";
        }

        return $path;
    }

    /** Khóa đầy đủ trên disk cho 1 đường dẫn tương đối trong vùng tenant/dự án. */
    public function key(string $relative, ?int $tenantId = null, ?int $buildingId = null): string
    {
        return $this->prefix($tenantId, $buildingId).'/'.ltrim($relative, '/');
    }

    public function exists(string $key): bool
    {
        return $this->disk()->exists($key);
    }

    public function move(string $from, string $to): string
    {
        $this->disk()->makeDirectory(dirname($to));
        $this->disk()->move($from, $to);

        return $to;
    }

    /**
     * Trả về đường dẫn FILE CỤC BỘ đọc được (cho thư viện đọc Excel theo path).
     * - disk local: đường dẫn thật.
     * - disk remote (s3/MinIO): tải về file tạm rồi trả path (giữ tính driver-agnostic).
     */
    public function localReadablePath(string $key): string
    {
        if ($this->isLocal()) {
            return $this->disk()->path($key);
        }

        $tmp = storage_path('app/tmp/'.md5($key).'-'.basename($key));
        if (! is_dir(dirname($tmp))) {
            @mkdir(dirname($tmp), 0775, true);
        }
        file_put_contents($tmp, $this->disk()->get($key));

        return $tmp;
    }

    /** Tải file về client (mọi driver) qua stream. */
    public function download(string $key, string $downloadName): StreamedResponse
    {
        return $this->disk()->download($key, $downloadName);
    }
}
