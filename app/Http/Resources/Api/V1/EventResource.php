<?php

namespace App\Http\Resources\Api\V1;

use App\Support\DemoImage;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property \App\Models\Event $resource
 * Sự kiện cộng đồng (tab Cộng đồng — CD-CM-04). `registered` = user đã đăng ký
 * (set từ controller qua $additional). `image_url` = ảnh demo theo chủ đề (DemoImage).
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
            // Trạng thái đăng ký CỦA NGƯỜI ĐANG XEM: null (chưa đăng ký) |
            // registered | attended | cancelled. App cần phân biệt để chọn đúng
            // nút — `registered` (bool) không nói được "đã check-in".
            'registration_status' => $this->registration_status ?? null,
            // Còn chỗ hay không: app khoá nút đăng ký khi hết chỗ thay vì để cư
            // dân bấm rồi nhận lỗi. `capacity` null = không giới hạn.
            'is_full' => $this->capacity !== null
                && (int) $this->registered_count >= (int) $this->capacity,
            // Cư dân chỉ check-in được khi sự kiện ĐANG diễn ra — quyền do
            // server quyết, app chỉ vẽ (nguyên tắc handoff §5).
            'can_check_in' => (bool) ($this->can_check_in ?? false),
            'status' => $this->status,
            'image_url' => DemoImage::url('event,party,concert', $this->id),
        ];
    }
}
