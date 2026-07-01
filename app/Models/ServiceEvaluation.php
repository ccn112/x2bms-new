<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Tier 2 — Đánh giá dịch vụ của cư dân sau khi xử lý (phản ánh/công việc). */
class ServiceEvaluation extends Model
{
    use BelongsToTenant;

    protected $guarded = [];

    protected $casts = [
        'criteria' => 'array',
        'evaluated_at' => 'datetime',
    ];

    public function feedbackRequest(): BelongsTo
    {
        return $this->belongsTo(FeedbackRequest::class);
    }

    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class);
    }

    public function resident(): BelongsTo
    {
        return $this->belongsTo(Resident::class);
    }
}
