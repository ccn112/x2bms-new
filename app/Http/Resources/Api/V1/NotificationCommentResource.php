<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property \App\Models\NotificationComment $resource
 * `is_mine` được set kèm (transient) bởi controller theo user hiện tại.
 */
class NotificationCommentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $user = $this->user;

        return [
            'id' => (string) $this->id,
            'body' => $this->body,
            'author' => [
                'name' => $user?->name ?? $this->author_name ?? 'Cư dân',
                'avatar_url' => $user?->avatar_url,
            ],
            'is_mine' => (bool) ($this->is_mine ?? false),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
