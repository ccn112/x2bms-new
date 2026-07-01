<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Addendum — Tài liệu KB governance 3 cấp cho AI (sensitivity + ai_index_status). */
class KnowledgeDocument extends Model
{
    use SoftDeletes;
    protected $guarded = [];

    protected $casts = ['metadata_json' => 'array', 'ai_indexed_at' => 'datetime', 'effective_from' => 'date', 'effective_to' => 'date'];

    public function sourceTemplate(): BelongsTo
    {
        return $this->belongsTo(DocumentTemplate::class, 'source_template_id');
    }

    public function scopes(): HasMany
    {
        return $this->hasMany(KnowledgeScope::class);
    }
}
