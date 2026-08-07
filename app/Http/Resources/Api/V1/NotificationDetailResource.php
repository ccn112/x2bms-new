<?php

namespace App\Http\Resources\Api\V1;

use App\Support\DemoImage;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * @property \App\Models\Notification $resource
 * Chi tiết thông báo — FULL nội dung (`body`) + ảnh bìa. `is_read` set transient
 * bởi controller. `kind` map từ cột `type`. `cover_url` từ `cover_path`; nếu rỗng →
 * ảnh demo theo chủ đề (DemoImage).
 */
class NotificationDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $cover = $this->cover_path
            ? (str_starts_with($this->cover_path, 'http') ? $this->cover_path : Storage::disk('public')->url($this->cover_path))
            : DemoImage::url('announcement,building,notice', $this->id, 1200, 500);

        return [
            'id' => (string) $this->id,
            'kind' => $this->type,
            'category' => $this->category ?? $this->type,
            'subtype' => $this->subtype,
            'action_key' => $this->action_key,
            'entity' => ($this->entity_type === null && $this->entity_id === null)
                ? null
                : ['type' => $this->entity_type, 'id' => $this->entity_id === null ? null : (string) $this->entity_id],
            'requires_ack' => (bool) $this->requires_ack,
            'acknowledged' => (bool) ($this->is_acknowledged ?? false),
            'title' => $this->title,
            'summary' => $this->summary,
            'body' => $this->body,
            'cover_url' => $cover,
            'content_type' => $this->content_type instanceof \BackedEnum ? $this->content_type->value : ($this->content_type ?? 'announcement'),
            'content_meta' => $this->content_meta,
            'cta' => ($this->cta_label || $this->cta_target) ? ['label' => $this->cta_label, 'target' => $this->cta_target] : null,
            'allow_feedback' => (bool) $this->allow_feedback,
            'event' => $this->eventSummary(),
            'poll' => $this->pollSummary(),
            'expires_at' => $this->expires_at?->toIso8601String(),
            'priority' => $this->priority,
            'is_pinned' => (bool) $this->is_pinned,
            'is_read' => (bool) ($this->is_read ?? false),
            'comment_count' => (int) ($this->comments_count ?? 0),
            'created_at' => ($this->published_at ?? $this->created_at)?->toIso8601String(),
        ];
    }

    /** Tóm tắt sự kiện (content_type=event) — app dùng render + nút đăng ký (gọi API community events). */
    private function eventSummary(): ?array
    {
        $event = $this->resource->contentEvent();
        if (! $event) {
            return null;
        }

        return [
            'id' => (string) $event->id,
            'starts_at' => $event->starts_at?->toIso8601String(),
            'ends_at' => $event->ends_at?->toIso8601String(),
            'venue' => $event->location,
            'capacity' => $event->capacity,
            'registered' => (int) $event->registered_count,
            'waitlist' => (int) ($event->waitlist_count ?? 0),
            'registration_status' => $event->registration_status ?? 'open',
            'registration_deadline' => $event->registration_deadline?->toIso8601String(),
            'fee_amount' => (int) ($event->fee_amount ?? 0),
            'allow_guests' => (bool) $event->allow_guests,
            'qr_checkin' => (bool) $event->qr_checkin,
            'status' => $event->status,
        ];
    }

    /** Tóm tắt poll (content_type=poll) — app render + bình chọn (gọi API community polls). */
    private function pollSummary(): ?array
    {
        $poll = $this->resource->contentPoll();
        if (! $poll) {
            return null;
        }

        return [
            'id' => (string) $poll->id,
            'question' => $poll->question,
            'allow_multiple' => $poll->type === 'multiple',
            'anonymous' => (bool) $poll->anonymous,
            'vote_scope' => $poll->vote_scope ?? 'resident',
            'result_visibility' => $poll->result_visibility ?? 'after_vote',
            'closes_at' => $poll->closes_at?->toIso8601String(),
            'status' => $poll->status,
            'options' => $poll->options->map(fn ($o) => [
                'id' => (string) $o->id,
                'key' => $o->option_key,
                'label' => $o->label,
                'votes' => (int) $o->vote_count,
            ])->values()->all(),
        ];
    }
}
