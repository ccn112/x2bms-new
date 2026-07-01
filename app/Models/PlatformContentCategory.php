<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Addendum — Danh mục nội dung nền tảng (news/banner/guide...). */
class PlatformContentCategory extends Model
{
    use SoftDeletes;
    protected $guarded = [];

    protected $casts = ['is_active' => 'boolean'];

    public function contents(): HasMany
    {
        return $this->hasMany(PlatformContent::class, 'category_id');
    }
}
