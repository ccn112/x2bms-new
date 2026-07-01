<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Tier 3 — Cấu hình SLA (C4): thời gian phản hồi/xử lý theo loại + mức ưu tiên. */
class SlaPolicy extends Model
{
    use BelongsToTenant;

    protected $guarded = [];

    protected $casts = ['business_hours_only' => 'boolean'];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
