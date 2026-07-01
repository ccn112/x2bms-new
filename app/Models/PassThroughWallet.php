<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Batch 07 — Ví pass-through theo kênh (sms/zalo/email/ai_token...). */
class PassThroughWallet extends Model
{
    use SoftDeletes;
    protected $guarded = [];

    protected $casts = ['auto_topup_enabled' => 'boolean'];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(PassThroughTransaction::class, 'wallet_id');
    }
}
