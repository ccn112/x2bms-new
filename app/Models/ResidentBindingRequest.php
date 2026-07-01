<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Addendum — Yêu cầu gắn tài khoản gốc vào căn hộ/tòa (BQL/Company duyệt). */
class ResidentBindingRequest extends Model
{
    use BelongsToTenant;

    protected $guarded = [];

    protected $casts = ['evidence_files_json' => 'array', 'requested_at' => 'datetime', 'reviewed_at' => 'datetime'];

    public function account(): BelongsTo
    {
        return $this->belongsTo(GlobalUserAccount::class, 'user_account_id');
    }

    public function apartment(): BelongsTo
    {
        return $this->belongsTo(Apartment::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
