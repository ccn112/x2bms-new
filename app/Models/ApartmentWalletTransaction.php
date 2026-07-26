<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

/**
 * Sổ ví cư dân. IN = phiếu thu / nộp tiền / topup; OUT = hạch toán trả nợ / hoàn / điều chỉnh.
 * `reference` (morph) trỏ tới Receipt / Statement / StatementLine / Debt liên quan.
 */
class ApartmentWalletTransaction extends Model
{
    use BelongsToTenant;

    protected $guarded = [];

    protected $casts = [
        'amount' => 'decimal:2',
        'balance_after' => 'decimal:2',
        'posted_at' => 'datetime',
    ];

    public function wallet()
    {
        return $this->belongsTo(ApartmentWallet::class, 'wallet_id');
    }

    public function reference()
    {
        return $this->morphTo();
    }
}
