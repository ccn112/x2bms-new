<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Batch 08 — mapping sự kiện/field/status của một kết nối (có version). */
class IntegrationMapping extends Model
{
    protected $guarded = [];

    protected $casts = ['mapping_json' => 'array'];

    public function connection(): BelongsTo
    {
        return $this->belongsTo(IntegrationConnection::class, 'connection_id');
    }
}
