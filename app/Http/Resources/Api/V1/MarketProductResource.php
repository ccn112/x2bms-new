<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * @property \App\Models\MarketplaceProduct $resource
 * Sản phẩm chợ nội khu (tab Chợ — CD-MK-01). `rating`/`favorited` chưa có cột → null/false.
 */
class MarketProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $image = $this->image_path
            ? (str_starts_with($this->image_path, 'http') ? $this->image_path : Storage::disk('public')->url($this->image_path))
            : null;

        return [
            'id' => (string) $this->id,
            'title' => $this->name,
            'description' => $this->description,
            'price' => $this->price === null ? null : (string) $this->price,
            'category' => $this->category,
            'condition' => $this->condition,
            'seller' => $this->seller?->full_name,
            'building' => $this->seller?->building?->name,
            'image_url' => $image,
            'rating' => null,
            'favorited' => false,
            'created_at' => optional($this->created_at)->toIso8601String(),
        ];
    }
}
