<?php

namespace App\Http\Controllers\Api\V1\Resident;

use App\Http\Controllers\Api\V1\ApiController;
use App\Models\StatementLine;
use App\Services\Billing\AllocationPreviewService;
use App\Services\Resident\ResidentContextService;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * P4 — PREVIEW phân bổ TRƯỚC khi thanh toán (canonical: "preview allocation bắt
 * buộc"). Cư dân chọn các dòng phí + số tiền → thấy TRƯỚC mỗi dòng sẽ được trừ bao
 * nhiêu, phần thừa còn lại. KHÔNG ghi gì — chỉ tính.
 *
 * Cùng luật với đường ghi thật (claim/ví): thứ tự `allocationSortKey`, không phân
 * bổ vượt phần còn nợ. Tiền vào/ra là SỐ NGUYÊN ĐỒNG.
 */
class PaymentPreviewController extends ApiController
{
    public function __construct(
        private readonly ResidentContextService $context,
        private readonly AllocationPreviewService $preview,
    ) {}

    /** POST /api/v1/resident/billing/payment-preview */
    public function preview(Request $request): JsonResponse
    {
        $data = $request->validate([
            'line_ids' => ['required', 'array', 'min:1'],
            'line_ids.*' => ['integer'],
            'amount' => ['required', 'integer', 'min:1000', 'max:5000000000'],
        ]);

        $user = $request->user();
        $apartmentIds = $this->context->apartmentIds($user, $request->header('X-Context-Id'));
        if (empty($apartmentIds)) {
            return ApiResponse::error('no_apartment', 'Chưa xác định được căn hộ của bạn.', 422);
        }

        $lineIds = array_values(array_unique(array_map('intval', $data['line_ids'])));
        $lines = StatementLine::query()
            ->whereIn('id', $lineIds)
            ->with(['feeType', 'statement.building'])
            ->get();

        if ($lines->count() !== count($lineIds)) {
            return ApiResponse::error('line_not_found', 'Có dòng phí không tồn tại.', 404);
        }

        // Quyền: mọi dòng phải thuộc căn của chính cư dân.
        foreach ($lines as $line) {
            if (! in_array($line->statement?->apartment_id, $apartmentIds, true)) {
                return ApiResponse::error('forbidden', 'Bạn không phải cư dân của căn hộ này.', 403);
            }
        }

        $sorted = $lines->sortBy(fn (StatementLine $l) => $l->allocationSortKey())->values();
        $result = $this->preview->preview($sorted, (string) $data['amount']);

        return ApiResponse::success([
            'amount' => (int) $data['amount'],
            'allocated' => $this->dong($result['allocated']),
            'unallocated' => $this->dong($result['unallocated']),
            'lines' => $sorted->map(fn (StatementLine $l) => [
                'line_id' => (string) $l->id,
                'fee_type' => $l->fee_type,
                'fee_category' => $l->fee_category,
                'outstanding' => $this->dong($l->outstanding()),
                'allocated' => $this->dong($result['per_line'][$l->id] ?? '0'),
            ])->all(),
        ]);
    }

    private function dong(string $decimal): int
    {
        return (int) round((float) $decimal);
    }
}
