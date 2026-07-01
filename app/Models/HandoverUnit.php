<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Tier 5 — Một căn trong đợt bàn giao. */
class HandoverUnit extends Model
{
    use SoftDeletes;
    protected $guarded = [];

    protected $casts = ['handed_over_at' => 'datetime'];

    public function batch(): BelongsTo
    {
        return $this->belongsTo(HandoverBatch::class, 'handover_batch_id');
    }

    public function apartment(): BelongsTo
    {
        return $this->belongsTo(Apartment::class);
    }

    public function checklists(): HasMany
    {
        return $this->hasMany(HandoverChecklist::class);
    }
}
