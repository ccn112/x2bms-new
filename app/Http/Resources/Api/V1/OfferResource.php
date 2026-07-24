<?php

namespace App\Http\Resources\Api\V1;

use App\Support\DemoImage;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property \App\Models\Voucher $resource
 * Ưu đãi (tab Ưu đãi — CD-OF-01). Là voucher hiển thị cho cư dân KHÔNG cần đổi
 * điểm (points_cost = 0/null). `image_url` = ảnh demo theo chủ đề (DemoImage).
 */
class OfferResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->id,
            'code' => $this->code,
            'title' => $this->name,
            'badge' => $this->type,
            'value' => $this->value === null ? null : (string) $this->value,
            'expiry_date' => optional($this->valid_to)->toDateString(),
            'image_url' => DemoImage::url('shopping,voucher,discount', $this->id),
            'is_platform' => $this->owner_level === 'platform',
        ];
    }
}
