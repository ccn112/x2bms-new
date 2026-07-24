<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property \App\Models\Voucher $resource
 * Quà đổi điểm (tab Ưu đãi — CD-LY-01 "Đổi quà"). Là voucher có points_cost > 0.
 * `image_url` chưa có cột → null (app dùng placeholder).
 */
class GiftResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->id,
            'code' => $this->code,
            'title' => $this->name,
            'overline' => $this->type,
            'points_cost' => (int) $this->points_cost,
            'value' => $this->value === null ? null : (string) $this->value,
            'expiry_date' => optional($this->valid_to)->toDateString(),
            'image_url' => null,
            'is_platform' => $this->owner_level === 'platform',
        ];
    }
}
