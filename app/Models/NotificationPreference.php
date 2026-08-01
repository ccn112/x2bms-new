<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Tuỳ chọn bật/tắt một kênh thông báo của một user. */
class NotificationPreference extends Model
{
    protected $guarded = [];

    protected $casts = ['enabled' => 'boolean'];
}
