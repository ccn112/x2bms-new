<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property \App\Models\EmergencyAlert $resource
 *
 * Cảnh báo khẩn cấp cư dân (CD-HOME-04). `severity` info|warning|critical và
 * `type` fire|flood|security|health|other là hợp đồng với app — app chọn màu
 * và icon theo hai trường này, KHÔNG tự đoán từ tiêu đề.
 *
 * `contacts` chỉ đi kèm màn chi tiết (controller nạp sẵn), danh sách rút gọn
 * bỏ trống để khỏi query thừa.
 */
class EmergencyAlertResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->id,
            'code' => $this->code,
            'type' => $this->type,
            'severity' => $this->severity,
            'status' => $this->status,
            'title' => $this->title,
            'message' => $this->message,
            'building_name' => $this->building?->name,
            'project_name' => $this->project?->name,
            'starts_at' => $this->starts_at?->toIso8601String(),
            'ends_at' => $this->ends_at?->toIso8601String(),
            'resolved_at' => $this->resolved_at?->toIso8601String(),
            // Transient: controller gán ở màn chi tiết.
            'contacts' => $this->contacts ?? [],
        ];
    }
}
