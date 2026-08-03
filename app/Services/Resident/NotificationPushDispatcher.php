<?php

namespace App\Services\Resident;

use App\Enums\NotificationChannel as ChannelEnum;
use App\Models\Building;
use App\Models\Notification;
use App\Models\NotificationDeliveryLog;
use App\Models\Resident;
use App\Models\ResidentApartmentRelation;
use App\Models\User;
use App\Services\Push\PushService;
use Illuminate\Support\Facades\DB;

/**
 * Đẩy PUSH khi một thông báo BQL được PHÁT HÀNH: resolve đối tượng nhận
 * (audiences → cư dân) rồi gửi FCM tới thiết bị của họ, tôn trọng tuỳ chọn KÊNH
 * (PushService bỏ qua ai đã tắt kênh; kênh khẩn cấp luôn nhận).
 *
 * Chỉ đẩy khi thông báo có chọn kênh 'push' (BQL không chọn push thì không gửi).
 *
 * A2 (bắc cầu AR-05) — GHI Ý ĐỊNH gửi TRƯỚC KHI bắn FCM: mỗi người nhận một dòng
 * `notification_delivery_logs` (channel=push) trong CÙNG transaction, rồi mới gửi
 * và cập nhật trạng thái từng dòng. Nhờ vậy:
 *   - idempotent: replay bỏ qua dòng đã `sent` (unique notification+user+channel),
 *   - gửi lại được: dòng `failed`/`queued` còn nguyên để thử lại,
 *   - đối soát được: mọi người nhận dự kiến đều có vết + lý do (`suppressed`/`failed`).
 * Đây là bước tối thiểu để không thêm dispatcher inline "bắn rồi quên" nữa (GAP-03).
 */
class NotificationPushDispatcher
{
    public function __construct(private readonly PushService $push) {}

    /**
     * @param  bool  $resend  Gửi LẠI cả dòng đã `sent` (chỉ dùng cho công cụ demo —
     *                        production để mặc định false để không đẩy trùng).
     * @return int Tổng số thiết bị nhận thành công LƯỢT NÀY.
     */
    public function dispatch(Notification $notification, bool $resend = false): int
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

        // N1 (ADR-001): tách BROADCAST (all/project/building → FCM topic, 1 message)
        // khỏi TARGETED (apartment/resident/user → per-user, có sổ giao nhận A2).
        [$topics, $userIds] = $this->resolveTargets($notification);
        if (empty($topics) && empty($userIds)) {
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

        // BROADCAST: gửi MỘT message tới mỗi topic — KHÔNG nhân theo người nhận,
        // KHÔNG ghi delivery-log per-người (audit topic-level = N3). Lưu ý: topic
        // KHÔNG lọc được tuỳ chọn kênh per-người (BQL đã chọn push là gửi); mute
        // per-người chỉ áp cho targeted. Kênh khẩn cấp: mọi người luôn nhận.
        foreach ($topics as $topic) {
            $ok = $this->push->toTopic($topic, $title, $body, $data, $image);
            // N3: audit topic-level (1 dòng/topic, recipient null) — không nhân per-người.
            NotificationDeliveryLog::updateOrCreate(
                ['notification_id' => $notification->id, 'channel' => 'push', 'topic' => $topic],
                [
                    'source_type' => $notification->getMorphClass(),
                    'source_id' => $notification->id,
                    'status' => $ok ? 'sent' : 'failed',
                    'queued_at' => now(),
                    'sent_at' => $ok ? now() : null,
                    'error' => $ok ? null : 'topic_send_failed',
                ],
            );
        }

        if (empty($userIds)) {
            return 0;   // thuần broadcast — không có phần per-user để đếm/ghi.
        }

        // TARGETED — GHI Ý ĐỊNH (durable, idempotent) một dòng 'queued'/người trong
        // transaction; firstOrCreate + unique index nên replay không nhân đôi.
        DB::transaction(function () use ($notification, $userIds, $resend) {
            foreach ($userIds as $uid) {
                $log = NotificationDeliveryLog::firstOrCreate(
                    ['notification_id' => $notification->id, 'user_id' => $uid, 'channel' => 'push'],
                    [
                        'source_type' => $notification->getMorphClass(),
                        'source_id' => $notification->id,
                        'status' => 'queued',
                        'queued_at' => now(),
                    ],
                );
                // Công cụ demo: dòng cũ đã 'sent' → đưa về 'queued' để bắn lại.
                if ($resend && ! $log->wasRecentlyCreated && $log->status === 'sent') {
                    $log->forceFill(['status' => 'queued', 'error' => null, 'sent_at' => null])->save();
                }
            }
            $notification->forceFill(['recipient_count' => count($userIds)])->save();
        });

        // GỬI per-user: chỉ dòng CHƯA gửi thành công. Bước mạng NGOÀI transaction.
        $rows = NotificationDeliveryLog::query()
            ->where('notification_id', $notification->id)
            ->where('channel', 'push')
            ->whereIn('status', ['queued', 'failed'])
            ->get();
        $users = User::query()->whereIn('id', $rows->pluck('user_id')->filter())->get()->keyBy('id');

        $sent = 0;
        foreach ($rows as $row) {
            $user = $users->get($row->user_id);
            if ($user === null) {
                $row->forceFill(['status' => 'failed', 'error' => 'user_missing'])->save();

                continue;
            }
            // Cư dân tắt kênh này → không phải lỗi gửi; ghi 'suppressed' để đối soát,
            // KHÔNG gọi FCM. Kênh khẩn cấp userAllows luôn true.
            if (! $this->push->userAllows($user, $channel)) {
                $row->forceFill(['status' => 'suppressed', 'error' => 'channel_disabled', 'sent_at' => now()])->save();

                continue;
            }

            $count = $this->push->toUser($user, $title, $body, $data, $channel, $image);
            if ($count > 0) {
                $row->forceFill(['status' => 'sent', 'error' => null, 'sent_at' => now()])->save();
                $sent += $count;
            } else {
                // Bật kênh nhưng không thiết bị nào nhận (chưa đăng nhập app / token chết).
                $row->forceFill(['status' => 'failed', 'error' => 'no_active_token'])->save();
            }
        }

        return $sent;
    }

    /**
     * Phân loại audiences: BROADCAST (all/project/building) → tên FCM topic;
     * TARGETED (apartment/resident/user) → danh sách user_id cụ thể.
     *
     * @return array{0: list<string>, 1: list<int>}  [topics, userIds]
     */
    private function resolveTargets(Notification $notification): array
    {
        $topics = [];
        $ids = collect();

        foreach ($notification->audiences as $aud) {
            switch ($aud->scope_type) {
                case 'all':
                    // 'all' theo owner: dự án → project topic; nếu không → tenant topic.
                    $topics[] = $notification->project_id
                        ? 'project_'.$notification->project_id
                        : ($notification->tenant_id ? 'tenant_'.$notification->tenant_id : null);
                    break;
                case 'project':
                    $topics[] = 'project_'.$aud->scope_id;
                    break;
                case 'building':
                    $topics[] = 'building_'.$aud->scope_id;
                    break;
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

        return [
            array_values(array_unique(array_filter($topics))),
            $ids->filter()->unique()->map(fn ($v) => (int) $v)->values()->all(),
        ];
    }

    /** Nội dung push gọn (bỏ HTML, gộp khoảng trắng, cắt 160 ký tự). */
    private function plainBody(Notification $n): string
    {
        $text = $n->summary ?: strip_tags((string) $n->body);
        $text = trim(preg_replace('/\s+/', ' ', $text) ?? '');

        return mb_substr($text, 0, 160);
    }
}
