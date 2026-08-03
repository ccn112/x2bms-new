<?php

declare(strict_types=1);

namespace App\Services\Notifications;

use App\Models\ActivityNotification;
use App\Models\ResidentBellState;
use App\Models\User;
use App\Services\Resident\ResidentNotificationService;
use Illuminate\Support\Carbon;

/**
 * Đọc CHUÔNG (ADR-001): HỢP NHẤT hai nguồn LÚC ĐỌC —
 *   (A) broadcast BQL áp cho tôi (`notifications` + audience match, fan-out-on-READ),
 *   (B) activity targeted của tôi (`activity_notifications`).
 * Không nguồn nào bị nhân theo dân số. Chưa-đọc broadcast suy từ mốc `bell_seen_at`
 * (coarse) — KHÔNG ghi dòng "chưa đọc" per người.
 *
 * Phân trang N0: cửa sổ thời gian có giới hạn + trộn theo thời gian giảm dần, cursor
 * = mốc thời gian item cuối. Đủ cho reference slice; tối ưu keyset đa nguồn để slice sau.
 */
class BellReader
{
    public function __construct(private readonly ResidentNotificationService $announcements) {}

    /**
     * @return array{items: list<array<string,mixed>>, next_cursor: ?string, unread: int}
     */
    public function render(User $user, ?string $contextId, ?string $before = null, int $limit = 30): array
    {
        $seenAt = $this->seenAt($user);
        $beforeAt = ($before !== null && $before !== '') ? Carbon::parse($before) : null;

        // (A) broadcast áp cho tôi — audience match qua service sẵn có (không feed = mọi nguồn bql).
        $annQuery = $this->announcements->visibleQuery($user, $contextId)
            ->when($beforeAt !== null, fn ($q) => $q->where('published_at', '<', $beforeAt))
            ->orderByDesc('published_at')->orderByDesc('id')
            ->limit($limit + 1);
        $announcements = $annQuery->get()->map(fn ($n) => $this->fromAnnouncement($n, $seenAt));

        // (B) activity targeted của tôi.
        $actQuery = ActivityNotification::query()
            ->where('recipient_user_id', $user->id)
            ->when($beforeAt !== null, fn ($q) => $q->where('created_at', '<', $beforeAt))
            ->orderByDesc('created_at')->orderByDesc('id')
            ->limit($limit + 1);
        $activities = $actQuery->get()->map(fn ($a) => $this->fromActivity($a));

        // Trộn theo thời gian giảm dần, cắt `limit`, dựng cursor.
        $merged = $announcements->concat($activities)
            ->sortByDesc(fn ($i) => $i['_ts'])
            ->values();

        $hasMore = $merged->count() > $limit;
        $page = $merged->take($limit);
        $nextCursor = $hasMore ? optional($page->last()['_ts'] ?? null)?->toIso8601String() : null;

        $items = $page->map(function ($i) {
            unset($i['_ts']);

            return $i;
        })->all();

        return [
            'items' => $items,
            'next_cursor' => $nextCursor,
            'unread' => $this->unreadCount($user, $contextId, $seenAt),
        ];
    }

    /** Chưa đọc = broadcast published sau mốc seen + activity chưa đọc. */
    public function unreadCount(User $user, ?string $contextId, ?Carbon $seenAt = null): int
    {
        $seenAt ??= $this->seenAt($user);

        $ann = $this->announcements->visibleQuery($user, $contextId)
            ->when($seenAt !== null, fn ($q) => $q->where('published_at', '>', $seenAt))
            ->count();

        $act = ActivityNotification::query()
            ->where('recipient_user_id', $user->id)
            ->whereNull('read_at')
            ->count();

        return $ann + $act;
    }

    /** Bump mốc đã-thấy chuông → về 0 chưa-đọc phía broadcast. */
    public function markSeen(User $user): void
    {
        ResidentBellState::query()->updateOrCreate(
            ['user_id' => $user->id],
            ['bell_seen_at' => now()],
        );
    }

    private function seenAt(User $user): ?Carbon
    {
        $v = ResidentBellState::query()->where('user_id', $user->id)->value('bell_seen_at');

        return $v ? Carbon::parse($v) : null;
    }

    private function fromAnnouncement($n, ?Carbon $seenAt): array
    {
        $ts = $n->published_at ?? $n->created_at;

        return [
            '_ts' => $ts,
            'type' => 'announcement',
            'id' => (string) $n->id,
            'kind' => $n->type,
            'category' => $n->category ?? $n->type,
            'title' => $n->title,
            'summary' => $n->summary,
            'image_url' => $n->cover_path
                ? (str_starts_with((string) $n->cover_path, 'http')
                    ? $n->cover_path
                    : \Illuminate\Support\Facades\Storage::disk('public')->url($n->cover_path))
                : null,
            'action_key' => $n->action_key,
            'entity' => ($n->entity_type === null && $n->entity_id === null)
                ? null : ['type' => $n->entity_type, 'id' => $n->entity_id === null ? null : (string) $n->entity_id],
            'requires_ack' => (bool) $n->requires_ack,
            // Đã đọc broadcast: coarse theo mốc seen (ADR-001 mục 1).
            'is_read' => $seenAt !== null && $ts !== null && $ts->lte($seenAt),
            'created_at' => $ts?->toIso8601String(),
        ];
    }

    private function fromActivity(ActivityNotification $a): array
    {
        return [
            '_ts' => $a->created_at,
            'type' => 'activity',
            'id' => (string) $a->id,
            'kind' => $a->kind,
            'category' => $a->subtype ?? $a->kind,
            'title' => $a->title,
            'summary' => $a->body,
            'image_url' => $a->image_url,
            'action_key' => $a->action_key,
            'entity' => ($a->entity_type === null && $a->entity_id === null)
                ? null : ['type' => $a->entity_type, 'id' => $a->entity_id === null ? null : (string) $a->entity_id],
            'requires_ack' => false,
            'is_read' => $a->read_at !== null,
            'coalesce_count' => $a->coalesce_count,
            'created_at' => optional($a->created_at)->toIso8601String(),
        ];
    }
}
