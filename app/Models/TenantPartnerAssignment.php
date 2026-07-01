<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Addendum — Gán đối tác dùng chung cho tenant/dự án (approved/contracted/blacklist/favorite). */
class TenantPartnerAssignment extends Model
{
    use BelongsToTenant;

    protected $guarded = [];

    protected $casts = ['start_date' => 'date', 'end_date' => 'date'];

    public function partner(): BelongsTo
    {
        return $this->belongsTo(SharedPartner::class, 'partner_id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
