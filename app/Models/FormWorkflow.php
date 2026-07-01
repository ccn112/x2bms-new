<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Tier 4 — Luồng duyệt của biểu mẫu động. */
class FormWorkflow extends Model
{
    protected $guarded = [];

    protected $casts = ['steps' => 'array'];

    public function form(): BelongsTo
    {
        return $this->belongsTo(DynamicForm::class, 'dynamic_form_id');
    }
}
