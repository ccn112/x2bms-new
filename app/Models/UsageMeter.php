<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Batch 07 — Định nghĩa loại meter usage. */
class UsageMeter extends Model
{
    protected $guarded = [];

    protected $casts = ['is_billable' => 'boolean'];
}
