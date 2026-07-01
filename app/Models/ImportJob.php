<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

/** Tier 4 — Job nhập dữ liệu (residents/apartments/statements...). */
class ImportJob extends Model
{
    use BelongsToTenant;

    protected $guarded = [];

    protected $casts = ['finished_at' => 'datetime'];
}
