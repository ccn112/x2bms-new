<?php

namespace App\Models;

use App\Enums\ResidentApprovalStatus;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResidentApprovalRequest extends Model
{
    use BelongsToTenant;

    protected $guarded = [];

    protected $casts = [
        'status' => ResidentApprovalStatus::class,
        'submitted_at' => 'datetime',
    ];

    public function apartment(): BelongsTo
    {
        return $this->belongsTo(Apartment::class);
    }
}
