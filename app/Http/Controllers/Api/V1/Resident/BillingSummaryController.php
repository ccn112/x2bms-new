<?php

namespace App\Http\Controllers\Api\V1\Resident;

use App\Http\Controllers\Api\V1\ApiController;
use App\Models\Statement;
use App\Services\Resident\ResidentContextService;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Công nợ tổng hợp cho card Tiện ích — tránh app phải tải hết statements để cộng.
 * Chưa trả = status != 'paid'; công nợ = Σ max(total_amount - paid_amount, 0).
 * Tiền là chuỗi decimal (không float).
 */
class BillingSummaryController extends ApiController
{
    public function __construct(private readonly ResidentContextService $context) {}

    /** GET /api/v1/resident/billing/summary */
    public function show(Request $request): JsonResponse
    {
        $apartmentIds = $this->context->apartmentIds($request->user(), $request->header('X-Context-Id'));

        if (empty($apartmentIds)) {
            return ApiResponse::success($this->emptySummary());
        }

        $unpaid = Statement::query()
            ->whereIn('apartment_id', $apartmentIds)
            ->where('status', '!=', 'paid')
            ->get(['total_amount', 'paid_amount', 'due_date']);

        $debt = '0';
        $count = 0;
        $dueDate = null;
        foreach ($unpaid as $s) {
            $outstanding = bcsub((string) ($s->total_amount ?? '0'), (string) ($s->paid_amount ?? '0'), 2);
            if (bccomp($outstanding, '0', 2) <= 0) {
                continue; // đã trả đủ dù status chưa cập nhật
            }
            $debt = bcadd($debt, $outstanding, 2);
            $count++;
            if ($s->due_date !== null && ($dueDate === null || $s->due_date->lt($dueDate))) {
                $dueDate = $s->due_date;
            }
        }

        return ApiResponse::success([
            'current_debt' => $this->trimMoney($debt),
            'currency' => 'VND',
            'due_date' => $dueDate?->toDateString(),
            'unpaid_statement_count' => $count,
            'as_of' => now()->toIso8601String(),
        ]);
    }

    private function emptySummary(): array
    {
        return [
            'current_debt' => '0',
            'currency' => 'VND',
            'due_date' => null,
            'unpaid_statement_count' => 0,
            'as_of' => now()->toIso8601String(),
        ];
    }

    /** "17320000.00" → "17320000"; giữ phần thập phân nếu có. */
    private function trimMoney(string $v): string
    {
        if (! str_contains($v, '.')) {
            return $v;
        }

        return rtrim(rtrim($v, '0'), '.');
    }
}
