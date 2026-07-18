<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property \App\Models\Statement $resource
 * Money is emitted as a decimal STRING (never a float) and dates as ISO-8601.
 */
class StatementResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'apartment_id' => $this->apartment_id,
            'billing_period_id' => $this->billing_period_id,
            'status' => $this->status,
            'total_amount' => $this->total_amount === null ? null : (string) $this->total_amount,
            'paid_amount' => $this->paid_amount === null ? null : (string) $this->paid_amount,
            'currency' => $this->currency ?? 'VND',
            'due_date' => $this->due_date?->toDateString(),
            'issued_at' => $this->issued_at?->toIso8601String(),
            'published_at' => $this->published_at?->toIso8601String(),
            'lines' => StatementLineResource::collection($this->whenLoaded('lines')),
        ];
    }
}
