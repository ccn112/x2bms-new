<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Tier 4 — Tài sản/thiết bị tòa nhà. */
class Asset extends Model
{
    use BelongsToTenant;

    protected $guarded = [];

    protected $casts = ['purchase_date' => 'date', 'warranty_until' => 'date', 'value' => 'decimal:2'];

    public function category(): BelongsTo
    {
        return $this->belongsTo(AssetCategory::class, 'asset_category_id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function building(): BelongsTo
    {
        return $this->belongsTo(Building::class);
    }
}
