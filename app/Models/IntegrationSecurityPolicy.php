<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Batch 08 — chính sách bảo mật tích hợp (key/value, có bật/tắt). */
class IntegrationSecurityPolicy extends Model
{
    protected $guarded = [];

    protected $casts = [
        'policy_value_json' => 'array',
        'is_enabled' => 'boolean',
    ];

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
