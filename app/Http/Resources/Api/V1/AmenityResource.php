<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Amenity;
use App\Support\DemoImage;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * @property Amenity $resource
 *                             Tiện ích nội khu (gym/hồ bơi/BBQ…). `slots` chỉ kèm khi controller load quan hệ.
 *                             `image_url` từ `image_path`; nếu rỗng → ảnh demo theo type/name (DemoImage).
 */
class AmenityResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $image = $this->image_path
            ? (str_starts_with($this->image_path, 'http') ? $this->image_path : Storage::disk('public')->url($this->image_path))
            : DemoImage::url($this->demoKeywords(), $this->id);

        $capacity = (int) $this->capacity;
        $bookingsTodayTotal = (int) ($this->bookings_today_total ?? 0);

        return [
            'id' => (string) $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'type' => $this->type,
            'description' => $this->description,
            'capacity' => $capacity,
            'open_time' => $this->open_time,
            'close_time' => $this->close_time,
            'booking_unit' => $this->booking_unit,
            'price' => $this->price === null ? null : (string) $this->price,
            'requires_approval' => (bool) $this->requires_approval,
            'image_url' => $image,
            // Dải thống kê trên thẻ tiện ích (chốt 30/07 lần 2) — chỉ có khi
            // controller đã `withCount`/`loadCount` (AmenityController::statsCounters).
            // Thiếu thì mặc định 0/false thay vì throw, để endpoint khác lỡ quên
            // eager-load vẫn không vỡ, chỉ hiện số 0 (an toàn hơn 500).
            'bookings_today_total' => $bookingsTodayTotal,
            'my_bookings_total' => (int) ($this->my_bookings_total ?? 0),
            'my_bookings_today' => (int) ($this->my_bookings_today ?? 0),
            // capacity = 0 coi như "không giới hạn" (mặc định cột là 1 nên hiếm
            // khi gặp, nhưng phòng khi có tiện ích cấu hình 0 nghĩa là tự do ra
            // vào) — chỉ so khi có hạn mức thật (> 0).
            'is_full_today' => $capacity > 0 && $bookingsTodayTotal >= $capacity,
            'slots' => $this->when(
                $this->relationLoaded('slots'),
                fn () => AmenitySlotResource::collection($this->slots)->resolve($request)
            ),
        ];
    }

    /** Chủ đề ảnh demo theo loại/tên tiện ích. */
    private function demoKeywords(): string
    {
        $haystack = mb_strtolower(trim(($this->type ?? '').' '.($this->name ?? '')));

        return match (true) {
            str_contains($haystack, 'pool') || str_contains($haystack, 'bể') || str_contains($haystack, 'hồ') => 'swimming,pool',
            str_contains($haystack, 'gym') => 'gym,fitness',
            str_contains($haystack, 'bbq') => 'barbecue,grill',
            default => 'amenity,facility',
        };
    }
}
