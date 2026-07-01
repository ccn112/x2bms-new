<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Addendum — Vật tư/sản phẩm/dịch vụ chuẩn của đối tác dùng chung. */
class SharedPartnerProduct extends Model
{
    use SoftDeletes;
    protected $guarded = [];

    protected $casts = ['reference_price' => 'decimal:2', 'is_active' => 'boolean'];

    public function partner(): BelongsTo
    {
        return $this->belongsTo(SharedPartner::class, 'partner_id');
    }
}
