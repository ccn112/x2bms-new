<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/** Batch 08 — credential/secret của kết nối (mã hoá, không lưu plain-text). */
class IntegrationCredential extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected $hidden = ['encrypted_payload'];

    protected $casts = [
        'expires_at' => 'datetime',
        'rotated_at' => 'datetime',
    ];

    public function connection(): BelongsTo
    {
        return $this->belongsTo(IntegrationConnection::class, 'connection_id');
    }

    public function rotatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rotated_by');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
