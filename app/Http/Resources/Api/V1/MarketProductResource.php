<?php

namespace App\Http\Resources\Api\V1;

use App\Support\DemoImage;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * @property \App\Models\MarketplaceProduct $resource
 * Sản phẩm chợ nội khu (tab Chợ — CD-MK-01). `rating`/`favorited` chưa có cột → null/false.
 * `image_url` từ `image_path`; nếu rỗng → ảnh demo theo `category` (DemoImage).
 */
class MarketProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $image = $this->image_path
            ? (str_starts_with($this->image_path, 'http') ? $this->image_path : Storage::disk('public')->url($this->image_path))
            : DemoImage::url($this->demoKeywords(), $this->id);

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

    /** Chủ đề ảnh demo theo danh mục sản phẩm. */
    private function demoKeywords(): string
    {
        return match ($this->category) {
            'household' => 'furniture,home',
            'electronics' => 'electronics,gadget',
            'fashion' => 'clothes,fashion',
            default => 'product,marketplace',
        };
    }
}
