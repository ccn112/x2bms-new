<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Tier 5 — Đợt bàn giao căn hộ. */
class HandoverBatch extends Model
{
    use BelongsToTenant;

    protected $guarded = [];

    protected $casts = ['scheduled_date' => 'date'];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function units(): HasMany
    {
        return $this->hasMany(HandoverUnit::class);
    }
}
