<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property \App\Models\FeedbackCategory $resource
 * Danh mục phản ánh (kỹ thuật/vệ sinh/an ninh…).
 */
class FeedbackCategoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'color' => $this->color,
        ];
    }
}
