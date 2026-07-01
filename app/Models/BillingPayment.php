<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Batch 07 — Thanh toán hóa đơn SaaS. */
class BillingPayment extends Model
{
    use SoftDeletes;
    protected $guarded = [];

    protected $casts = ['metadata_json' => 'array', 'paid_at' => 'datetime'];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(BillingInvoice::class, 'invoice_id');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
