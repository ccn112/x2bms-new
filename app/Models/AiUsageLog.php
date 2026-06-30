<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One row per X2AI interaction. Feeds Trung tâm AI (09-01) usage metrics and the
 * Governance & Audit AI (09-02) audit log.
 */
class AiUsageLog extends Model
{
    use BelongsToTenant;

    protected $guarded = [];

    protected $casts = [
        'requires_approval' => 'boolean',
        'cost' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function building(): BelongsTo
    {
        return $this->belongsTo(Building::class);
    }
}
