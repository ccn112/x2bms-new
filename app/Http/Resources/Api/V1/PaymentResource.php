<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property Payment $resource
 *                             Lịch sử thanh toán của cư dân (tab Hoá đơn — CD-PAY-05). Tiền là chuỗi decimal.
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

            // Khoản này từ đâu ra: cư dân tự khai (`resident_app`) hay BQL nhập.
            // App cần biết để vẽ đúng — khoản cư dân tự khai mà đang `pending`
            // thì hiện "Chờ BQL duyệt", chứ không hiện như đã thanh toán.
            'source' => $this->source,
            'submitted_at' => optional($this->submitted_at)->toIso8601String(),
            'claimed_statement_id' => $this->claimed_statement_id === null
                ? null : (string) $this->claimed_statement_id,

            // Kết quả duyệt. `review_note` là lý do TỪ CHỐI — phải trả về, nếu
            // không cư dân chỉ thấy "bị từ chối" mà không biết sửa gì.
            'reviewed_at' => optional($this->reviewed_at)->toIso8601String(),
            'review_note' => $this->review_note,

            // Ảnh chứng từ cư dân đã nộp (chỉ khi đã load quan hệ).
            'attachments' => $this->when(
                $this->relationLoaded('attachments'),
                fn () => AttachmentResource::collection($this->attachments)->resolve($request)
            ),
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
