<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Số lượt cài app do store báo — xem migration `create_store_install_stats_table`.
 *
 * KHÔNG có tenant: số của một app trên store, thuộc tầng nhà cung cấp. Đừng trộn
 * với `MobileDevice` (thiết bị đã đăng ký, chia được theo tenant).
 */
class StoreInstallStat extends Model
{
    public const SOURCE_GOOGLE_PLAY = 'google_play';

    public const SOURCE_APP_STORE = 'app_store';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'stat_date' => 'date',
            'raw' => 'array',
            'synced_at' => 'datetime',
        ];
    }
}
