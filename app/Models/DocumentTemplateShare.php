<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Addendum — Chia sẻ/kế thừa mẫu tài liệu xuống cấp dưới. */
class DocumentTemplateShare extends Model
{
    protected $guarded = [];

    protected $casts = ['can_ai_read' => 'boolean', 'effective_from' => 'date', 'effective_to' => 'date'];

    public function template(): BelongsTo
    {
        return $this->belongsTo(DocumentTemplate::class, 'template_id');
    }
}
