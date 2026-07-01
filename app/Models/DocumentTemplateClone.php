<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Addendum — Bản clone của mẫu tài liệu (tạo owner mới). */
class DocumentTemplateClone extends Model
{
    protected $guarded = [];

    protected $casts = ['cloned_at' => 'datetime'];

    public function source(): BelongsTo
    {
        return $this->belongsTo(DocumentTemplate::class, 'source_template_id');
    }

    public function cloned(): BelongsTo
    {
        return $this->belongsTo(DocumentTemplate::class, 'cloned_template_id');
    }
}
