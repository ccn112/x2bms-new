<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Tier 3 — Chữ ký nghiệm thu work order (kỹ thuật/cư dân/giám sát). */
class WorkOrderSignature extends Model
{
    use SoftDeletes;
    protected $guarded = [];

    protected $casts = ['signed_at' => 'datetime'];

    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class);
    }
}
