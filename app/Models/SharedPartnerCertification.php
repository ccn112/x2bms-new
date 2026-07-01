<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Addendum — Chứng chỉ/năng lực của đối tác dùng chung. */
class SharedPartnerCertification extends Model
{
    protected $guarded = [];

    protected $casts = ['issued_at' => 'date', 'expired_at' => 'date'];

    public function partner(): BelongsTo
    {
        return $this->belongsTo(SharedPartner::class, 'partner_id');
    }
}
