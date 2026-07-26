<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property \App\Models\ApartmentWalletTransaction $resource
 */
class ApartmentWalletTransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'direction' => $this->direction, // in|out
            'type' => $this->type,
            'fee_category' => $this->fee_category,
            'fee_type_id' => $this->fee_type_id,
            'amount' => (string) $this->amount,
            'balance_after' => $this->balance_after === null ? null : (string) $this->balance_after,
            'reference_no' => $this->reference_no,
            'description' => $this->description,
            'status' => $this->status,
            'posted_at' => $this->posted_at?->toIso8601String(),
        ];
    }
}
