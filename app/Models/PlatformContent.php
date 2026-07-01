<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Addendum — Nội dung nền tảng (tin tức/thông báo/banner/guide) do SuperAdmin quản lý. */
class PlatformContent extends Model
{
    use SoftDeletes;
    protected $guarded = [];

    protected $casts = ['metadata_json' => 'array', 'published_at' => 'datetime', 'expired_at' => 'datetime'];

    public function category(): BelongsTo
    {
        return $this->belongsTo(PlatformContentCategory::class, 'category_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
