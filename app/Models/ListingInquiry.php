<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Tier 5 — Hộp thư lead của một tin rao: MỘT bảng cho cả ba loại tương tác
 * (`kind` = interest|viewing|contact) — xem migration
 * `add_listing_approval_workflow` để biết vì sao không tách ba bảng.
 */
class ListingInquiry extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'preferred_at' => 'datetime',
    ];

    public const KIND_INTEREST = 'interest';

    public const KIND_VIEWING = 'viewing';

    public const KIND_CONTACT = 'contact';

    public function listing(): BelongsTo
    {
        return $this->belongsTo(RealEstateListing::class, 'real_estate_listing_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function resident(): BelongsTo
    {
        return $this->belongsTo(Resident::class);
    }
}
