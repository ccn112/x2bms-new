<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Tier 5 — Nhà cung cấp dịch vụ tiện ích (giặt là, đồ ăn, sửa chữa...). */
class ServiceProvider extends Model
{
    use BelongsToTenant;

    protected $guarded = [];

    protected $casts = ['rating' => 'decimal:1'];

    public function orders(): HasMany
    {
        return $this->hasMany(ServiceOrder::class);
    }
}
