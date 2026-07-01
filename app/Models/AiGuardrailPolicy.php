<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Addendum — Chính sách guardrail cho AI (privacy/finance/legal/safety...). */
class AiGuardrailPolicy extends Model
{
    protected $guarded = [];

    protected $casts = ['rule_json' => 'array', 'is_active' => 'boolean'];
}
