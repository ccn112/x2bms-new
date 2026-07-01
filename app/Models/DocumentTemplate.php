<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Addendum — Mẫu tài liệu (SOP/policy/contract/form/checklist) 3 cấp sở hữu. */
class DocumentTemplate extends Model
{
    protected $guarded = [];

    protected $casts = ['variables_json' => 'array', 'ai_readable' => 'boolean', 'effective_from' => 'date', 'effective_to' => 'date'];

    public function category(): BelongsTo
    {
        return $this->belongsTo(DocumentTemplateCategory::class, 'category_id');
    }

    public function shares(): HasMany
    {
        return $this->hasMany(DocumentTemplateShare::class, 'template_id');
    }

    public function clones(): HasMany
    {
        return $this->hasMany(DocumentTemplateClone::class, 'source_template_id');
    }
}
