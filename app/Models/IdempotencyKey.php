<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Bản ghi một Idempotency-Key đã nhận. Xem middleware
 * {@see \App\Http\Middleware\EnsureIdempotency} cho vòng đời (khóa → thực thi →
 * lưu response → phát lại).
 */
class IdempotencyKey extends Model
{
    protected $fillable = [
        'idempotency_key',
        'scope',
        'user_id',
        'method',
        'path',
        'request_hash',
        'response_status',
        'response_body',
        'locked_at',
    ];

    protected $casts = [
        'locked_at' => 'datetime',
    ];
}
