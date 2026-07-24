<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property \App\Models\AmenitySlot $resource
 * Khung giờ của tiện ích. `day_of_week` null = áp dụng mọi ngày.
 */
class AmenitySlotResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->id,
            'day_of_week' => $this->day_of_week === null ? null : (int) $this->day_of_week,
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'capacity' => (int) $this->capacity,
            'status' => $this->status,
        ];
    }
}
