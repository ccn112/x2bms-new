<?php

namespace App\Http\Resources\Api\V1;

use App\Support\DemoImage;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * @property \App\Models\Amenity $resource
 * Tiện ích nội khu (gym/hồ bơi/BBQ…). `slots` chỉ kèm khi controller load quan hệ.
 * `image_url` từ `image_path`; nếu rỗng → ảnh demo theo type/name (DemoImage).
 */
class AmenityResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $image = $this->image_path
            ? (str_starts_with($this->image_path, 'http') ? $this->image_path : Storage::disk('public')->url($this->image_path))
            : DemoImage::url($this->demoKeywords(), $this->id);

        return [
            'id' => (string) $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'type' => $this->type,
            'description' => $this->description,
            'capacity' => (int) $this->capacity,
            'open_time' => $this->open_time,
            'close_time' => $this->close_time,
            'booking_unit' => $this->booking_unit,
            'price' => $this->price === null ? null : (string) $this->price,
            'requires_approval' => (bool) $this->requires_approval,
            'image_url' => $image,
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
