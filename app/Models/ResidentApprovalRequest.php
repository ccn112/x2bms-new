<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Concerns\BelongsToProject;

use App\Enums\ResidentApprovalStatus;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResidentApprovalRequest extends Model
{
    use BelongsToTenant, SoftDeletes, BelongsToProject;

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
