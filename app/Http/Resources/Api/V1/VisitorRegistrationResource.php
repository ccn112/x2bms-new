<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property \App\Models\VisitorRegistration $resource
 * Đăng ký khách của cư dân (tab Tiện ích/An ninh — đăng ký khách C12).
 */
class VisitorRegistrationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->id,
            'code' => $this->code,
            'visitor_name' => $this->visitor_name,
            'visitor_phone' => $this->visitor_phone,
            'purpose' => $this->purpose,
            'vehicle_plate' => $this->vehicle_plate,
            'num_guests' => (int) $this->num_guests,
            'expected_at' => optional($this->expected_at)->toIso8601String(),
            'expected_leave_at' => optional($this->expected_leave_at)->toIso8601String(),
            'status' => $this->status,
            'created_at' => optional($this->created_at)->toIso8601String(),
        ];
    }
}
