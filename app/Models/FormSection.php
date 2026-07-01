<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Tier 4 — Phần (section) trong biểu mẫu. */
class FormSection extends Model
{
    use SoftDeletes;
    protected $guarded = [];

    public function form(): BelongsTo
    {
        return $this->belongsTo(DynamicForm::class, 'dynamic_form_id');
    }

    public function fields(): HasMany
    {
        return $this->hasMany(FormField::class);
    }
}
