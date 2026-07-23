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
