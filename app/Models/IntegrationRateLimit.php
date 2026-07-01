<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Batch 08 — cấu hình rate limit theo scope (global/api_key/connection). */
class IntegrationRateLimit extends Model
{
    protected $guarded = [];

    protected $casts = ['is_enabled' => 'boolean'];
}
