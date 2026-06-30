<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FeeFormula extends Model
{
    use BelongsToTenant;

    protected $guarded = [];

    protected $casts = [
        'variables' => 'array',
    ];

    public function feeType(): BelongsTo
    {
        return $this->belongsTo(FeeType::class);
    }

    public function versions(): HasMany
    {
        return $this->hasMany(FeeFormulaVersion::class);
    }
}
