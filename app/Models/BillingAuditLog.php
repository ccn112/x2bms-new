<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Batch 07 — Audit riêng cho mọi hành động billing (before/after json). */
class BillingAuditLog extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    protected $casts = ['before_json' => 'array', 'after_json' => 'array', 'created_at' => 'datetime'];

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
