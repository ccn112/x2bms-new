<?php

namespace App\Http\Resources\Api\V1;

use App\Support\DemoImage;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * @property \App\Models\Notification $resource
 * `is_read` được set kèm (transient) bởi controller trước khi resolve.
 * `kind` map từ cột `type` để khớp hợp đồng app (maintenance|fee|fire|event|community|important…).
 * `cover_url` từ `cover_path`; nếu rỗng → ảnh demo theo chủ đề (DemoImage).
 */
class NotificationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $cover = $this->cover_path
            ? (str_starts_with($this->cover_path, 'http') ? $this->cover_path : Storage::disk('public')->url($this->cover_path))
            : DemoImage::url('announcement,building,notice', $this->id, 1200, 500);

        return [
            'id' => (string) $this->id,
            'kind' => $this->type,
            // BQL-NOTI: loại nội dung (announcement|news|event|poll) — additive, app route theo đây.
            'content_type' => $this->content_type instanceof \BackedEnum ? $this->content_type->value : ($this->content_type ?? 'announcement'),
            // Trục taxonomy hộp thư hợp nhất (fallback type khi chưa backfill).
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
            'cover_url' => $cover,
            'cta' => ($this->cta_label || $this->cta_target) ? ['label' => $this->cta_label, 'target' => $this->cta_target] : null,
            'allow_feedback' => (bool) $this->allow_feedback,
            'priority' => $this->priority,
            'is_pinned' => (bool) $this->is_pinned,
            'is_read' => (bool) ($this->is_read ?? false),
            'comment_count' => (int) ($this->comments_count ?? 0),
            'created_at' => ($this->published_at ?? $this->created_at)?->toIso8601String(),
        ];
    }
}
