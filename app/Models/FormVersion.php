<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Tier 4 — Phiên bản schema của biểu mẫu. */
class FormVersion extends Model
{
    protected $guarded = [];

    protected $casts = ['schema' => 'array', 'published_at' => 'datetime'];

    public function form(): BelongsTo
    {
        return $this->belongsTo(DynamicForm::class, 'dynamic_form_id');
    }
}
