<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Batch 10 — KB article version (history). */
class SupportKbArticleVersion extends Model
{
    public const UPDATED_AT = null;

    protected $guarded = [];

    protected $casts = ['created_at' => 'datetime'];

    public function article(): BelongsTo
    {
        return $this->belongsTo(SupportKbArticle::class, 'support_kb_article_id');
    }
}
