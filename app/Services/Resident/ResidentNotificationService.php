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
    public function visibleQuery(User $user, ?string $contextId = null): Builder
    {
        $apartmentIds = $this->context->apartmentIds($user, $contextId);
        $buildingIds = $this->context->buildingIds($user, $contextId);

        return Notification::query()
            ->where('status', 'published')
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

        return [
            'unread_total' => $total,
            'unread_by_category' => $byCategory,
            'unread_bql' => ($byCategory['announcement'] ?? 0) + ($byCategory['bql'] ?? 0),
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
}
