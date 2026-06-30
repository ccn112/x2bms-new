<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** WEB-UX-09-03 · Nhật ký chạy workflow. */
class AiWorkflowRun extends Model
{
    protected $guarded = [];

    protected $casts = [
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    public function workflow(): BelongsTo
    {
        return $this->belongsTo(AiWorkflow::class, 'ai_workflow_id');
    }
}
