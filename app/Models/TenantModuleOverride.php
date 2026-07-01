<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Addendum — Ghi đè bật/tắt module theo tenant (white-label / thủ công). */
class TenantModuleOverride extends Model
{
    use BelongsToTenant;

    protected $guarded = [];

    protected $casts = ['enabled' => 'boolean'];

    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }
}
