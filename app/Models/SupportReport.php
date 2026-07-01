<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Batch 10 — Support report snapshot (resolution report / dashboard snapshot metrics). */
class SupportReport extends Model
{
    protected $guarded = [];

    protected $casts = ['metrics_json' => 'array'];
}
