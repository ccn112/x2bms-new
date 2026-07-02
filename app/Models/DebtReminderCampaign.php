<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class DebtReminderCampaign extends Model
{
    use BelongsToTenant, SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'response_rate' => 'decimal:2',
        'committed_amount' => 'decimal:2',
        'collected_amount' => 'decimal:2',
        'content_template' => 'array',
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
    ];

    public function owner(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(DebtReminderLog::class, 'campaign_id');
    }
}
