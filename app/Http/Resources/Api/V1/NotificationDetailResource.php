<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * @property \App\Models\Notification $resource
 * Chi tiết thông báo — FULL nội dung (`body`) + ảnh bìa. `is_read` set transient
 * bởi controller. `kind` map từ cột `type`.
 */
class NotificationDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $cover = $this->cover_path
            ? (str_starts_with($this->cover_path, 'http') ? $this->cover_path : Storage::disk('public')->url($this->cover_path))
            : null;

        return [
            'id' => (string) $this->id,
            'kind' => $this->type,
            'title' => $this->title,
            'summary' => $this->summary,
            'body' => $this->body,
            'cover_url' => $cover,
            'priority' => $this->priority,
            'is_pinned' => (bool) $this->is_pinned,
            'is_read' => (bool) ($this->is_read ?? false),
            'created_at' => ($this->published_at ?? $this->created_at)?->toIso8601String(),
        ];
    }
}
