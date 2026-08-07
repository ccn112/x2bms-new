<?php

namespace App\Services\Notifications;

use App\Models\Notification;
use App\Models\NotificationSnapshot;

/**
 * Chốt snapshot BẤT BIẾN của chiến dịch (spec 06 §7). Gọi khi gửi duyệt / approved /
 * publish. Trang chi tiết chiến dịch đã gửi ĐỌC snapshot, không render lại từ nội dung
 * hiện tại. `hash` phát hiện thay đổi nội dung/audience/kênh sau khi duyệt → invalidate.
 */
class NotificationSnapshotService
{
    /** Tạo snapshot phiên bản mới; cập nhật snapshot_version + hash trên notification. */
    public function capture(Notification $n, ?int $actorId = null): NotificationSnapshot
    {
        $n->loadMissing(['channels', 'approvals.steps']);

        $content = $this->contentPayload($n);
        $audience = $this->audiencePayload($n);
        $channels = $this->channelsPayload($n);
        $hash = $this->hashOf($content, $audience, $channels);

        $version = ((int) $n->snapshot_version) + 1;

        $snapshot = NotificationSnapshot::create([
            'notification_id' => $n->id,
            'version' => $version,
            'hash' => $hash,
            'content' => $content,
            'audience' => $audience,
            'channels' => $channels,
            'approval' => $this->approvalPayload($n),
            'cost_estimate' => (float) $n->cost_estimate,
            'created_by_id' => $actorId,
            'created_at' => now(),
        ]);

        $n->forceFill(['snapshot_version' => $version, 'audience_snapshot_hash' => $hash])->save();

        return $snapshot;
    }

    /** Nội dung/audience/kênh hiện tại có khác snapshot mới nhất không (→ reapprove). */
    public function divergesFromLatest(Notification $n): bool
    {
        $latest = $n->latestSnapshot()->first();
        if (! $latest) {
            return true;
        }
        $current = $this->hashOf($this->contentPayload($n), $this->audiencePayload($n), $this->channelsPayload($n));

        return $current !== $latest->hash;
    }

    /** @return array<string,mixed> */
    private function contentPayload(Notification $n): array
    {
        return [
            'content_type' => $n->content_type?->value,
            'title' => $n->title,
            'summary' => $n->summary,
            'body' => $n->body,
            'priority' => $n->priority,
            'category' => $n->category,
            'cover_path' => $n->cover_path,
            'cta_label' => $n->cta_label,
            'cta_target' => $n->cta_target,
            'require_read_ack' => (bool) $n->requires_ack,
            'allow_feedback' => (bool) $n->allow_feedback,
            'pin_in_app' => (bool) $n->is_pinned,
            'expires_at' => optional($n->expires_at)->toIso8601String(),
            'content_meta' => $n->content_meta,
            'entity' => $n->entity_type ? ['type' => $n->entity_type, 'id' => $n->entity_id] : null,
        ];
    }

    /** @return array<string,mixed> */
    private function audiencePayload(Notification $n): array
    {
        return [
            'rule' => $n->audience_rule,
            'recipient_count' => (int) $n->recipient_count,
            'locked' => (bool) $n->audience_locked,
        ];
    }

    /** @return array<int,array<string,mixed>> */
    private function channelsPayload(Notification $n): array
    {
        return $n->channels->map(fn ($c) => [
            'channel' => $c->channel,
            'enabled' => (bool) $c->enabled,
            'config' => $c->config,
        ])->values()->all();
    }

    /** @return array<string,mixed>|null */
    private function approvalPayload(Notification $n): ?array
    {
        $approval = $n->approvals->sortByDesc('id')->first();
        if (! $approval) {
            return null;
        }

        return [
            'route_key' => $approval->route_key,
            'status' => $approval->status instanceof \BackedEnum ? $approval->status->value : $approval->status,
            'steps' => $approval->steps->map(fn ($s) => [
                'step_no' => $s->step_no,
                'role' => $s->role,
                'status' => $s->status instanceof \BackedEnum ? $s->status->value : $s->status,
                'actor_id' => $s->actor_id,
                'acted_at' => optional($s->acted_at)->toIso8601String(),
            ])->values()->all(),
        ];
    }

    private function hashOf(array $content, array $audience, array $channels): string
    {
        return hash('sha256', json_encode([
            'content' => $content, 'audience' => $audience, 'channels' => $channels,
        ], JSON_UNESCAPED_UNICODE));
    }
}
