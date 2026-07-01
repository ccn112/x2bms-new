<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/** Batch 10 — Controlled data correction request (canonical, thay data_fix_requests cũ). */
class DataCorrectionRequest extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'approved_at' => 'datetime',
        'execution_window_at' => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(SupportTicket::class, 'support_ticket_id');
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_id');
    }

    public function affectedRecords(): HasMany
    {
        return $this->hasMany(DataCorrectionAffectedRecord::class);
    }

    public function snapshots(): HasMany
    {
        return $this->hasMany(DataFixSnapshot::class);
    }

    public function diffItems(): HasMany
    {
        return $this->hasMany(DataFixDiffItem::class);
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(DataFixApproval::class);
    }

    public function executions(): HasMany
    {
        return $this->hasMany(DataFixExecution::class);
    }

    public function rollbacks(): HasMany
    {
        return $this->hasMany(DataFixRollback::class);
    }
}
