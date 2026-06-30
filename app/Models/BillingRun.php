<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BillingRun extends Model
{
    use BelongsToTenant;

    protected $guarded = [];

    protected $casts = [
        'total_billed' => 'decimal:2',
        'run_at' => 'datetime',
        'sla_due_at' => 'datetime',
    ];

    public function billingPeriod(): BelongsTo
    {
        return $this->belongsTo(BillingPeriod::class);
    }

    public function building(): BelongsTo
    {
        return $this->belongsTo(Building::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(BillingRunItem::class);
    }
}
