<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property \App\Models\StatementLine $resource
 *
 * Human label lives in the `fee_type` string column; `category` comes from the
 * linked fee catalog (`fee_types.category`) when eager-loaded. `description`
 * kept as a legacy alias (previously always null — no such column existed).
 */
class StatementLineResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'fee_type_id' => $this->fee_type_id,
            'label' => $this->fee_type,
            'category' => $this->whenLoaded('feeType', fn () => $this->feeType?->category),
            'description' => $this->fee_type, // legacy alias
            'quantity' => $this->quantity === null ? null : (string) $this->quantity,
            'unit_price' => $this->unit_price === null ? null : (string) $this->unit_price,
            'amount' => $this->amount === null ? null : (string) $this->amount,
        ];
    }
}
