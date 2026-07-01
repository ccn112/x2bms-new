<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Tier 6 — Đoạn văn bản tách từ bài KB để RAG/embeddings. */
class KnowledgeChunk extends Model
{
    protected $guarded = [];

    protected $casts = ['embedding' => 'array'];

    public function article(): BelongsTo
    {
        return $this->belongsTo(KnowledgeArticle::class, 'knowledge_article_id');
    }
}
