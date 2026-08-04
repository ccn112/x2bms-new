<?php

declare(strict_types=1);

namespace App\Services\Notifications;

use App\Models\BuildingNotificationChannel;

/**
 * ADR-002 — tra CẤU HÌNH KÊNH của một TÒA. Trả về `BuildingNotificationChannel`
 * (đã bỏ global scope tenant vì gửi thông báo chạy nền/console) hoặc null nếu tòa
 * chưa khai kênh đó. `MultiChannelNotifier` dùng để chọn adapter + trạng thái cổng chờ.
 *
 * Cache trong-request theo (buildingId): một lượt gửi đụng nhiều người cùng tòa nên
 * không truy vấn lại mỗi người.
 *
 * @var array<int, array<string, BuildingNotificationChannel|null>> $cache
 */
class ChannelConfigResolver
{
    private array $cache = [];

    public function for(?int $buildingId, string $channel): ?BuildingNotificationChannel
    {
        if ($buildingId === null) {
            return null;
        }

        if (! array_key_exists($buildingId, $this->cache)) {
            $this->cache[$buildingId] = BuildingNotificationChannel::query()
                ->withoutGlobalScopes()
                ->where('building_id', $buildingId)
                ->get()
                ->keyBy('channel')
                ->all();
        }

        return $this->cache[$buildingId][$channel] ?? null;
    }

    /** Quên cache (dùng trong test khi cấu hình đổi giữa chừng). */
    public function flush(): void
    {
        $this->cache = [];
    }
}
