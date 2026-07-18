<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @property \App\Models\StatementLine $resource */
class StatementLineResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'fee_type_id' => $this->fee_type_id,
            'description' => $this->description ?? $this->name ?? null,
            'quantity' => $this->quantity === null ? null : (string) $this->quantity,
            'unit_price' => $this->unit_price === null ? null : (string) $this->unit_price,
            'amount' => $this->amount === null ? null : (string) $this->amount,
        ];
    }
}
