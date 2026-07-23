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

    /**
     * GET /api/v1/resident/billing/summary/trend?months=6
     * Xu hướng tổng phí theo tháng của căn hộ người dùng (biểu đồ CD-PAY-01).
     * Gộp statements theo billing_period, cộng total_amount (bcmath), N tháng gần nhất.
     */
    public function trend(Request $request): JsonResponse
    {
        $apartmentIds = $this->context->apartmentIds($request->user(), $request->header('X-Context-Id'));
        if (empty($apartmentIds)) {
            return ApiResponse::success(['bars' => []]);
        }

        $months = min(max((int) $request->integer('months', 6), 1), 12);

        $rows = Statement::query()
            ->with('billingPeriod')
            ->whereIn('apartment_id', $apartmentIds)
            ->whereNotNull('billing_period_id')
            ->get(['id', 'billing_period_id', 'total_amount']);

        $byPeriod = [];
        foreach ($rows as $s) {
            $bp = $s->billingPeriod;
            if ($bp === null || $bp->period_month === null) {
                continue;
            }
            $key = $bp->period_month->format('Y-m');
            if (! isset($byPeriod[$key])) {
                $byPeriod[$key] = [
                    'month' => $bp->period_month,
                    'label' => $bp->period_month->format('m/y'),
                    'sum' => '0',
                ];
            }
            $byPeriod[$key]['sum'] = bcadd($byPeriod[$key]['sum'], (string) ($s->total_amount ?? '0'), 2);
        }

        // Sắp theo tháng tăng dần, lấy N tháng gần nhất.
        ksort($byPeriod);
        $bars = array_slice(array_values($byPeriod), -$months);

        return ApiResponse::success([
            'bars' => array_map(fn ($b) => [
                'label' => $b['label'],
                'value' => $this->trimMoney($b['sum']),
            ], $bars),
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
