<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

/**
 * Ngăn ví: earmark tiền thừa theo fee_category, tùy chọn xuống fee_type cụ thể.
 * fee_type_id NULL = ngăn cấp nhóm (dùng cho mọi fee_type trong nhóm).
 */
class ApartmentWalletBucket extends Model
{
    use BelongsToTenant;

    protected $guarded = [];

    protected $casts = [
        'balance' => 'decimal:2',
    ];

    public function wallet()
    {
        return $this->belongsTo(ApartmentWallet::class, 'wallet_id');
    }

    public function feeType()
    {
        return $this->belongsTo(FeeType::class);
    }

    /**
     * Tài sản mà ngăn này earmark tiền cho (D6): Vehicle/Meter/…; NULL = ngăn theo
     * fee_type như cũ (phí không gắn tài sản).
     */
    public function subject(): \Illuminate\Database\Eloquent\Relations\MorphTo
    {
        return $this->morphTo();
    }
}
