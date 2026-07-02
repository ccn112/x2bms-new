<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class MetricSnapshot extends Model
{
    use BelongsToTenant;

    protected $guarded = [];

    protected $casts = [
        'value' => 'decimal:2',
        'dimension' => 'array',
        'captured_at' => 'datetime',
    ];
}
