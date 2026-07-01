<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Tier 4 — Hợp đồng nhà thầu (C7). */
class Contract extends Model
{
    use BelongsToTenant, SoftDeletes;

    protected $guarded = [];

    protected $casts = ['value' => 'decimal:2', 'start_date' => 'date', 'end_date' => 'date'];

    public function contractor(): BelongsTo
    {
        return $this->belongsTo(Contractor::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function packages(): HasMany
    {
        return $this->hasMany(ContractPackage::class);
    }

    public function acceptances(): HasMany
    {
        return $this->hasMany(ContractAcceptance::class);
    }
}
