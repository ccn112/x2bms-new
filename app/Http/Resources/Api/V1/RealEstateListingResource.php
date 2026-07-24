<?php

namespace App\Http\Resources\Api\V1;

use App\Support\DemoImage;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property \App\Models\RealEstateListing $resource
 * Tin BĐS nội khu (tab Chợ — mục BĐS, tách riêng khỏi market/*). `type` = sale|rent.
 * `image_url` = ảnh demo theo chủ đề (DemoImage).
 */
class RealEstateListingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->id,
            'code' => $this->code,
            'type' => $this->type,
            'title' => $this->title,
            'price' => $this->price === null ? null : (string) $this->price,
            'area' => $this->area === null ? null : (string) $this->area,
            'bedrooms' => $this->bedrooms === null ? null : (int) $this->bedrooms,
            'owner' => $this->owner?->full_name,
            'apartment' => $this->apartment?->code,
            'image_url' => DemoImage::url('apartment,interior,realestate', $this->id),
            'published_at' => optional($this->published_at)->toIso8601String(),
        ];
    }
}
