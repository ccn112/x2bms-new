<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Ví cư dân theo CĂN HỘ. `balance` là quỹ chung (chưa gán loại phí);
 * tổng khả dụng = balance + Σ buckets. Khác `Wallet` (quỹ công ty per-tenant).
 */
class ApartmentWallet extends Model
{
    use BelongsToTenant, SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'balance' => 'decimal:2',
    ];

    public function apartment()
    {
        return $this->belongsTo(Apartment::class);
    }

    public function buckets(): HasMany
    {
        return $this->hasMany(ApartmentWalletBucket::class, 'wallet_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(ApartmentWalletTransaction::class, 'wallet_id');
    }

    /** Tổng khả dụng = quỹ chung + tất cả các ngăn. */
    public function availableTotal(): string
    {
        return bcadd((string) $this->balance, (string) $this->buckets->sum('balance'), 2);
    }
}
