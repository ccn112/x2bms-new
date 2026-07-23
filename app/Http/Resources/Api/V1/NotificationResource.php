<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property \App\Models\Notification $resource
 * `is_read` được set kèm (transient) bởi controller trước khi resolve.
 * `kind` map từ cột `type` để khớp hợp đồng app (maintenance|fee|fire|event|community|important…).
 */
class NotificationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->id,
            'kind' => $this->type,
            'title' => $this->title,
            'summary' => $this->summary,
            'priority' => $this->priority,
            'is_pinned' => (bool) $this->is_pinned,
            'is_read' => (bool) ($this->is_read ?? false),
            'created_at' => ($this->published_at ?? $this->created_at)?->toIso8601String(),
        ];
    }
}
