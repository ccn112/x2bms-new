<?php

namespace App\Services\Resident;

use App\Models\Notification;
use App\Models\NotificationRead;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * Thông báo hiển thị cho CƯ DÂN (khác staff scopeVisibleTo). Cư dân thấy thông báo đã
 * PUBLISHED, chưa hết hạn, và audience nhắm tới: all | building của họ | căn hộ của họ.
 * Trạng thái đã đọc lưu ở notification_reads theo user_id. Dùng chung cho
 * /me/bootstrap (đếm unread) và /resident/notifications.
 */
class ResidentNotificationService
{
    public function __construct(private readonly ResidentContextService $context) {}

    /** Query các thông báo cư dân được xem (chưa sort). */
    /**
     * @param  ?string  $feed  A4 — tách nguồn: 'bql' = chỉ thông báo chính thống do
     *   quản lý soạn (màn "Thông báo BQL"); null/'all' = mọi nguồn kể cả item tương
     *   tác đẩy sau này (Hộp thư hợp nhất/chuông).
     */
    public function visibleQuery(User $user, ?string $contextId = null, ?string $feed = null): Builder
    {
        $apartmentIds = $this->context->apartmentIds($user, $contextId);
        $buildingIds = $this->context->buildingIds($user, $contextId);

        return Notification::query()
            ->where('status', 'published')
            ->when($feed === 'bql', fn (Builder $q) => $q->where('source', 'bql'))
            ->where(fn (Builder $q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->whereHas('audiences', function (Builder $a) use ($apartmentIds, $buildingIds): void {
                $a->where(function (Builder $inner) use ($apartmentIds, $buildingIds): void {
                    $inner->where('scope_type', 'all');
                    if (! empty($buildingIds)) {
                        $inner->orWhere(fn (Builder $b) => $b->where('scope_type', 'building')->whereIn('scope_id', $buildingIds));
                    }
                    if (! empty($apartmentIds)) {
                        $inner->orWhere(fn (Builder $b) => $b->where('scope_type', 'apartment')->whereIn('scope_id', $apartmentIds));
                    }
                });
            });
    }

    /** Số thông báo chưa đọc của user. */
    public function unreadCount(User $user, ?string $contextId = null): int
    {
        return (int) $this->visibleQuery($user, $contextId)
            ->whereDoesntHave('reads', fn (Builder $r) => $r->where('user_id', $user->id)->whereNotNull('read_at'))
            ->count();
    }

    /**
     * Tổng unread + breakdown theo nhóm (category, fallback type) cho chuông hộp
     * thư hợp nhất. `unread_bql` tách riêng để badge quick-action "Thông báo BQL".
     *
     * @return array{unread_total:int, unread_by_category:array<string,int>, unread_bql:int}
     */
    public function summary(User $user, ?string $contextId = null): array
    {
        $base = $this->visibleQuery($user, $contextId)
            ->whereDoesntHave('reads', fn (Builder $r) => $r->where('user_id', $user->id)->whereNotNull('read_at'));

        $total = (int) (clone $base)->count();

        $rows = (clone $base)
            ->selectRaw('COALESCE(category, type) as cat, COUNT(*) as c')
            ->groupBy('cat')
            ->pluck('c', 'cat');

        $byCategory = [];
        foreach ($rows as $cat => $c) {
            $byCategory[(string) $cat] = (int) $c;
        }

        // A4 — badge "Thông báo BQL" = mọi thông báo CHÍNH THỐNG chưa đọc (source=bql),
        // gồm cả Phí/Bảo trì/PCCC, không chỉ riêng nhóm announcement.
        $unreadBql = (int) (clone $base)->where('source', 'bql')->count();

        return [
            'unread_total' => $total,
            'unread_by_category' => $byCategory,
            'unread_bql' => $unreadBql,
        ];
    }

    /**
     * Đánh dấu đã đọc TẤT CẢ thông báo trong phạm vi user (tùy chọn theo nhóm).
     * Trả số bản ghi vừa được đánh dấu.
     */
    public function markAllRead(User $user, ?string $contextId = null, ?string $category = null): int
    {
        $q = $this->visibleQuery($user, $contextId)
            ->whereDoesntHave('reads', fn (Builder $r) => $r->where('user_id', $user->id)->whereNotNull('read_at'));
        if ($category !== null && $category !== '') {
            $q->where(fn (Builder $c) => $c->where('category', $category)->orWhere('type', $category));
        }

        $ids = $q->pluck('id');
        $now = now();
        foreach ($ids as $id) {
            NotificationRead::query()->updateOrCreate(
                ['notification_id' => (int) $id, 'user_id' => $user->id],
                ['read_at' => $now],
            );
        }

        return $ids->count();
    }

    /**
     * Đánh dấu đã đọc (idempotent). Trả false nếu thông báo không thuộc phạm vi user.
     */
    public function markRead(User $user, int $notificationId, ?string $contextId = null): bool
    {
        $visible = $this->visibleQuery($user, $contextId)->whereKey($notificationId)->exists();
        if (! $visible) {
            return false;
        }

        NotificationRead::query()->updateOrCreate(
            ['notification_id' => $notificationId, 'user_id' => $user->id],
            ['read_at' => now()],
        );

        return true;
    }

    /**
     * A3 — Cư dân XÁC NHẬN đã tiếp nhận thông báo khẩn (`requires_ack`). Ack bao
     * hàm cả đã đọc. GIỮ NGUYÊN thời điểm ack đầu tiên (idempotent — bấm lại không
     * dời mốc). Chỉ nhận với thông báo yêu cầu ack; còn lại 422 để app không lạm dụng.
     *
     * @return 'ok'|'not_found'|'ack_not_required'
     */
    public function acknowledge(User $user, int $notificationId, ?string $contextId = null): string
    {
        $notification = $this->visibleQuery($user, $contextId)->whereKey($notificationId)->first();
        if ($notification === null) {
            return 'not_found';
        }
        if (! $notification->requires_ack) {
            return 'ack_not_required';
        }

        $read = NotificationRead::query()->firstOrNew([
            'notification_id' => $notificationId, 'user_id' => $user->id,
        ]);
        $read->read_at ??= now();
        $read->acknowledged_at ??= now();
        $read->save();

        return 'ok';
    }
}
