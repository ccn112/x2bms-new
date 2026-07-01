<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/** Batch 10 — Support ticket (platform Support Center, canonical). */
class SupportTicket extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'tags' => 'array',
        'csat_score' => 'decimal:2',
        'sla_due_at' => 'datetime',
        'first_response_at' => 'datetime',
        'resolved_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(SupportTeam::class, 'team_id');
    }

    public function slaPolicy(): BelongsTo
    {
        return $this->belongsTo(SupportSlaPolicy::class, 'sla_policy_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(SupportTicketMessage::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(SupportTicketAttachment::class);
    }

    public function statusLogs(): HasMany
    {
        return $this->hasMany(SupportTicketStatusLog::class);
    }

    public function escalations(): HasMany
    {
        return $this->hasMany(SupportEscalation::class);
    }

    public function dataCorrectionRequests(): HasMany
    {
        return $this->hasMany(DataCorrectionRequest::class);
    }
}
