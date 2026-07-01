<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Batch 07 — Hóa đơn SaaS B2B (canonical, thay subscription_invoices cũ). */
class BillingInvoice extends Model
{
    protected $guarded = [];

    protected $casts = ['metadata_json' => 'array', 'issue_date' => 'date', 'due_date' => 'date'];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(TenantSubscription::class, 'subscription_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(BillingInvoiceLine::class, 'invoice_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(BillingPayment::class, 'invoice_id');
    }
}
