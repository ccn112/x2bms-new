<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Batch 07 — Dòng hóa đơn (subscription/addon/usage_overage/pass_through/discount/tax/adjustment). */
class BillingInvoiceLine extends Model
{
    protected $guarded = [];

    protected $casts = ['metadata_json' => 'array'];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(BillingInvoice::class, 'invoice_id');
    }
}
