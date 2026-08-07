<?php

namespace App\Models;

use App\Enums\CommunicationApprovalStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Tuyến duyệt chiến dịch (maker-checker) — BQL-NOTI-05. */
class NotificationApproval extends Model
{
    protected $guarded = [];

    protected $casts = [
        'status' => CommunicationApprovalStatus::class,
        'requested_at' => 'datetime',
        'due_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    public function notification(): BelongsTo
    {
        return $this->belongsTo(Notification::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by_id');
    }

    public function steps(): HasMany
    {
        return $this->hasMany(NotificationApprovalStep::class, 'approval_id')->orderBy('step_no');
    }

    public function currentStep(): ?NotificationApprovalStep
    {
        return $this->steps->firstWhere('step_no', $this->current_step);
    }
}
