<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FeeType extends Model
{
    use BelongsToTenant, SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'is_recurring' => 'boolean',
        'is_complex' => 'boolean',
        'vat_percent' => 'decimal:2',
        'effective_from' => 'date',
    ];

    public function rates(): HasMany
    {
        return $this->hasMany(FeeRate::class);
    }

    public function formulas(): HasMany
    {
        return $this->hasMany(FeeFormula::class);
    }

    public function scopeAssignments(): HasMany
    {
        return $this->hasMany(FeeScopeAssignment::class);
    }
}
