<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

/** Tier 4 — Kết nối tích hợp bên thứ 3 (payment/sms/zalo/accounting...). */
class IntegrationConnection extends Model
{
    use BelongsToTenant;

    protected $guarded = [];

    protected $casts = ['config' => 'array', 'last_sync_at' => 'datetime'];
}
