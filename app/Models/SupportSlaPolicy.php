<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Batch 10 — SLA policy per priority. */
class SupportSlaPolicy extends Model
{
    protected $guarded = [];

    protected $casts = ['is_active' => 'boolean'];
}
