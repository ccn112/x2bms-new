<?php

namespace App\Services\Notifications;

use App\Models\Notification;

/**
 * Ước tính đối tượng nhận (spec 07 §3) — cho wizard live (count + coverage), có thể
 * cache ngắn ở call-site. Ranh giới service tách khỏi resolve (ghi snapshot).
 */
class AudienceEstimator
{
    public function __construct(private readonly AudienceResolver $resolver) {}

    /**
     * @return array{residents:int,apartments:int}
     */
    public function estimate(Notification $notification, ?array $rule = null): array
    {
        return $this->resolver->estimate($notification, $rule);
    }
}
