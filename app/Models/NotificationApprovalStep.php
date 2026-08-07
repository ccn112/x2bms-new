<?php

namespace App\Models;

use App\Enums\CommunicationApprovalStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Một bước trong tuyến duyệt chiến dịch. */
class NotificationApprovalStep extends Model
{
    protected $guarded = [];

    protected $casts = [
        'status' => CommunicationApprovalStatus::class,
        'sla_due_at' => 'datetime',
        'acted_at' => 'datetime',
    ];

    public function approval(): BelongsTo
    {
        return $this->belongsTo(NotificationApproval::class, 'approval_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
