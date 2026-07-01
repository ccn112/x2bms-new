<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Batch 08 — nhóm loại tích hợp (communication/finance/erp/ai/custom/iot). */
class IntegrationCategory extends Model
{
    protected $guarded = [];

    protected $casts = ['is_active' => 'boolean'];

    public function connections(): HasMany
    {
        return $this->hasMany(IntegrationConnection::class, 'category_id');
    }
}
