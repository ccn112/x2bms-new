<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Batch 10 — KB draft review workflow. */
class SupportKbDraftWorkflow extends Model
{
    protected $guarded = [];

    protected $casts = ['submitted_at' => 'datetime', 'reviewed_at' => 'datetime'];

    public function article(): BelongsTo
    {
        return $this->belongsTo(SupportKbArticle::class, 'support_kb_article_id');
    }
}
