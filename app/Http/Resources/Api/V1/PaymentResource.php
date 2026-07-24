<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property \App\Models\Payment $resource
 * Lịch sử thanh toán của cư dân (tab Hoá đơn — CD-PAY-05). Tiền là chuỗi decimal.
 */
class PaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->id,
            'code' => $this->code,
            'amount' => $this->amount === null ? null : (string) $this->amount,
            'status' => $this->status,
            'method' => $this->method?->name,
            'reference_no' => $this->reference_no,
            'paid_at' => optional($this->paid_at)->toIso8601String(),
            'note' => $this->note,
            'allocations' => $this->when(
                $this->relationLoaded('allocations'),
                fn () => $this->allocations->map(fn ($a) => [
                    'statement_id' => $a->statement_id,
                    'statement_line_id' => $a->statement_line_id,
                    'amount' => $a->amount === null ? null : (string) $a->amount,
                ])->values()->all()
            ),
            'receipt' => $this->when(
                $this->relationLoaded('receipt'),
                fn () => $this->receipt === null ? null : [
                    'code' => $this->receipt->code,
                    'amount' => $this->receipt->amount === null ? null : (string) $this->receipt->amount,
                    'issued_at' => optional($this->receipt->issued_at)->toIso8601String(),
                ]
            ),
        ];
    }
}
