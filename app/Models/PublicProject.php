<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Model;
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
}
