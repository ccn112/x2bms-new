<?php

namespace App\Services\Billing;

use App\Models\AuditLog;
use App\Models\Payment;
use App\Models\PaymentAllocation;
use App\Models\Receipt;
use App\Models\Statement;
use App\Models\StatementLine;
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

            // N1: báo cư dân "thanh toán đã được xác nhận" (vào chuông). Chỉ chạy
            // trên lần chuyển pending→confirmed thật (guard ở trên đã lọc no-op).
            $this->notifyPaymentConfirmed($fresh);

            return $fresh;
        });
    }

    /** N1 — sinh activity xác nhận thanh toán cho chủ chứng từ. */
    private function notifyPaymentConfirmed(Payment $payment): void
    {
        $resident = null;
        if (! empty($payment->resident_id)) {
            $resident = \App\Models\Resident::withoutGlobalScopes()->find($payment->resident_id);
        } elseif (! empty($payment->apartment_id)) {
            $rid = \App\Models\ResidentApartmentRelation::query()
                ->where('apartment_id', $payment->apartment_id)
                ->orderByDesc('is_primary')->orderBy('id')->value('resident_id');
            $resident = $rid ? \App\Models\Resident::withoutGlobalScopes()->find($rid) : null;
        }
        if ($resident === null || $resident->user_id === null) {
            return;
        }

        $projectId = \App\Models\Building::withoutGlobalScopes()->whereKey($resident->building_id)->value('project_id');
        $hasStatement = ! empty($payment->claimed_statement_id);

        app(\App\Services\Notifications\ActivityEmitter::class)->emit([
            'recipient_user_id' => (int) $resident->user_id,
            'tenant_id' => (int) ($payment->tenant_id ?? $resident->tenant_id),
            'project_id' => $projectId,
            'kind' => 'payment_confirmed',
            'title' => 'Thanh toán của bạn đã được xác nhận',
            'body' => 'Chứng từ '.$payment->code.' đã được Ban quản lý duyệt.',
            'entity_type' => $hasStatement ? 'statement' : 'payment',
            'entity_id' => $hasStatement ? (int) $payment->claimed_statement_id : (int) $payment->id,
            'action_key' => $hasStatement ? 'view_statement' : null,
        ]);
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
     * Phân bổ vào hoá đơn cư dân khai — THEO TỪNG DÒNG PHÍ (Phase B3, D3).
     * Trả về số tiền ĐÃ phân bổ.
     *
     * Trước bản này: một `PaymentAllocation` phẳng ở cấp `statement`, không
     * đụng `statement_lines.paid_amount` — "còn nợ gì" ở cấp dòng phí (màn công
     * nợ theo dịch vụ, D6) không có cách nào đúng vì tiền vào không biết trả
     * cho dòng nào. Nay đi qua từng dòng theo `StatementLine::allocationSortKey()`
     * (dùng CHUNG với `ApartmentWalletService` — một khoá thứ tự duy nhất),
     * ghi một `PaymentAllocation` riêng cho MỖI dòng chạm tới (`statement_line_id`
     * kèm `statement_id`), rồi để `Statement::recomputePaidAmount()` tính lại
     * `paid_amount`/`status` từ tổng các dòng — không cộng tay ở đây nữa.
     *
     * Chỉ phân bổ tối đa bằng phần CÒN NỢ. Cư dân chuyển nhiều hơn (trả gộp
     * nhiều kỳ, hoặc trả dư) thì phần vượt để nguyên CHƯA phân bổ — dòng phí
     * không được nhận quá `amount` của chính nó.
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

        $remaining = (string) $payment->amount;
        if (bccomp($remaining, '0', 2) <= 0) {
            return 0.0;
        }

        // D10 — cư dân đã chọn DÒNG cụ thể: phân bổ đúng các dòng đó theo số tiền
        // đã chọn (cap ở phần còn nợ mỗi dòng + phần còn của khoản), KHÔNG quét
        // theo khoá ưu tiên D4. Cap lại theo `outstanding()` hiện tại vì giữa lúc
        // khai và lúc duyệt, khoản khác có thể đã trả bớt dòng đó.
        if (! empty($payment->claimed_line_items)) {
            return $this->allocateToClaimedLines($payment, $statement, $remaining);
        }

        // Nạp sẵn `building` MỘT LẦN rồi gán thẳng quan hệ `statement` cho từng dòng
        // (thay vì để `StatementLine::resolveProjectId()` lazy-load lại) — mọi dòng ở
        // đây cùng một bảng kê nên không cần eager-load theo từng dòng (Phase B4).
        $statement->loadMissing('building');
        $lines = $statement->lines()->outstanding()->with('feeType')->lockForUpdate()->get()
            ->each(fn (StatementLine $l) => $l->setRelation('statement', $statement))
            ->sortBy(fn (StatementLine $l) => $l->allocationSortKey());

        // Bảng kê KHÔNG có dòng phí nào (dữ liệu cũ/legacy chưa từng itemize) —
        // không có gì để đi qua từng dòng, giữ hành vi phẳng cũ ở cấp statement
        // thay vì để tiền "biến mất" vì vòng lặp dưới không chạm gì.
        if ($statement->lines()->count() === 0) {
            $remainingOnStatement = bcsub((string) $statement->total_amount, (string) $statement->paid_amount, 2);
            if (bccomp($remainingOnStatement, '0', 2) <= 0) {
                return 0.0;
            }
            $take = bccomp($remaining, $remainingOnStatement, 2) >= 0 ? $remainingOnStatement : $remaining;

            PaymentAllocation::create([
                'payment_id' => $payment->id,
                'statement_id' => $statement->id,
                'amount' => $take,
            ]);

            $statement->forceFill(['paid_amount' => bcadd((string) $statement->paid_amount, $take, 2)]);
            $statement->status = bccomp($statement->paid_amount, $statement->total_amount, 2) >= 0 ? 'paid' : 'partial';
            $statement->save();

            return (float) $take;
        }

        $allocated = '0';
        foreach ($lines as $line) {
            if (bccomp($remaining, '0', 2) <= 0) {
                break;
            }

            $owed = $line->outstanding();
            if (bccomp($owed, '0', 2) <= 0) {
                continue;
            }

            $take = bccomp($remaining, $owed, 2) >= 0 ? $owed : $remaining;

            // P1a/ADR-003: chốt legacy base TRƯỚC khi tạo allocation ledger row.
            $line->ensureLegacyBase();

            PaymentAllocation::create([
                'payment_id' => $payment->id,
                'statement_id' => $statement->id,
                'statement_line_id' => $line->id,
                'amount' => $take,
            ]);

            // `paid_amount` = legacy + Σ ledger; recompute từ allocation vừa tạo, KHÔNG cộng tay.
            $line->recomputePaidFromLedger();

            $remaining = bcsub($remaining, $take, 2);
            $allocated = bcadd($allocated, $take, 2);
        }

        if (bccomp($allocated, '0', 2) > 0) {
            $statement->recomputePaidAmount();
        }

        return (float) $allocated;
    }

    /**
     * D10 — phân bổ vào ĐÚNG các dòng cư dân chọn (`payment.claimed_line_items`),
     * theo số tiền chọn nhưng cap ở phần còn nợ mỗi dòng và phần còn của khoản.
     * Thứ tự theo đúng danh sách cư dân gửi; không dùng khoá ưu tiên D4.
     */
    private function allocateToClaimedLines(Payment $payment, Statement $statement, string $remaining): float
    {
        $allocated = '0';
        $ids = collect($payment->claimed_line_items)
            ->pluck('statement_line_id')->map(fn ($v) => (int) $v);
        $lines = $statement->lines()->whereIn('id', $ids)->lockForUpdate()->get()->keyBy('id');

        foreach ($payment->claimed_line_items as $item) {
            if (bccomp($remaining, '0', 2) <= 0) {
                break;
            }
            $line = $lines[(int) $item['statement_line_id']] ?? null;
            if ($line === null) {
                continue;
            }
            $owed = $line->outstanding();
            if (bccomp($owed, '0', 2) <= 0) {
                continue;
            }
            $take = (string) $item['amount'];
            if (bccomp($take, $owed, 2) > 0) {
                $take = $owed;
            }
            if (bccomp($take, $remaining, 2) > 0) {
                $take = $remaining;
            }
            if (bccomp($take, '0', 2) <= 0) {
                continue;
            }

            $line->ensureLegacyBase();

            PaymentAllocation::create([
                'payment_id' => $payment->id,
                'statement_id' => $statement->id,
                'statement_line_id' => $line->id,
                'amount' => $take,
            ]);
            $line->recomputePaidFromLedger();

            $remaining = bcsub($remaining, $take, 2);
            $allocated = bcadd($allocated, $take, 2);
        }

        if (bccomp($allocated, '0', 2) > 0) {
            $statement->recomputePaidAmount();
        }

        return (float) $allocated;
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
