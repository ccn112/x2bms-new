<?php

declare(strict_types=1);

namespace App\Services\Billing;

use App\Models\LiabilityPeriod;
use App\Models\StatementLine;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * A1 — Vì sao một dòng phí (charge) KHÔNG được cư dân tick chọn để trả.
 *
 * NGUỒN QUYỀN LỰC DUY NHẤT cho câu hỏi "khoản này có trả được không". Cả hai màn
 * (chi tiết bảng kê FIN-11 và công nợ theo dịch vụ D6) lẫn endpoint thanh toán
 * `debts/by-service/pay` đều đi qua đây — không màn nào tự phán, không tin mỗi UI
 * (rule x2bms-api-flutter). Trả được → app cho tick; không → app khoá tick + hiện
 * lý do; server thì TỪ CHỐI nếu client vẫn cố gửi (enforcement, không chỉ hiển thị).
 *
 * Lý do có DỮ LIỆU THẬT hôm nay:
 *   - `paid`         : dòng đã trả hết (`outstanding <= 0`). (Cây D6 đã lọc outstanding
 *                      nên chỉ gặp ở chi tiết bảng kê, nơi hiện cả dòng đã trả.)
 *   - `former_owner` : `service_period_start` của dòng rơi vào một `liability_periods`
 *                      role=`former_owner` phủ family tương ứng — nợ thuộc chủ cũ,
 *                      không đẩy sang chủ mới (D11/D12). Cơ chế đúng, sẵn cho Phase 5;
 *                      hôm nay thường rỗng vì backfill chỉ tạo kỳ "owner, mở".
 *
 * CHƯA phát ra (không có nơi sản sinh dữ liệu — để ngỏ chờ owner + luồng BQL):
 *   - `disputed`     : chưa có cột/quy trình BQL đánh dấu dòng phí tranh chấp. Thêm cột
 *                      không có writer = scope creep (rule x2-no-scope-creep). Backlog.
 */
class ChargeSelectability
{
    /** Nhãn cư dân theo lý do — do SERVER trả, app không tự dịch. */
    public const LABELS = [
        'paid' => 'Đã thanh toán',
        'former_owner' => 'Nợ của chủ cũ',
        'disputed' => 'Đang tranh chấp',
    ];

    /**
     * Nạp SẴN các kỳ chịu-trách-nhiệm role=former_owner cho một tập căn hộ, gom theo
     * apartment_id — để đánh giá nhiều dòng không phát sinh N+1. Cư dân có tenant_id
     * NULL nên bỏ global scope tenant, tra thẳng theo apartment_id (đúng mẫu billing
     * cư dân dùng khắp nơi).
     *
     * @param  int[]  $apartmentIds
     * @return Collection<int, Collection<int, LiabilityPeriod>>  keyed by apartment_id
     */
    public function formerOwnerPeriods(array $apartmentIds): Collection
    {
        if (empty($apartmentIds)) {
            return collect();
        }

        return LiabilityPeriod::withoutGlobalScopes()
            ->whereIn('apartment_id', $apartmentIds)
            ->where('role', 'former_owner')
            ->whereNull('deleted_at')
            ->get()
            ->groupBy('apartment_id');
    }

    /**
     * @param  Collection<int, Collection<int, LiabilityPeriod>>  $formerByApartment
     * @return array{selectable:bool, reason:?string, label:?string}
     */
    public function evaluate(StatementLine $line, ?int $apartmentId, Collection $formerByApartment): array
    {
        if (bccomp($line->outstanding(), '0', 2) <= 0) {
            return self::blocked('paid');
        }

        if ($apartmentId !== null && $this->coveredByFormerOwner($line, $formerByApartment->get($apartmentId))) {
            return self::blocked('former_owner');
        }

        return ['selectable' => true, 'reason' => null, 'label' => null];
    }

    /** @param  Collection<int, LiabilityPeriod>|null  $periods */
    private function coveredByFormerOwner(StatementLine $line, ?Collection $periods): bool
    {
        if ($periods === null || $periods->isEmpty()) {
            return false;
        }

        $at = $this->servicePeriod($line);
        if ($at === null) {
            return false;   // không suy được kỳ dịch vụ → không dám khoá (mặc định trả được)
        }

        $family = (string) ($line->fee_category ?: 'other');

        foreach ($periods as $p) {
            if (! $p->coversFamily($family)) {
                continue;
            }
            $from = $p->liable_from;
            $to = $p->liable_to;                 // NULL = còn mở (kéo tới hiện tại)
            if ($from !== null && $at->lt($from)) {
                continue;
            }
            if ($to !== null && $at->gt($to)) {
                continue;
            }

            return true;
        }

        return false;
    }

    /** `service_period_start` không được cast ở model → tự parse (chuỗi/Carbon/null). */
    private function servicePeriod(StatementLine $line): ?Carbon
    {
        $value = $line->service_period_start;
        if ($value === null || $value === '') {
            return null;
        }

        return $value instanceof Carbon ? $value : Carbon::parse($value);
    }

    /** @return array{selectable:bool, reason:string, label:string} */
    private static function blocked(string $reason): array
    {
        return [
            'selectable' => false,
            'reason' => $reason,
            'label' => self::LABELS[$reason] ?? $reason,
        ];
    }
}
