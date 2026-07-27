<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

/**
 * Đính kèm dùng chung (polymorphic). Gắn cho Comment và các phiếu tương tác.
 * `attachable` null = mới upload, chưa link.
 */
class Attachment extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'size' => 'integer',
        'order_column' => 'integer',
        'width' => 'integer',
        'height' => 'integer',
        'variants' => 'array',
    ];

    public function attachable(): MorphTo
    {
        return $this->morphTo();
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /** URL công khai (ưu tiên cột url đã lưu, else dựng từ disk+path). */
    public function getPublicUrlAttribute(): ?string
    {
        if (! empty($this->url)) {
            return $this->url;
        }
        try {
            return Storage::disk($this->disk)->url($this->path);
        } catch (\Throwable) {
            return null;
        }
    }

    public function isImage(): bool
    {
        return str_starts_with((string) $this->mime_type, 'image/');
    }

    /**
     * URL của một biến thể (`thumb` 320 / `feed` 1080 / `original` 2048).
     * Ảnh upload TRƯỚC khi có pipeline chưa có biến thể → rơi về ảnh gốc, nên
     * app không phải xử lý trường hợp null.
     */
    public function variantUrl(string $name): ?string
    {
        $path = $this->variants[$name] ?? null;
        if ($path === null) {
            return $this->public_url;
        }
        try {
            return Storage::disk($this->disk)->url($path);
        } catch (\Throwable) {
            return $this->public_url;
        }
    }

    /** Xoá cả file gốc lẫn biến thể — soft-delete không dọn được đĩa. */
    public function purgeFiles(): void
    {
        $disk = Storage::disk($this->disk);
        foreach (array_merge([$this->path], array_values($this->variants ?? [])) as $p) {
            try {
                $disk->delete($p);
            } catch (\Throwable) {
                // file đã mất — không phải lỗi cần chặn
            }
        }
    }
}
