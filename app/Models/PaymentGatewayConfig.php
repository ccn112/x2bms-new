<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

/** Tier 4 — Cấu hình cổng thanh toán theo tenant. */
class PaymentGatewayConfig extends Model
{
    use BelongsToTenant;

    protected $guarded = [];

    protected $casts = ['is_active' => 'boolean', 'config' => 'array'];
}
