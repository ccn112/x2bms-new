<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property \App\Models\RealEstateListing $resource
 * Tin BĐS nội khu (tab Chợ — mục BĐS, tách riêng khỏi market/*). `type` = sale|rent.
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
            'published_at' => optional($this->published_at)->toIso8601String(),
        ];
    }
}
