<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Tier 6 — Hàng chờ duyệt hành động AI rủi ro cao (human-in-the-loop). */
class AiApproval extends Model
{
    use BelongsToTenant;

    protected $guarded = [];

    protected $casts = ['decided_at' => 'datetime'];

    public function usageLog(): BelongsTo
    {
        return $this->belongsTo(AiUsageLog::class, 'ai_usage_log_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_id');
    }
}
