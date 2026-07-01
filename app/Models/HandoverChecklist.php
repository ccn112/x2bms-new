<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Tier 5 — Checklist bàn giao căn. */
class HandoverChecklist extends Model
{
    use SoftDeletes;
    protected $guarded = [];

    public function unit(): BelongsTo
    {
        return $this->belongsTo(HandoverUnit::class, 'handover_unit_id');
    }

    public function punchItems(): HasMany
    {
        return $this->hasMany(HandoverPunchItem::class);
    }
}
