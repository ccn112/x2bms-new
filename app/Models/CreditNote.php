<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Batch 07 — Giấy báo có (credit note) từ điều chỉnh. */
class CreditNote extends Model
{
    protected $guarded = [];

    protected $casts = ['issued_at' => 'datetime', 'applied_at' => 'datetime'];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(BillingInvoice::class, 'invoice_id');
    }

    public function adjustment(): BelongsTo
    {
        return $this->belongsTo(BillingAdjustment::class, 'adjustment_id');
    }
}
