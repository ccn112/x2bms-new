<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Addendum — Dự án/tòa trong thư viện public dùng chung toàn nền tảng. */
class PublicProject extends Model
{
    use SoftDeletes;
    protected $guarded = [];

    protected $casts = ['amenities_json' => 'array', 'metadata_json' => 'array', 'is_public' => 'boolean'];

    public function media(): HasMany
    {
        return $this->hasMany(ProjectMedia::class);
    }

    public function developer(): BelongsTo
    {
        return $this->belongsTo(Developer::class);
    }

    /** URL ảnh bìa: ưu tiên ProjectMedia is_cover (official/manual > batdongsan), fallback metadata. */
    public function coverUrl(): ?string
    {
        $cover = $this->media->firstWhere('is_cover', true)
            ?? $this->media->sortBy('sort_order')->first();
        if ($cover) {
            return $cover->file_url;
        }
        $meta = (array) $this->metadata_json;

        return $meta['official_cover'] ?? $meta['cover_image'] ?? $meta['image'] ?? null;
    }
}
