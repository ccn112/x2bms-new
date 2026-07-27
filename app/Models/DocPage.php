<?php

namespace App\Models;

use App\Observers\DocPageObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Trang tài liệu — cây phân cấp (parent/children). Nội dung markdown.
 * Tự sinh revision mỗi khi title/body thay đổi (xem DocPageObserver).
 */
#[ObservedBy(DocPageObserver::class)]
class DocPage extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'sort' => 'integer',
    ];

    public function space(): BelongsTo
    {
        return $this->belongsTo(DocSpace::class, 'space_id');
    }

    /** Phiên bản sản phẩm trang thuộc về; null = trang chung (mọi version). */
    public function version(): BelongsTo
    {
        return $this->belongsTo(DocVersion::class, 'version_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort')->orderBy('title');
    }

    public function revisions(): HasMany
    {
        return $this->hasMany(DocPageRevision::class, 'page_id')->orderByDesc('version');
    }

    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /** Chuỗi path phân cấp (slug/slug/...) để dựng URL reader. */
    public function pathSegments(): array
    {
        $segments = [];
        $node = $this;
        while ($node) {
            array_unshift($segments, $node->slug);
            $node = $node->parent;
        }

        return $segments;
    }
}
