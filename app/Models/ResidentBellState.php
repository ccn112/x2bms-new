<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Mốc high-water "đã thấy chuông" của một user (N0/ADR-001). Dùng để đếm chưa-đọc
 * broadcast mà KHÔNG ghi một dòng "chưa đọc" cho mỗi (thông báo × người).
 */
class ResidentBellState extends Model
{
    protected $table = 'resident_bell_state';

    protected $primaryKey = 'user_id';

    public $incrementing = false;

    protected $keyType = 'int';

    protected $guarded = [];

    protected $casts = [
        'bell_seen_at' => 'datetime',
    ];
}
