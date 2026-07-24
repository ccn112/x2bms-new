<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property \App\Models\Event $resource
 * Sự kiện cộng đồng (tab Cộng đồng — CD-CM-04). `registered` = user đã đăng ký
 * (set từ controller qua $additional). `image_url` chưa có cột → null.
 */
class EventResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'location' => $this->location,
            'starts_at' => optional($this->starts_at)->toIso8601String(),
            'ends_at' => optional($this->ends_at)->toIso8601String(),
            'capacity' => $this->capacity === null ? null : (int) $this->capacity,
            'attendees' => (int) $this->registered_count,
            'registered' => (bool) ($this->registered ?? false),
            'image_url' => null,
        ];
    }
}
