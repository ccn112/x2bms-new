<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

/** Tier 5 — Voucher/ưu đãi đổi điểm. */
class Voucher extends Model
{
    use BelongsToTenant, SoftDeletes;

    protected $guarded = [];

    protected $casts = ['value' => 'decimal:2', 'valid_from' => 'date', 'valid_to' => 'date'];
}
