<?php

declare(strict_types=1);

namespace App\Services\Billing;

use App\Enums\BillingFamily;
use App\Models\Statement;
use App\Models\StatementLine;
use Illuminate\Support\Facades\DB;

/**
 * Dựng cấu trúc CHI TIẾT bảng kê theo 5 BILLING FAMILY cho app cư dân (FIN-11).
 *
 * Mỗi family một section, đúng thứ tự hiển thị `management → electricity → water →
 * vehicle → other`; trong family gom theo fee_definition (loại phí) → subject (tài
 * sản) → dòng phí. Bốn cột tiền mỗi family: nợ trước (previous_debt, gom từ các
 * bảng kê ĐÃ PHÁT HÀNH kỳ trước còn nợ), phát sinh kỳ (current_period), đã phân bổ
 * (allocated = paid_amount projection từ ledger — P1a), còn nợ (outstanding).
 *
 * Tiền xuất SỐ NGUYÊN ĐỒNG (chuỗi). Điện/nước kèm chỉ số từ `calculation_snapshot`.
 * KHÔNG tính lại tiền — chỉ trình bày số đã có (số kế toán là chuẩn, D9).
 */
class StatementDetailPresenter
{
    /** Thứ tự hiển thị family theo content contract FIN-11. */
    private const DISPLAY_ORDER = ['management', 'electricity', 'water', 'vehicle', 'other'];

    /** A1 — kỳ chủ-cũ của căn đang xem (nạp một lần/statement). */
    private \Illuminate\Support\Collection $formerPeriods;

    private ?int $apartmentId = null;

    public function __construct(private readonly ChargeSelectability $selectability = new ChargeSelectability) {}

    /** @return list<array<string,mixed>> */
    public function familiesFor(Statement $statement): array
    {
        $statement->loadMissing(['lines.feeType', 'billingPeriod']);
        $lines = $statement->lines;
        $previousDebt = $this->previousDebtByFamily($statement);

        $this->apartmentId = $statement->apartment_id;
        $this->formerPeriods = $this->selectability->formerOwnerPeriods([$statement->apartment_id]);

        $sections = [];
        foreach (self::DISPLAY_ORDER as $familyCode) {
            $familyLines = $lines->filter(fn (StatementLine $l) => ($l->fee_category ?? 'other') === $familyCode);
            $prev = $previousDebt[$familyCode] ?? 0;

            // Ẩn family rỗng (không phát sinh kỳ này VÀ không nợ trước).
            if ($familyLines->isEmpty() && $prev <= 0) {
                continue;
            }

            $current = 0;
            $allocated = 0;
            foreach ($familyLines as $l) {
                $current += (int) round((float) $l->amount);
                $allocated += (int) round((float) ($l->paid_amount ?? 0));
            }
            $outstanding = max($current - $allocated, 0);

            $sections[] = [
                'code' => $familyCode,
                'label' => $this->label($familyCode),
                'priority' => $this->priority($familyCode),
                'amounts' => [
                    'previous_debt' => (string) $prev,
                    'current_period' => (string) $current,
                    'allocated' => (string) $allocated,
                    'outstanding' => (string) ($outstanding + $prev),
                ],
                'fee_definitions' => $this->feeDefinitions($familyLines),
            ];
        }

        return $sections;
    }

    /** @param \Illuminate\Support\Collection<int,StatementLine> $familyLines */
    private function feeDefinitions($familyLines): array
    {
        $byDef = $familyLines->groupBy(fn (StatementLine $l) => $l->fee_type_id ?? ('name:'.$l->fee_type));

        return $byDef->map(function ($defLines) {
            $first = $defLines->first();

            return [
                'fee_type_id' => $first->fee_type_id,
                'label' => $first->fee_type,
                'lines' => $defLines->map(fn (StatementLine $l) => $this->lineArray($l))->values()->all(),
            ];
        })->values()->all();
    }

    private function lineArray(StatementLine $l): array
    {
        $amount = (int) round((float) $l->amount);
        $paid = (int) round((float) ($l->paid_amount ?? 0));
        $snapshot = is_array($l->calculation_snapshot) ? $l->calculation_snapshot : null;

        $row = [
            'id' => $l->id,
            'label' => $l->fee_type,
            'subject_ref' => $this->subjectRef($l, $snapshot),
            'service_period_start' => $this->dateStr($l->service_period_start),
            'service_period_end' => $this->dateStr($l->service_period_end),
            'quantity' => $l->quantity === null ? null : (string) $l->quantity,
            'unit_price' => $l->unit_price === null ? null : (string) (int) round((float) $l->unit_price),
            'amount' => (string) $amount,
            'paid_amount' => (string) $paid,
            'outstanding' => (string) max($amount - $paid, 0),
            'note' => $l->note,
        ];

        // A1: khoản có trả được không (paid/former_owner). App khoá tick + hiện lý do.
        $sel = $this->selectability->evaluate($l, $this->apartmentId, $this->formerPeriods);
        $row['selectable'] = $sel['selectable'];
        $row['non_selectable_reason'] = $sel['reason'];
        $row['non_selectable_label'] = $sel['label'];

        // Điện/nước: chỉ số đầu/cuối + tiêu thụ + bậc (nếu có trong snapshot).
        if ($snapshot !== null && ($snapshot['method'] ?? null) === 'metered') {
            $row['meter'] = [
                'previous_reading' => $snapshot['previous_reading'] ?? null,
                'current_reading' => $snapshot['current_reading'] ?? null,
                'consumption' => $snapshot['consumption'] ?? null,
                'tiers' => $snapshot['tiers'] ?? [],
            ];
        }

        return $row;
    }

    /** Nợ trước theo family: Σ còn nợ ở các bảng kê ĐÃ PHÁT HÀNH kỳ TRƯỚC của căn. */
    private function previousDebtByFamily(Statement $statement): array
    {
        $periodMonth = $statement->billingPeriod?->period_month;
        if ($periodMonth === null) {
            return [];
        }

        $rows = DB::table('statement_lines')
            ->join('statements', 'statements.id', '=', 'statement_lines.statement_id')
            ->join('billing_periods', 'billing_periods.id', '=', 'statements.billing_period_id')
            ->where('statements.apartment_id', $statement->apartment_id)
            ->where('statements.approval_status', 'published')
            ->where('statements.id', '!=', $statement->id)
            ->where('billing_periods.period_month', '<', $periodMonth->toDateString())
            ->whereNull('statement_lines.deleted_at')
            ->groupBy('statement_lines.fee_category')
            ->selectRaw('statement_lines.fee_category AS fam, SUM(statement_lines.amount) AS amt, SUM(statement_lines.paid_amount) AS paid')
            ->get();

        $out = [];
        foreach ($rows as $r) {
            $out[$r->fam ?? 'other'] = max((int) round((float) $r->amt) - (int) round((float) $r->paid), 0);
        }

        return $out;
    }

    private function subjectRef(StatementLine $l, ?array $snapshot): ?string
    {
        if ($l->subject_id !== null) {
            // Tài sản thật (xe/đồng hồ) — nhãn để app hiển thị "51K-838888" / "E-A0205-01".
            $subject = $l->relationLoaded('subject') ? $l->getRelation('subject') : null;

            return $subject?->plate_no ?? $subject?->code ?? (string) $l->subject_id;
        }

        return null;
    }

    private function label(string $code): string
    {
        return BillingFamily::tryFrom($code)?->label() ?? 'Phí khác';
    }

    private function priority(string $code): int
    {
        return BillingFamily::tryFrom($code)?->defaultPriority() ?? 900;
    }

    private function dateStr($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return $value instanceof \DateTimeInterface ? $value->format('Y-m-d') : substr((string) $value, 0, 10);
    }
}
