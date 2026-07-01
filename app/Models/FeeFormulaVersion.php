<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeeFormulaVersion extends Model
{
    use SoftDeletes;
    protected $guarded = [];

    protected $casts = [
        'effective_from' => 'date',
    ];

    public function feeFormula(): BelongsTo
    {
        return $this->belongsTo(FeeFormula::class);
    }
}
