<?php

declare(strict_types=1);

namespace App\Services\Notifications;

use App\Models\Notification;
use App\Models\Resident;
use App\Models\ResidentApartmentRelation;
use App\Models\User;

/**
 * ADR-002 — khi một thông báo BQL được PHÁT HÀNH, gửi các KÊNH NGOÀI đã chọn
 * (email/zalo/whatsapp/telegram/xspace) tới người nhận, ghi SỔ GỬI per-người qua
 * {@see MultiChannelNotifier} (email gửi thật; kênh khác là cổng chờ theo tòa).
 *
 * CHỈ gửi cho audience TARGETED (căn hộ / cư dân / user) — KHÔNG cho broadcast rộng
 * (all/project/building): email/SMS/Zalo không có 'topic', gửi rộng = tốn phí per-người
 * (ADR-001 mục 5). Push broadcast do {@see NotificationPushDispatcher} lo qua FCM topic.
 *
 * Tách khỏi Filament (rule: business ở service, không ở Resource/Page). Bọc try ở nơi
 * gọi: lỗi gửi kênh ngoài KHÔNG được làm việc phát hành thất bại.
 */
class NotificationExternalChannelDispatcher
{
    /** Kênh ngoài được gửi qua notifier (push có đường riêng; app là in-app). */
    public const EXTERNAL = ['email', 'zalo', 'whatsapp', 'telegram', 'xspace', 'sms', 'postal'];

    public function __construct(private readonly MultiChannelNotifier $notifier) {}

    /** @return int số (người × kênh) đã xử lý (gửi thật hoặc ghi cổng chờ). */
    public function dispatch(Notification $notification): int
    {
        if ($notification->status !== 'published') {
            return 0;
        }
        $notification->loadMissing(['audiences', 'channels']);

        $channels = $notification->channels->pluck('channel')
            ->intersect(self::EXTERNAL)->values()->all();
        if (empty($channels)) {
            return 0;
        }

        $userIds = $this->targetedUserIds($notification);
        if (empty($userIds)) {
            return 0;   // chỉ broadcast rộng → không auto-gửi kênh ngoài (tốn phí).
        }

        $title = (string) $notification->title;
        $body = $this->plainBody($notification);
        $morph = $notification->getMorphClass();

        $residentBuilding = Resident::withoutGlobalScopes()
            ->whereIn('user_id', $userIds)
            ->pluck('building_id', 'user_id');
        $users = User::query()->whereIn('id', $userIds)->get();

        $count = 0;
        foreach ($users as $user) {
            $this->notifier->notify(
                sourceType: $morph,
                sourceId: $notification->id,
                recipient: $user,
                channels: $channels,
                title: $title,
                body: $body,
                notificationId: $notification->id,
                buildingId: $residentBuilding[$user->id] ?? null,
            );
            $count += count($channels);
        }

        return $count;
    }

    /**
     * Chỉ audience TARGETED (apartment/resident/user) → danh sách user_id.
     * Broadcast (all/project/building) trả rỗng: không auto-gửi kênh ngoài.
     *
     * @return list<int>
     */
    private function targetedUserIds(Notification $notification): array
    {
        $ids = collect();
        foreach ($notification->audiences as $aud) {
            switch ($aud->scope_type) {
                case 'apartment':
                    $ids = $ids->merge(Resident::withoutGlobalScopes()
                        ->whereIn('id', ResidentApartmentRelation::query()
                            ->where('apartment_id', $aud->scope_id)->pluck('resident_id'))
                        ->whereNotNull('user_id')->pluck('user_id'));
                    break;
                case 'resident':
                    $ids = $ids->merge(Resident::withoutGlobalScopes()
                        ->whereKey($aud->scope_id)->whereNotNull('user_id')->pluck('user_id'));
                    break;
                case 'user':
                    $ids = $ids->merge([$aud->scope_id]);
                    break;
            }
        }

        return $ids->filter()->unique()->map(fn ($v) => (int) $v)->values()->all();
    }

    private function plainBody(Notification $n): string
    {
        $text = $n->summary ?: strip_tags((string) $n->body);
        $text = trim(preg_replace('/\s+/', ' ', $text) ?? '');

        return $text !== '' ? $text : (string) $n->title;
    }
}
