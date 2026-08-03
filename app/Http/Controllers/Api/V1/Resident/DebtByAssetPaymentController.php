<?php

namespace App\Http\Controllers\Api\V1\Resident;

use App\Http\Controllers\Api\V1\ApiController;
use App\Models\Apartment;
use App\Models\Meter;
use App\Models\StatementLine;
use App\Models\Vehicle;
use App\Services\Billing\ChargeSelectability;
use App\Services\Resident\ApartmentWalletService;
use App\Services\Resident\ResidentContextService;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * D6 Slice B — THANH TOÁN công nợ theo TÀI SẢN (claim-by-asset).
 *
 * Cư dân chọn các THÁNG còn nợ của MỘT tài sản (vd chiếc xe 51K-838888) rồi trả
 * trước. Tiền: (1) phân bổ vào đúng các dòng đã chọn theo
 * `StatementLine::allocationSortKey()` — khoá phân bổ DÙNG CHUNG, không tự chọn
 * thứ tự khác; (2) phần thừa chảy vào NGĂN ví theo chiều tài sản để lần sau tự
 * trừ tiếp cho đúng tài sản đó. Toàn bộ hạch toán tiền nằm trong
 * `ApartmentWalletService::settleAssetLines()` — controller chỉ xác thực quyền.
 *
 * Tiền vào/ra API với app là SỐ NGUYÊN ĐỒNG; nội bộ money đi qua bcmath/decimal.
 */
class DebtByAssetPaymentController extends ApiController
{
    public function __construct(
        private readonly ResidentContextService $context,
        private readonly ApartmentWalletService $wallets,
        private readonly ChargeSelectability $selectability,
    ) {}

    /** POST /api/v1/resident/debts/by-service/pay */
    public function pay(Request $request): JsonResponse
    {
        $data = $request->validate([
            // TÙY CHỌN: có → trả trước theo MỘT tài sản (earmark dư vào ngăn tài sản);
            // KHÔNG có → thanh toán XUYÊN nhiều dịch vụ/tháng/tài sản (dư vào quỹ chung).
            'subject_type' => ['nullable', 'string', 'in:vehicle,meter,apartment'],
            'subject_id' => ['nullable', 'integer'],
            'line_ids' => ['required', 'array', 'min:1'],
            'line_ids.*' => ['integer'],
            // Đồng (integer); app gửi số nguyên đồng.
            'amount' => ['required', 'integer', 'min:1000', 'max:5000000000'],
        ]);

        $user = $request->user();
        $apartmentIds = $this->context->apartmentIds($user, $request->header('X-Context-Id'));
        if (empty($apartmentIds)) {
            return ApiResponse::error('no_apartment', 'Chưa xác định được căn hộ của bạn.', 422);
        }

        $subjectType = $data['subject_type'] ?? null;
        $subjectId = $subjectType === 'apartment' ? null : ($data['subject_id'] ?? null);
        if ($subjectType !== null && $subjectType !== 'apartment' && $subjectId === null) {
            return ApiResponse::error('subject_id_required',
                'Thiếu mã tài sản cần thanh toán.', 422);
        }

        $lineIds = array_values(array_unique(array_map('intval', $data['line_ids'])));

        // Nạp dòng TRƯỚC khi lọc theo căn để phân biệt "không tồn tại" (404) với
        // "không phải căn của bạn" (403). `StatementLine` không mang tenant scope
        // (cư dân tenant_id = NULL) nên tra thẳng theo id là đúng.
        $lines = StatementLine::query()
            ->whereIn('id', $lineIds)
            ->with(['feeType', 'subject', 'statement.building'])
            ->get();

        if ($lines->count() !== count($lineIds)) {
            return ApiResponse::error('line_not_found',
                'Có dòng phí không tồn tại.', 404);
        }

        // Quyền: MỌI dòng phải thuộc một căn của chính cư dân — nếu không thì đây là
        // người không phải cư dân của căn đang trả (hoặc trả nhầm căn khác).
        foreach ($lines as $line) {
            if (! in_array($line->statement?->apartment_id, $apartmentIds, true)) {
                return ApiResponse::error('forbidden',
                    'Bạn không phải cư dân của căn hộ này.', 403);
            }
        }

        // A1: KHÔNG tin mỗi UI — dòng đã trả / nợ chủ cũ thì server từ chối dù client
        // vẫn gửi (cùng một ChargeSelectability với lúc hiển thị nên không lệch nhau).
        $formerPeriods = $this->selectability->formerOwnerPeriods($apartmentIds);
        foreach ($lines as $line) {
            $sel = $this->selectability->evaluate($line, $line->statement?->apartment_id, $formerPeriods);
            if (! $sel['selectable']) {
                return ApiResponse::error('charge_not_selectable',
                    'Có khoản không thể thanh toán ('.$sel['label'].').', 422);
            }
        }

        // Một lượt trả cho MỘT căn hộ (dù nhiều dịch vụ/tháng).
        $first = $lines->first();
        $apartmentId = (int) $first->statement->apartment_id;
        $apartment = Apartment::query()->whereIn('id', $apartmentIds)->whereKey($apartmentId)->first();
        if ($apartment === null) {
            return ApiResponse::error('forbidden', 'Bạn không phải cư dân của căn hộ này.', 403);
        }
        foreach ($lines as $line) {
            if ((int) $line->statement?->apartment_id !== $apartmentId) {
                return ApiResponse::error('mixed_apartment',
                    'Chỉ chọn các khoản của cùng một căn hộ trong một lần thanh toán.', 422);
            }
        }
        $wallet = $this->wallets->walletFor($apartment);

        // Thứ tự phân bổ DÙNG CHUNG — building đã eager-load để không N+1 khi sắp.
        $sorted = $lines->sortBy(fn (StatementLine $l) => $l->allocationSortKey())->values();

        if ($subjectType !== null) {
            // TRẢ TRƯỚC theo MỘT tài sản (D6): dồn cùng tài sản + cùng loại phí, dư
            // earmark vào ngăn tài sản để lần sau tự trừ đúng tài sản đó.
            foreach ($lines as $line) {
                [$type, $id] = $this->subjectRef($line);
                if ($type !== $subjectType || (string) $id !== (string) $subjectId) {
                    return ApiResponse::error('subject_mismatch',
                        'Có dòng phí không thuộc tài sản đã chọn.', 422);
                }
            }
            if ($lines->pluck('fee_type_id')->unique()->count() > 1) {
                return ApiResponse::error('mixed_fee_type',
                    'Chỉ chọn các tháng của cùng một loại phí khi trả trước theo tài sản.', 422);
            }
            $result = $this->wallets->settleAssetLines(
                $wallet, $sorted, (string) $data['amount'],
                (string) ($first->fee_category ?? $first->feeType?->category ?? 'other'),
                $first->fee_type_id !== null ? (int) $first->fee_type_id : null,
                $first->subject_type,
                $first->subject_id !== null ? (int) $first->subject_id : null,
                $user->id,
            );
            $overflow = $result['bucket_balance'];
        } else {
            // THANH TOÁN XUYÊN nhiều dịch vụ/tháng/tài sản — phần dư vào quỹ chung.
            $result = $this->wallets->settleLinesAcross($wallet, $sorted, (string) $data['amount'], $user->id);
            $overflow = $result['overflow'];
        }

        return ApiResponse::success([
            'allocated' => $this->dong($result['allocated']),
            'overflow' => $this->dong($overflow),
            'subject_type' => $subjectType,
            'subject_id' => $subjectId !== null ? (string) $subjectId : null,
            'lines' => $sorted->map(fn (StatementLine $l) => [
                'line_id' => (string) $l->id,
                'allocated' => $this->dong($result['per_line'][$l->id] ?? '0'),
                'outstanding' => $this->dong($l->outstanding()),
            ])->all(),
        ]);
    }

    /** Chuỗi decimal → SỐ NGUYÊN đồng (VND không có phần lẻ). */
    private function dong(string $decimal): int
    {
        return (int) round((float) $decimal);
    }

    /**
     * Tài sản của dòng phí về khoá thân thiện (khớp `DebtByServiceService`):
     * xe → 'vehicle', đồng hồ → 'meter', không gắn tài sản → 'apartment'.
     *
     * @return array{0:string, 1:?string} [type, id]
     */
    private function subjectRef(StatementLine $line): array
    {
        $s = $line->subject;
        if ($s instanceof Vehicle) {
            return ['vehicle', (string) $s->id];
        }
        if ($s instanceof Meter) {
            return ['meter', (string) $s->id];
        }

        return ['apartment', null];
    }
}
