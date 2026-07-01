<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Addendum — Nhà thầu/NCC/dịch vụ trong thư viện dùng chung toàn nền tảng. */
class SharedPartner extends Model
{
    use SoftDeletes;
    protected $guarded = [];

    protected $casts = ['metadata_json' => 'array', 'rating_avg' => 'decimal:1', 'kpi_score' => 'decimal:2', 'is_active' => 'boolean'];

    public function category(): BelongsTo
    {
        return $this->belongsTo(SharedPartnerCategory::class, 'category_id');
    }

    public function certifications(): HasMany
    {
        return $this->hasMany(SharedPartnerCertification::class, 'partner_id');
    }

    public function products(): HasMany
    {
        return $this->hasMany(SharedPartnerProduct::class, 'partner_id');
    }
}
