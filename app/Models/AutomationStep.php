<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Tier 6 — Bước của workflow tự động (bảng hoá, cạnh steps JSON). */
class AutomationStep extends Model
{
    protected $guarded = [];

    protected $casts = ['config' => 'array'];

    public function workflow(): BelongsTo
    {
        return $this->belongsTo(AiWorkflow::class, 'ai_workflow_id');
    }
}
