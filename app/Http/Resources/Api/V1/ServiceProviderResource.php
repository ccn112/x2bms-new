<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property \App\Models\ServiceProvider $resource
 * Nhà cung cấp dịch vụ (tab Chợ — CD-MK dịch vụ). `price`/`image_url` chưa có ở
 * provider → null (giá nằm ở service_orders khi phát sinh).
 */
class ServiceProviderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->id,
            'title' => $this->name,
            'description' => $this->category,
            'category' => $this->category,
            'phone' => $this->phone,
            'rating' => $this->rating === null ? null : (string) $this->rating,
            'price' => null,
            'image_url' => null,
        ];
    }
}
