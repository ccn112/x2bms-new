<?php

declare(strict_types=1);

namespace App\Services\Notifications;

use App\Models\User;
use App\Services\Resident\ResidentContextService;

/**
 * Tên các FCM topic mà thiết bị của một cư dân cần đăng ký, để broadcast BQL đi
 * bằng TOPIC (1 message cho cả toà/dự án) thay vì gửi lẻ từng máy (ADR-001, N1).
 *
 * Quy ước tên (khớp mapping ở NotificationPushDispatcher):
 *   tenant_{id} · project_{id} · building_{id}
 * (ký tự hợp lệ FCM topic: [a-zA-Z0-9-_.~%]). Cư dân nhiều căn → nhiều toà/dự án.
 */
class ResidentTopics
{
    public function __construct(private readonly ResidentContextService $context) {}

    /** @return list<string> */
    public function for(User $user, ?string $contextId = null): array
    {
        $topics = [];
        foreach ($this->context->tenantIds($user, $contextId) as $t) {
            $topics[] = 'tenant_'.$t;
        }
        foreach ($this->context->projectIds($user, $contextId) as $p) {
            $topics[] = 'project_'.$p;
        }
        foreach ($this->context->buildingIds($user, $contextId) as $b) {
            $topics[] = 'building_'.$b;
        }

        return array_values(array_unique(array_filter($topics)));
    }
}
