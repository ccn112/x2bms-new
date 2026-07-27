<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Phiên bản SẢN PHẨM/TÀI LIỆU (v1.0/v2.0…) — đợt phát triển toàn site.
 * KHÁC với DocPageRevision (lịch sử sửa từng trang).
 */
class DocVersion extends Model
{
    protected $guarded = [];

    protected $casts = [
        'released_at' => 'date',
        'is_current' => 'boolean',
        'sort' => 'integer',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(DocVersionItem::class)->orderBy('sort')->orderBy('id');
    }

    public function pages(): HasMany
    {
        return $this->hasMany(DocPage::class, 'version_id');
    }

    public function getRouteKeyName(): string
    {
        return 'label';
    }

    /** Version mặc định hiển thị: is_current, fallback bản mới nhất theo sort. */
    public static function current(): ?self
    {
        return static::where('is_current', true)->first()
            ?? static::orderByDesc('sort')->orderByDesc('id')->first();
    }
}
