<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StatementLine extends Model
{
    protected $guarded = [];

    protected $casts = [
        'amount' => 'decimal:2',
    ];
}
