<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Tier 4 — Hóa đơn SaaS gửi công ty (C2). */
class SubscriptionInvoice extends Model
{
    use BelongsToTenant;

    protected $guarded = [];

    protected $casts = [
        'amount' => 'decimal:2', 'tax' => 'decimal:2', 'total' => 'decimal:2',
        'period_start' => 'date', 'period_end' => 'date', 'due_date' => 'date',
        'issued_at' => 'datetime', 'paid_at' => 'datetime',
    ];

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(SubscriptionInvoiceLine::class);
    }
}
