<?php

namespace App\Services\Billing;

use App\Models\AuditLog;
use App\Models\Payment;
use App\Models\PaymentAllocation;
use App\Models\Receipt;
use App\Models\Statement;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * BQL duyệt / từ chối chứng từ chuyển khoản do cư dân nộp (chốt 2026-07-30).
 *
 * Đây là chỗ DUY NHẤT biến một khai báo `pending` thành tiền đã ghi nhận. Cố ý
 * là **service** chứ không phải trait của Filament: nghiệp vụ tiền không được
 * phụ thuộc `auth()` hay tầng UI, và sau này webhook cổng thanh toán cũng phải
 * đi qua đây thay vì viết lại.
 *
 * ## Vì sao phải khoá hàng (`lockForUpdate`)
 * Hai người của BQL mở cùng một khai báo rồi bấm Duyệt gần như đồng thời sẽ ghi
 * nhận tiền HAI LẦN: hai phân bổ vào cùng hoá đơn, hai biên lai, công nợ trừ gấp
 * đôi. Khoá + kiểm lại trạng thái sau khi khoá làm quyết định tuần tự hoá theo
 * ai COMMIT trước; người thứ hai thấy bản ghi đã `confirmed` và thành no-op.
 *
 * ## Vì sao phân bổ chỉ xảy ra Ở ĐÂY
 * `POST resident/payments/claim` cố tình KHÔNG tạo `payment_allocations`. Nếu tạo
 * lúc khai báo thì cư dân gửi ảnh bất kỳ là thấy hết nợ trong khi tiền chưa về.
 */
class ResidentPaymentClaimReviewer
{
    /**
     * Duyệt: khoản thành `confirmed`, phân bổ vào hoá đơn cư dân khai (nếu có),
     * phát hành biên lai.
     *
     * Idempotent: khoản đã `confirmed` thì trả về nguyên trạng, KHÔNG tạo phân bổ
     * hay biên lai thứ hai.
     */
    public function approve(Payment $payment, ?User $reviewer = null, ?string $note = null): Payment
    {
        return DB::transaction(function () use ($payment, $reviewer, $note) {
            $fresh = Payment::withoutGlobalScopes()
                ->whereKey($payment->getKey())->lockForUpdate()->first();

            if ($fresh === null || $fresh->status !== Payment::STATUS_PENDING) {
                return $fresh ?? $payment;
            }

            $fresh->forceFill([
                'status' => Payment::STATUS_CONFIRMED,
                'reviewed_by_id' => $reviewer?->id,
                'reviewed_at' => now(),
                'review_note' => $note,
            ])->save();

            $allocated = $this->allocateToClaimedStatement($fresh);
            $this->issueReceipt($fresh, $reviewer);

            $this->audit('payment.claim.approve', $fresh, $reviewer, sprintf(
                'Duyệt chứng từ %s: %s đ%s',
                $fresh->code,
                number_format((float) $fresh->amount, 0, ',', '.'),
                $allocated > 0
                    ? ' — phân bổ '.number_format($allocated, 0, ',', '.')
                        .' đ vào hoá đơn #'.$fresh->claimed_statement_id
                    : ' — chưa phân bổ vào hoá đơn nào'
            ));

            return $fresh;
        });
    }

    /**
     * Từ chối — BẮT BUỘC lý do, vì cư dân đọc chính lý do này trong app (không
     * có nó thì họ chỉ thấy "bị từ chối" mà không biết sửa gì).
     *
     * Chặn bằng exception ở tầng nghiệp vụ, không chỉ dựa vào `->required()` của
     * form: luật này không được lách qua bất kỳ đường gọi nào khác.
     */
    public function reject(Payment $payment, ?User $reviewer, string $reason): Payment
    {
        $reason = trim($reason);
        if ($reason === '') {
            throw new InvalidArgumentException(
                'Cần nhập lý do từ chối — cư dân sẽ nhìn thấy lý do này trong app.');
        }

        return DB::transaction(function () use ($payment, $reviewer, $reason) {
            $fresh = Payment::withoutGlobalScopes()
                ->whereKey($payment->getKey())->lockForUpdate()->first();

            if ($fresh === null || $fresh->status !== Payment::STATUS_PENDING) {
                return $fresh ?? $payment;
            }

            $fresh->forceFill([
                'status' => Payment::STATUS_REJECTED,
                'reviewed_by_id' => $reviewer?->id,
                'reviewed_at' => now(),
                'review_note' => $reason,
            ])->save();

            $this->audit('payment.claim.reject', $fresh, $reviewer,
                'Từ chối chứng từ '.$fresh->code.': '.$reason);

            return $fresh;
        });
    }

    /**
     * Phân bổ vào hoá đơn cư dân khai. Trả về số tiền ĐÃ phân bổ.
     *
     * Chỉ phân bổ tối đa bằng phần CÒN NỢ của hoá đơn. Cư dân chuyển nhiều hơn
     * (trả gộp nhiều kỳ, hoặc trả dư) thì phần vượt để nguyên chưa phân bổ —
     * `paid_amount` không được vượt `total_amount`, nếu không hoá đơn hiện "đã
     * trả 6 triệu / tổng 5 triệu" và mọi báo cáo công nợ sai theo.
     */
    private function allocateToClaimedStatement(Payment $payment): float
    {
        if ($payment->claimed_statement_id === null) {
            return 0.0;
        }

        // Khoá hoá đơn: hai khoản khác nhau cùng phân bổ vào một hoá đơn cũng có
        // thể cộng dồn sai nếu đọc `paid_amount` trước khi người kia ghi.
        $statement = Statement::withoutGlobalScopes()
            ->whereKey($payment->claimed_statement_id)->lockForUpdate()->first();

        if ($statement === null) {
            return 0.0;
        }

        $remaining = (float) $statement->total_amount - (float) $statement->paid_amount;
        if ($remaining <= 0) {
            return 0.0;
        }

        $amount = min((float) $payment->amount, $remaining);

        PaymentAllocation::create([
            'payment_id' => $payment->id,
            'statement_id' => $statement->id,
            'amount' => $amount,
        ]);

        $paid = (float) $statement->paid_amount + $amount;
        $statement->forceFill([
            'paid_amount' => $paid,
            // Vốn từ THẬT của statements.status (đo trên DB 30/07):
            // paid 466 · partial 622 · issued 272 — không có giá trị nào khác.
            'status' => $paid >= (float) $statement->total_amount
                ? 'paid'
                : ($paid > 0 ? 'partial' : 'issued'),
        ])->save();

        return $amount;
    }

    /**
     * Biên lai cho khoản đã xác nhận. Idempotent theo quan hệ 1-1 với payment:
     * duyệt lại (nếu có lọt qua) không sinh biên lai thứ hai.
     */
    private function issueReceipt(Payment $payment, ?User $reviewer): void
    {
        if (Receipt::withoutGlobalScopes()->where('payment_id', $payment->id)->exists()) {
            return;
        }

        Receipt::create([
            'tenant_id' => $payment->tenant_id,
            'payment_id' => $payment->id,
            'code' => $this->nextReceiptCode((int) $payment->tenant_id),
            'amount' => $payment->amount,
            'issued_at' => now(),
            'issued_by_id' => $reviewer?->id,
        ]);
    }

    /**
     * Mã biên lai theo ĐÚNG định dạng đang có trong DB: `BL-YYMM-NNN`.
     *
     * Còn một khe hở nhỏ: hai lượt duyệt đồng thời trong cùng tháng có thể tính
     * ra cùng số thứ tự (bảng `receipts` không có unique index trên `code`). Hậu
     * quả là hai biên lai trùng NHÃN, không phải sai số tiền — chấp nhận được
     * trước mắt. Muốn đóng hẳn thì thêm unique (tenant_id, code), nhưng phải rà
     * dữ liệu cũ trước vì có thể đã trùng sẵn.
     */
    private function nextReceiptCode(int $tenantId): string
    {
        $prefix = 'BL-'.now()->format('ym').'-';

        $last = Receipt::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('code', 'like', $prefix.'%')
            ->orderByDesc('code')
            ->value('code');

        $next = $last === null ? 1 : ((int) substr($last, strlen($prefix))) + 1;

        return $prefix.str_pad((string) $next, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Ghi vết theo schema `audit_logs` THẬT (tenant_id/building_id/user_id/
     * actor_name/action/subject_type/subject_id/description) — không dùng
     * auditable_type/event/new_values như một chỗ khác trong codebase đã lỡ
     * dùng, vì những cột đó không tồn tại nên ghi thất bại âm thầm.
     */
    private function audit(string $action, Payment $payment, ?User $user, string $description): void
    {
        AuditLog::create([
            'tenant_id' => $payment->tenant_id,
            'building_id' => $payment->building_id,
            'user_id' => $user?->id,
            'actor_name' => $user?->name,
            'action' => $action,
            'subject_type' => 'Payment',
            'subject_id' => $payment->id,
            'description' => $description,
        ]);
    }
}
