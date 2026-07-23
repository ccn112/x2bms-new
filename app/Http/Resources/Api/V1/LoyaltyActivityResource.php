<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property \App\Models\LoyaltyTransaction $resource
 * Một dòng lịch sử điểm (CD-LY-01 "Hoạt động gần đây"). `points` âm = đổi/redeem.
 */
class LoyaltyActivityResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->id,
            'title' => $this->description ?? ($this->type === 'redeem' ? 'Đổi điểm' : 'Tích điểm'),
            'type' => $this->type,
            'points' => (int) $this->points,
            'occurred_at' => optional($this->transacted_at ?? $this->created_at)->toIso8601String(),
        ];
    }
}
