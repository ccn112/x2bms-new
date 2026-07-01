<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

/** Tier 4 — Job xuất dữ liệu (xlsx/csv/pdf). */
class ExportJob extends Model
{
    use BelongsToTenant;

    protected $guarded = [];

    protected $casts = ['params' => 'array', 'finished_at' => 'datetime'];
}
