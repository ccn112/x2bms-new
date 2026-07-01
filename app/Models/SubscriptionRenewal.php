<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Batch 07 — Pipeline gia hạn thuê bao/hợp đồng. */
class SubscriptionRenewal extends Model
{
    use SoftDeletes;
    protected $guarded = [];

    protected $casts = ['target_date' => 'date'];

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(TenantSubscription::class, 'subscription_id');
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(SubscriptionContract::class, 'contract_id');
    }
}
