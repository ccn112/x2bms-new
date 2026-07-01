<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Tier 4 — Nhà thầu/nhà cung cấp dịch vụ. */
class Contractor extends Model
{
    use BelongsToTenant, SoftDeletes;

    protected $guarded = [];

    protected $casts = ['rating' => 'decimal:1'];

    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class);
    }
}
