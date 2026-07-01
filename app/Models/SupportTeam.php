<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Batch 10 — Support team (L1/L2/L3/account). */
class SupportTeam extends Model
{
    protected $guarded = [];

    protected $casts = ['is_active' => 'boolean'];

    public function members(): HasMany
    {
        return $this->hasMany(SupportTeamMember::class);
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(SupportTicket::class, 'team_id');
    }
}
