<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Batch 08 — kết nối bên ngoài (platform-level Integration Center).
 * Canonical, thay bảng per-tenant sơ khai cũ. KHÔNG dùng tenant/project scope.
 */
class IntegrationConnection extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'metadata_json' => 'array',
        'idempotency_enabled' => 'boolean',
        'last_checked_at' => 'datetime',
        'success_rate_24h' => 'decimal:2',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(IntegrationCategory::class, 'category_id');
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function credentials(): HasMany
    {
        return $this->hasMany(IntegrationCredential::class, 'connection_id');
    }

    public function checks(): HasMany
    {
        return $this->hasMany(IntegrationConnectionCheck::class, 'connection_id');
    }

    public function mappings(): HasMany
    {
        return $this->hasMany(IntegrationMapping::class, 'connection_id');
    }
}
