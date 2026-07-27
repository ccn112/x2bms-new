<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Không gian tài liệu (docs CMS kiểu GitBook). Reader lọc theo `audience` +
 * quyền `docs.view.{audience}`.
 */
class DocSpace extends Model
{
    protected $guarded = [];

    protected $casts = [
        'is_published' => 'boolean',
        'is_public' => 'boolean',
        'sort' => 'integer',
    ];

    /** 6 đối tượng đọc — khớp enum migration + permissions docs.view.{audience}. */
    public const AUDIENCES = ['dev', 'ops', 'bql', 'hq', 'sa', 'resident'];

    public function pages(): HasMany
    {
        return $this->hasMany(DocPage::class, 'space_id');
    }

    /** Trang gốc (không có parent) — dùng dựng cây sidebar. */
    public function rootPages(): HasMany
    {
        return $this->pages()->whereNull('parent_id')->orderBy('sort')->orderBy('title');
    }

    public function getRouteKeyName(): string
    {
        return 'key';
    }
}
