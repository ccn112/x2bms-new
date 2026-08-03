<?php

namespace App\Services\Billing;

use App\Enums\BillingFamily;
use App\Models\Meter;
use App\Models\Statement;
use App\Models\StatementLine;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\Resident\ResidentContextService;
use Illuminate\Support\Carbon;

/**
 * Công nợ THEO DỊCH VỤ / TÀI SẢN (D6). Gom các dòng phí CÒN NỢ của căn hộ thành
 * cây 3 cấp: family (fee_category) › fee_type › tài sản (subject: xe/đồng hồ/…),
 * dưới mỗi tài sản là các THÁNG đang nợ (mỗi dòng phí = một kỳ dịch vụ). Cho phép
 * cư dân "trả trước nhiều tháng cho một xe" mà không phải mở từng bảng kê.
 *
 * Tiền để dạng CHUỖI DECIMAL, cộng bằng bcadd — không float.
 */
class DebtByServiceService
{
    public function __construct(
        private readonly ResidentContextService $context,
        private readonly ChargeSelectability $selectability,
    ) {}

    private const FAMILY_LABEL = [
        'management' => 'Phí quản lý',
        'parking' => 'Phương tiện',
        'vehicle' => 'Phương tiện',
        'utility' => 'Điện nước',
        'electric' => 'Điện',
        'water' => 'Nước',
        'service' => 'Dịch vụ',
        'other' => 'Khác',
    ];

    /**
     * @param  array{family?:?string, from?:?string, to?:?string}  $filters  Lọc FIN-12:
     *   `family` = một trong 5 billing family; `from`/`to` = khoảng `service_period_start`
     *   (YYYY-MM-DD). Bỏ trống = không lọc.
     * @return array{families: array, total_outstanding: string, filter: array}
     */
    public function tree(User $user, ?string $contextId, array $filters = []): array
    {
        $family = $filters['family'] ?? null;
        $from = $filters['from'] ?? null;
        $to = $filters['to'] ?? null;

        $apartmentIds = $this->context->apartmentIds($user, $contextId);
        if (empty($apartmentIds)) {
            return ['families' => [], 'total_outstanding' => '0.00', 'filter' => compact('family', 'from', 'to')];
        }

        $lines = StatementLine::query()
            ->whereHas('statement', fn ($q) => $q
                ->visibleToResident()->whereIn('apartment_id', $apartmentIds))
            ->outstanding()
            ->when($family !== null && $family !== '' && $family !== 'all',
                fn ($q) => $q->where('fee_category', $family))
            ->when($from !== null && $from !== '',
                fn ($q) => $q->where('service_period_start', '>=', $from))
            ->when($to !== null && $to !== '',
                fn ($q) => $q->where('service_period_start', '<=', $to))
            ->with(['feeType:id,name,unit', 'subject', 'statement:id,apartment_id,billing_period_id'])
            ->with('statement.billingPeriod:id,period_month')
            ->orderBy('service_period_start')
            ->get();

        // A1: nạp sẵn các kỳ chủ-cũ cho mọi căn để đánh cờ non-selectable không N+1.
        $formerPeriods = $this->selectability->formerOwnerPeriods($apartmentIds);

        // Gom: family → fee_type → subject → [tháng nợ].
        $families = [];
        $total = '0.00';

        foreach ($lines as $line) {
            $out = $line->outstanding();
            if (bccomp($out, '0', 2) <= 0) {
                continue;
            }
            $total = bcadd($total, $out, 2);

            $famKey = (string) ($line->fee_category ?: 'other');
            $ftId = (string) ($line->fee_type_id ?: '0');
            [$subjKey, $subjId, $subjLabel, $subjSub] = $this->resolveSubject($line);
            $groupKey = $ftId.'|'.$subjKey.'|'.$subjId;

            $families[$famKey] ??= [
                'family' => $famKey,
                'label' => BillingFamily::tryFrom($famKey)?->label()
                    ?? self::FAMILY_LABEL[$famKey] ?? ucfirst($famKey),
                'priority' => BillingFamily::tryFrom($famKey)?->defaultPriority() ?? 950,
                'outstanding' => '0.00',
                '_ft' => [],
            ];
            $families[$famKey]['outstanding'] = bcadd($families[$famKey]['outstanding'], $out, 2);

            $ftBucket = &$families[$famKey]['_ft'];
            $ftBucket[$ftId] ??= [
                'fee_type_id' => $ftId === '0' ? null : $ftId,
                'name' => $line->feeType?->name ?? ($line->description ?: 'Phí'),
                'unit' => $line->feeType?->unit,
                'outstanding' => '0.00',
                '_subj' => [],
            ];
            $ftBucket[$ftId]['outstanding'] = bcadd($ftBucket[$ftId]['outstanding'], $out, 2);

            $subj = &$ftBucket[$ftId]['_subj'];
            $subj[$groupKey] ??= [
                'subject_type' => $subjKey,
                'subject_id' => $subjId,
                'label' => $subjLabel,
                'sublabel' => $subjSub,
                'outstanding' => '0.00',
                'months' => [],
            ];
            $subj[$groupKey]['outstanding'] = bcadd($subj[$groupKey]['outstanding'], $out, 2);
            $sel = $this->selectability->evaluate($line, $line->statement?->apartment_id, $formerPeriods);
            $subj[$groupKey]['months'][] = [
                'line_id' => (string) $line->id,
                'statement_id' => (string) $line->statement_id,
                'period' => $this->periodLabel($line),
                'service_period_start' => optional($this->asDate($line->service_period_start))->toDateString(),
                'due_date' => optional($this->asDate($line->due_date))->toDateString(),
                'amount' => (string) $line->amount,
                'paid' => (string) ($line->paid_amount ?? 0),
                'outstanding' => $out,
                'selectable' => $sel['selectable'],
                'non_selectable_reason' => $sel['reason'],
                'non_selectable_label' => $sel['label'],
            ];
        }

        // Sắp family theo thứ tự canonical (management→water→electricity→vehicle→other).
        uasort($families, fn ($a, $b) => $a['priority'] <=> $b['priority']);

        // Bỏ khoá tạm (_ft/_subj) → mảng tuần tự.
        $out = [];
        foreach ($families as $fam) {
            $fts = [];
            foreach ($fam['_ft'] as $ft) {
                $ft['subjects'] = array_values($ft['_subj']);
                unset($ft['_subj']);
                $fts[] = $ft;
            }
            $fam['fee_types'] = $fts;
            unset($fam['_ft'], $fam['priority']);
            $out[] = $fam;
        }

        return ['families' => $out, 'total_outstanding' => $total, 'filter' => compact('family', 'from', 'to')];
    }

    /** @return array{0:string,1:?string,2:string,3:?string} [key, id, label, sublabel] */
    private function resolveSubject(StatementLine $line): array
    {
        $s = $line->subject;
        if ($s instanceof Vehicle) {
            return ['vehicle', (string) $s->id, $s->plate_no ?: 'Xe', $s->type?->label() ?? 'Phương tiện'];
        }
        if ($s instanceof Meter) {
            $code = $s->code ?? $s->serial ?? $s->meter_no ?? ('Đồng hồ #'.$s->id);
            return ['meter', (string) $s->id, (string) $code, 'Đồng hồ'];
        }
        // Không gắn tài sản (phí quản lý/vệ sinh…) → gộp theo chính căn hộ.
        return ['apartment', null, $line->feeType?->name ?? 'Căn hộ', null];
    }

    private function periodLabel(StatementLine $line): string
    {
        // `service_period_start` là cột date NHƯNG StatementLine không cast nó (đường import
        // dựa vào việc đọc ra chuỗi thô), nên tự parse ở đây. `period_month` thì có cast.
        $d = $this->asDate($line->service_period_start) ?? $line->statement?->billingPeriod?->period_month;

        return $d ? $d->format('m/Y') : '—';
    }

    /** Chuỗi/Carbon/null → ?Carbon. Dòng phí không cast date nên nhận cả hai kiểu. */
    private function asDate(mixed $value): ?Carbon
    {
        if ($value === null || $value === '') {
            return null;
        }

        return $value instanceof Carbon ? $value : Carbon::parse($value);
    }
}
