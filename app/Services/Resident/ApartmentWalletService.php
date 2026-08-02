<?php

namespace App\Services\Resident;

use App\Models\Apartment;
use App\Models\ApartmentWallet;
use App\Models\ApartmentWalletBucket;
use App\Models\ApartmentWalletTransaction;
use App\Models\FeeType;
use App\Models\Statement;
use App\Models\StatementLine;
use Illuminate\Support\Facades\DB;

/**
 * Hạch toán ví cư dân theo CĂN HỘ.
 *
 * - Tiền VÀO (phiếu thu / nộp tiền): cộng vào quỹ chung hoặc một NGĂN (fee_category, tùy chọn fee_type).
 * - Tiền RA (trả nợ): trừ theo thứ tự NGĂN khớp nhất → quỹ chung.
 * - Tự hạch toán: quét dòng phí còn nợ, trả phí ƯU TIÊN (is_critical, payment_priority nhỏ) trước.
 *
 * Số tiền dùng chuỗi + bcmath để tránh sai số float trên tiền.
 */
class ApartmentWalletService
{
    /** Lấy (hoặc tạo) ví của căn hộ. */
    public function walletFor(Apartment $apartment): ApartmentWallet
    {
        return ApartmentWallet::firstOrCreate(
            ['apartment_id' => $apartment->id],
            [
                'tenant_id' => $apartment->tenant_id,
                'building_id' => $apartment->building_id,
                'currency' => 'VND',
                'balance' => 0,
                'status' => 'active',
            ],
        );
    }

    /**
     * Nạp tiền VÀO ví. Nếu có $feeCategory → vào ngăn tương ứng (tùy chọn $feeTypeId); nếu không → quỹ chung.
     * $reference: model liên quan (Receipt…) để truy vết.
     */
    public function credit(
        ApartmentWallet $wallet,
        string $amount,
        string $type = 'receipt',
        ?string $feeCategory = null,
        ?int $feeTypeId = null,
        $reference = null,
        ?string $description = null,
        ?int $userId = null,
        ?string $subjectType = null,
        ?int $subjectId = null,
    ): ApartmentWalletTransaction {
        return DB::transaction(function () use ($wallet, $amount, $type, $feeCategory, $feeTypeId, $reference, $description, $userId, $subjectType, $subjectId) {
            if ($feeCategory === null) {
                $wallet->balance = bcadd((string) $wallet->balance, $amount, 2);
                $wallet->save();
            } else {
                $bucket = $this->bucket($wallet, $feeCategory, $feeTypeId, $subjectType, $subjectId);
                $bucket->balance = bcadd((string) $bucket->balance, $amount, 2);
                $bucket->save();
            }

            return $this->log($wallet, 'in', $type, $amount, $feeCategory, $feeTypeId, $reference, $description, $userId);
        });
    }

    /**
     * Trừ ví để trả cho một loại phí. Ưu tiên nguồn: ngăn fee_type khớp → ngăn fee_category → quỹ chung.
     * Trả về số tiền THỰC trừ được (có thể < $amount nếu ví không đủ).
     */
    public function debit(
        ApartmentWallet $wallet,
        string $amount,
        string $feeCategory,
        ?int $feeTypeId = null,
        string $type = 'debt_settlement',
        $reference = null,
        ?string $description = null,
        ?int $userId = null,
    ): string {
        return DB::transaction(function () use ($wallet, $amount, $feeCategory, $feeTypeId, $type, $reference, $description, $userId) {
            $remaining = $amount;

            // 1) ngăn khớp đúng fee_type
            if ($feeTypeId !== null) {
                $remaining = $this->drawBucket($wallet, $feeCategory, $feeTypeId, $remaining);
            }
            // 2) ngăn cấp nhóm (fee_type_id NULL)
            $remaining = $this->drawBucket($wallet, $feeCategory, null, $remaining);
            // 3) quỹ chung
            if (bccomp($remaining, '0', 2) > 0 && bccomp((string) $wallet->balance, '0', 2) > 0) {
                $take = bccomp((string) $wallet->balance, $remaining, 2) >= 0 ? $remaining : (string) $wallet->balance;
                $wallet->balance = bcsub((string) $wallet->balance, $take, 2);
                $wallet->save();
                $remaining = bcsub($remaining, $take, 2);
            }

            $settled = bcsub($amount, $remaining, 2);
            if (bccomp($settled, '0', 2) > 0) {
                $this->log($wallet, 'out', $type, $settled, $feeCategory, $feeTypeId, $reference, $description, $userId);
            }

            return $settled;
        });
    }

    /**
     * Tự hạch toán toàn bộ tiền đang có trong ví vào các dòng phí còn nợ của căn hộ.
     * Thứ tự: `StatementLine::allocationSortKey()` — CHUNG với
     * `ResidentPaymentClaimReviewer`, không tự chọn thứ tự riêng (Phase B3).
     *
     * SỬA 2026-07-31 (`docs/delivery/TECH_DEBT_REGISTER.md` M8): trước bản này
     * ghi `line.paid_amount` xong KHÔNG đụng gì tới `statement.paid_amount` —
     * bảng kê cha lệch khỏi tổng các dòng của chính nó ngay khi hàm này chạy.
     * Dòng phí có thể trải trên NHIỀU bảng kê (nợ dồn nhiều kỳ) nên phải gom
     * theo `statement_id` rồi `recomputePaidAmount()` từng bảng kê CHẠM TỚI,
     * không phải mọi bảng kê của căn hộ.
     */
    public function autoSettleOutstanding(ApartmentWallet $wallet): void
    {
        $lines = $this->outstandingLines($wallet->apartment_id);
        $touchedStatementIds = [];

        foreach ($lines as $line) {
            if (bccomp($this->totalAvailable($wallet->fresh(['buckets'])), '0', 2) <= 0) {
                break;
            }
            $owed = $line->outstanding();
            if (bccomp($owed, '0', 2) <= 0) {
                continue;
            }
            $category = $line->fee_category ?? optional($line->feeType)->category ?? 'other';
            $settled = $this->debit($wallet, $owed, $category, $line->fee_type_id, 'debt_settlement', $line, "Trả phí #{$line->id}");
            if (bccomp($settled, '0', 2) > 0) {
                $line->paid_amount = bcadd((string) ($line->paid_amount ?? 0), $settled, 2);
                $line->status = bccomp($line->outstanding(), '0', 2) <= 0 ? 'paid' : 'partial';
                $line->save();
                $touchedStatementIds[$line->statement_id] = true;
            }
        }

        foreach (array_keys($touchedStatementIds) as $statementId) {
            Statement::find($statementId)?->recomputePaidAmount();
        }
    }

    /**
     * Dòng phí còn nợ của căn hộ, đã sắp theo khoá phân bổ dùng chung (B3).
     * Nợ dồn nhiều kỳ nên các dòng có thể thuộc NHIỀU statement khác nhau (khác
     * `ResidentPaymentClaimReviewer`, không cùng MỘT statement) — eager-load
     * `statement.building` theo từng dòng để `effectivePaymentPriority()` (B4)
     * suy được dự án mà không phát sinh N+1.
     */
    public function outstandingLines(int $apartmentId)
    {
        return StatementLine::query()
            ->whereHas('statement', fn ($q) => $q->where('apartment_id', $apartmentId))
            ->outstanding()
            ->with(['feeType', 'statement.building'])
            ->get()
            ->sortBy(fn (StatementLine $l) => $l->allocationSortKey())
            ->values();
    }

    /** Nợ tổng hợp theo từng loại phí (gộp mọi kỳ) — cho màn "nợ cũ per-service". */
    public function debtByFeeType(int $apartmentId): array
    {
        $lines = StatementLine::query()
            ->whereHas('statement', fn ($q) => $q->where('apartment_id', $apartmentId))
            ->whereColumn('paid_amount', '<', 'amount')
            ->with('feeType')
            ->get();

        $grouped = [];
        foreach ($lines as $line) {
            $key = $line->fee_type_id ?? ('name:' . $line->fee_type);
            $grouped[$key] ??= [
                'fee_type_id' => $line->fee_type_id,
                'fee_type' => $line->fee_type,
                'fee_category' => $line->fee_category ?? optional($line->feeType)->category,
                'is_critical' => (bool) optional($line->feeType)->is_critical,
                'enforcement_action' => optional($line->feeType)->enforcement_action,
                'outstanding' => '0',
            ];
            $grouped[$key]['outstanding'] = bcadd($grouped[$key]['outstanding'], $line->outstanding(), 2);
        }

        return array_values($grouped);
    }

    /**
     * D6 Slice B — trả công nợ cho MỘT tài sản (claim-by-asset). Nạp `amount` vào
     * NGĂN theo chiều tài sản rồi rút đúng các dòng đã chọn theo thứ tự
     * `StatementLine::allocationSortKey()` (khoá phân bổ DÙNG CHUNG với
     * `ResidentPaymentClaimReviewer` — không tự chọn thứ tự khác). Tiền thừa Ở LẠI
     * ngăn tài sản để LẦN SAU tự trừ tiếp cho đúng tài sản đó — không mất, không
     * lẫn sang tài sản khác cùng loại phí.
     *
     * Cố ý đi qua ngăn (nạp IN → trả nợ OUT → phần dư earmark) thay vì gán thẳng
     * vào dòng: ledger `apartment_wallet_transactions` khép kín, `balance_after`
     * liền mạch, và phần dư nằm sẵn ở ngăn nên lần trả sau chỉ việc nạp thêm rồi
     * rút tiếp — dư cũ được tiêu trước một cách tự nhiên.
     *
     * @param  iterable<StatementLine>  $lines  các dòng ĐÃ SẮP theo allocationSortKey
     * @return array{allocated:string, bucket_balance:string, per_line:array<int,string>}
     */
    public function settleAssetLines(
        ApartmentWallet $wallet,
        iterable $lines,
        string $amount,
        string $feeCategory,
        ?int $feeTypeId,
        ?string $subjectType,
        ?int $subjectId,
        ?int $userId = null,
    ): array {
        return DB::transaction(function () use ($wallet, $lines, $amount, $feeCategory, $feeTypeId, $subjectType, $subjectId, $userId) {
            // 1) Nạp toàn bộ tiền vào ngăn tài sản (cộng lên phần dư CŨ của ngăn nếu có).
            $bucket = $this->bucket($wallet, $feeCategory, $feeTypeId, $subjectType, $subjectId);
            $bucket->balance = bcadd((string) $bucket->balance, $amount, 2);
            $bucket->save();
            $this->log($wallet, 'in', 'topup', $amount, $feeCategory, $feeTypeId, null,
                'Nạp trả trước tài sản', $userId);

            // 2) Rút ngăn trả từng dòng đã chọn, cap ở phần còn nợ mỗi dòng.
            $allocated = '0';
            $perLine = [];
            $touchedStatementIds = [];

            foreach ($lines as $line) {
                if (bccomp((string) $bucket->balance, '0', 2) <= 0) {
                    break;
                }
                $owed = $line->outstanding();
                if (bccomp($owed, '0', 2) <= 0) {
                    $perLine[$line->id] = '0';
                    continue;
                }
                $take = bccomp((string) $bucket->balance, $owed, 2) >= 0 ? $owed : (string) $bucket->balance;

                $bucket->balance = bcsub((string) $bucket->balance, $take, 2);
                $bucket->save();

                $line->paid_amount = bcadd((string) ($line->paid_amount ?? 0), $take, 2);
                $line->status = bccomp($line->outstanding(), '0', 2) <= 0 ? 'paid' : 'partial';
                $line->save();

                $this->log($wallet, 'out', 'debt_settlement', $take, $feeCategory, $feeTypeId, $line,
                    "Trả phí #{$line->id}", $userId);

                $allocated = bcadd($allocated, $take, 2);
                $perLine[$line->id] = $take;
                $touchedStatementIds[$line->statement_id] = true;
            }

            foreach (array_keys($touchedStatementIds) as $statementId) {
                Statement::find($statementId)?->recomputePaidAmount();
            }

            return [
                'allocated' => $allocated,
                'bucket_balance' => (string) $bucket->balance,
                'per_line' => $perLine,
            ];
        });
    }

    // ---- helpers ----

    private function bucket(
        ApartmentWallet $wallet,
        string $feeCategory,
        ?int $feeTypeId,
        ?string $subjectType = null,
        ?int $subjectId = null,
    ): ApartmentWalletBucket {
        // subject_type/subject_id NULL = ngăn theo fee_type NHƯ CŨ (D6 giữ nguyên
        // hành vi cho phí không gắn tài sản).
        return ApartmentWalletBucket::firstOrCreate(
            [
                'wallet_id' => $wallet->id,
                'fee_category' => $feeCategory,
                'fee_type_id' => $feeTypeId,
                'subject_type' => $subjectType,
                'subject_id' => $subjectId,
            ],
            ['tenant_id' => $wallet->tenant_id, 'balance' => 0],
        );
    }

    private function drawBucket(ApartmentWallet $wallet, string $feeCategory, ?int $feeTypeId, string $remaining): string
    {
        if (bccomp($remaining, '0', 2) <= 0) {
            return $remaining;
        }
        // Chỉ rút ngăn KHÔNG gắn tài sản (subject NULL). Ngăn earmark theo tài sản
        // (D6) chỉ được settleAssetLines rút cho đúng tài sản của nó — hạch toán
        // chung (autoSettle) không được tiêu lẹm vào tiền đã dành cho một chiếc xe.
        $bucket = ApartmentWalletBucket::where('wallet_id', $wallet->id)
            ->where('fee_category', $feeCategory)
            ->where('fee_type_id', $feeTypeId)
            ->whereNull('subject_type')
            ->whereNull('subject_id')
            ->first();
        if (! $bucket || bccomp((string) $bucket->balance, '0', 2) <= 0) {
            return $remaining;
        }
        $take = bccomp((string) $bucket->balance, $remaining, 2) >= 0 ? $remaining : (string) $bucket->balance;
        $bucket->balance = bcsub((string) $bucket->balance, $take, 2);
        $bucket->save();

        return bcsub($remaining, $take, 2);
    }

    private function totalAvailable(ApartmentWallet $wallet): string
    {
        return bcadd((string) $wallet->balance, (string) $wallet->buckets->sum('balance'), 2);
    }

    private function log(
        ApartmentWallet $wallet,
        string $direction,
        string $type,
        string $amount,
        ?string $feeCategory,
        ?int $feeTypeId,
        $reference,
        ?string $description,
        ?int $userId,
    ): ApartmentWalletTransaction {
        return ApartmentWalletTransaction::create([
            'tenant_id' => $wallet->tenant_id,
            'wallet_id' => $wallet->id,
            'apartment_id' => $wallet->apartment_id,
            'direction' => $direction,
            'type' => $type,
            'fee_category' => $feeCategory,
            'fee_type_id' => $feeTypeId,
            'amount' => $amount,
            'balance_after' => $this->totalAvailable($wallet->fresh(['buckets'])),
            'reference_type' => $reference ? $reference->getMorphClass() : null,
            'reference_id' => $reference?->getKey(),
            'description' => $description,
            'status' => 'confirmed',
            'posted_at' => now(),
            'created_by' => $userId,
        ]);
    }
}
