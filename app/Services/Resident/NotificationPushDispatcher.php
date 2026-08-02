<?php

namespace App\Services\Resident;

use App\Enums\NotificationChannel as ChannelEnum;
use App\Models\Building;
use App\Models\Notification;
use App\Models\Resident;
use App\Models\ResidentApartmentRelation;
use App\Models\User;
use App\Services\Push\PushService;

/**
 * Đẩy PUSH khi một thông báo BQL được PHÁT HÀNH: resolve đối tượng nhận
 * (audiences → cư dân) rồi gửi FCM tới thiết bị của họ, tôn trọng tuỳ chọn KÊNH
 * (PushService bỏ qua ai đã tắt kênh; kênh khẩn cấp luôn nhận).
 *
 * Chỉ đẩy khi thông báo có chọn kênh 'push' (BQL không chọn push thì không gửi).
 */
class NotificationPushDispatcher
{
    public function __construct(private readonly PushService $push) {}

    /** Trả về tổng số thiết bị nhận thành công. */
    public function dispatch(Notification $notification): int
    {
        if ($notification->status !== 'published') {
            return 0;
        }
        $notification->loadMissing(['audiences', 'channels']);

        // Tôn trọng lựa chọn kênh của BQL: chỉ đẩy khi có 'push'.
        if (! $notification->channels->pluck('channel')->contains('push')) {
            return 0;
        }

        $channel = ChannelEnum::tryFrom((string) $notification->type) ?? ChannelEnum::Announcement;
        $userIds = $this->targetUserIds($notification);
        if (empty($userIds)) {
            return 0;
        }

        $title = (string) $notification->title;
        $body = $this->plainBody($notification);
        $data = [
            'type' => 'notification',
            'notification_id' => (string) $notification->id,
            'category' => (string) $notification->type,
        ];
        // Ảnh thông báo = ảnh bìa của thông báo BQL (nếu có) → BigPicture; icon
        // app vẫn hiện nhỏ. URL tương đối/rỗng thì PushService tự bỏ qua.
        $image = $notification->cover_path
            ? \Illuminate\Support\Facades\Storage::disk('public')->url($notification->cover_path)
            : null;

        $sent = 0;
        foreach (User::query()->whereIn('id', $userIds)->get() as $user) {
            $sent += $this->push->toUser($user, $title, $body, $data, $channel, $image);
        }

        return $sent;
    }

    /** Resolve audiences → danh sách user_id cư dân đích. */
    private function targetUserIds(Notification $notification): array
    {
        $ids = collect();
        foreach ($notification->audiences as $aud) {
            $ids = $ids->merge(match ($aud->scope_type) {
                'all' => Resident::withoutGlobalScopes()
                    ->where('tenant_id', $notification->tenant_id)
                    ->whereNotNull('user_id')->pluck('user_id'),
                'building' => Resident::withoutGlobalScopes()
                    ->where('building_id', $aud->scope_id)
                    ->whereNotNull('user_id')->pluck('user_id'),
                'project' => Resident::withoutGlobalScopes()
                    ->whereIn('building_id', Building::withoutGlobalScopes()
                        ->where('project_id', $aud->scope_id)->pluck('id'))
                    ->whereNotNull('user_id')->pluck('user_id'),
                'apartment' => Resident::withoutGlobalScopes()
                    ->whereIn('id', ResidentApartmentRelation::query()
                        ->where('apartment_id', $aud->scope_id)->pluck('resident_id'))
                    ->whereNotNull('user_id')->pluck('user_id'),
                'resident' => Resident::withoutGlobalScopes()
                    ->whereKey($aud->scope_id)
                    ->whereNotNull('user_id')->pluck('user_id'),
                'user' => collect([$aud->scope_id]),
                default => collect(),
            });
        }

        return $ids->filter()->unique()->map(fn ($v) => (int) $v)->values()->all();
    }

    /** Nội dung push gọn (bỏ HTML, gộp khoảng trắng, cắt 160 ký tự). */
    private function plainBody(Notification $n): string
    {
        $text = $n->summary ?: strip_tags((string) $n->body);
        $text = trim(preg_replace('/\s+/', ' ', $text) ?? '');

        return mb_substr($text, 0, 160);
    }
}
