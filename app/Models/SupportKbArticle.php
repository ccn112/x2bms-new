<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/** Batch 10 — Support KB article (SOP/runbook/FAQ). */
class SupportKbArticle extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected $casts = ['rating' => 'decimal:2', 'published_at' => 'datetime'];

    public function category(): BelongsTo
    {
        return $this->belongsTo(SupportKbCategory::class, 'category_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(SupportKbArticleVersion::class);
    }
}
