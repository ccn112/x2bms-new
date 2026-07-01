<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Tier 5 — Lỗi/điểm cần khắc phục khi bàn giao (alias handover_defects). */
class HandoverPunchItem extends Model
{
    protected $guarded = [];

    protected $casts = ['is_ok' => 'boolean'];

    public function checklist(): BelongsTo
    {
        return $this->belongsTo(HandoverChecklist::class, 'handover_checklist_id');
    }
}
