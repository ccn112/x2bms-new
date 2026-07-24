<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * @property \App\Models\Amenity $resource
 * Tiện ích nội khu (gym/hồ bơi/BBQ…). `slots` chỉ kèm khi controller load quan hệ.
 */
class AmenityResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $image = $this->image_path
            ? (str_starts_with($this->image_path, 'http') ? $this->image_path : Storage::disk('public')->url($this->image_path))
            : null;

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
}
