<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property \App\Models\ApartmentWallet $resource
 * Tiền là chuỗi decimal (không float). `available_total` = quỹ chung + Σ ngăn.
 */
class ApartmentWalletResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'apartment_id' => $this->apartment_id,
            'currency' => $this->currency ?? 'VND',
            'balance' => (string) $this->balance,          // quỹ chung
            'available_total' => $this->availableTotal(),  // tổng khả dụng
            'status' => $this->status,
            'buckets' => $this->whenLoaded('buckets', fn () => $this->buckets->map(fn ($b) => [
                'id' => $b->id,
                'fee_category' => $b->fee_category,
                'fee_type_id' => $b->fee_type_id,
                'fee_type' => $b->relationLoaded('feeType') ? $b->feeType?->name : null,
                'balance' => (string) $b->balance,
            ])->values()),
        ];
    }
}
