<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Tier 4 — Trường dữ liệu trong biểu mẫu động. */
class FormField extends Model
{
    protected $guarded = [];

    protected $casts = ['options' => 'array', 'config' => 'array', 'required' => 'boolean'];

    public function form(): BelongsTo
    {
        return $this->belongsTo(DynamicForm::class, 'dynamic_form_id');
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(FormSection::class, 'form_section_id');
    }
}
