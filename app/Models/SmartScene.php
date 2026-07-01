<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Tier 5 — Ngữ cảnh (scene) nhà thông minh. */
class SmartScene extends Model
{
    use SoftDeletes;
    protected $guarded = [];

    protected $casts = ['is_active' => 'boolean'];

    public function account(): BelongsTo
    {
        return $this->belongsTo(SmartHomeAccount::class, 'smart_home_account_id');
    }
}
