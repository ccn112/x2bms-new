<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Tier 3 — Mục trong checklist work order. */
class WorkOrderChecklistItem extends Model
{
    use SoftDeletes;
    protected $guarded = [];

    protected $casts = ['is_done' => 'boolean', 'done_at' => 'datetime'];

    public function checklist(): BelongsTo
    {
        return $this->belongsTo(WorkOrderChecklist::class, 'work_order_checklist_id');
    }

    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class);
    }
}
