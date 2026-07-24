<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property \App\Models\AmenityBooking $resource
 * Lượt đặt tiện ích của cư dân. Tiền là chuỗi decimal.
 */
class AmenityBookingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->id,
            'code' => $this->code,
            'amenity' => $this->whenLoaded('amenity', fn () => [
                'id' => (string) $this->amenity->id,
                'name' => $this->amenity->name,
            ]),
            'booking_date' => optional($this->booking_date)->toDateString(),
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'party_size' => (int) $this->party_size,
            'status' => $this->status,
            'price' => $this->price === null ? null : (string) $this->price,
            'note' => $this->note,
        ];
    }
}
