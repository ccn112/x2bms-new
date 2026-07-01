<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Addendum — Danh mục đối tác dùng chung. */
class SharedPartnerCategory extends Model
{
    use SoftDeletes;
    protected $guarded = [];

    protected $casts = ['is_active' => 'boolean'];

    public function partners(): HasMany
    {
        return $this->hasMany(SharedPartner::class, 'category_id');
    }
}
