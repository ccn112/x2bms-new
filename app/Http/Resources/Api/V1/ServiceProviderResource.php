<?php

namespace App\Http\Resources\Api\V1;

use App\Support\DemoImage;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property \App\Models\ServiceProvider $resource
 * Nhà cung cấp dịch vụ (tab Chợ — CD-MK dịch vụ). `price` chưa có ở provider → null
 * (giá nằm ở service_orders khi phát sinh). `image_url` = ảnh demo theo category (DemoImage).
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
            'image_url' => DemoImage::url('service,repair,'.$this->category, $this->id),
        ];
    }
}
