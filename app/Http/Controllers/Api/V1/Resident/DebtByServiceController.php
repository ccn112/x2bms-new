<?php

namespace App\Http\Controllers\Api\V1\Resident;

use App\Http\Controllers\Api\V1\ApiController;
use App\Services\Billing\DebtByServiceService;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Công nợ THEO DỊCH VỤ / TÀI SẢN (D6) — màn cư dân xem nợ gom theo
 * family (fee_category) › fee_type › tài sản (xe/đồng hồ/…) › các tháng nợ.
 *
 * Con số ở đây đi qua cùng scope `Statement::scopeVisibleToResident` (quyết định D1)
 * như `GET resident/statements` và `GET resident/billing/summary` — chỉ bảng kê đã
 * phát hành mới được tính, để ba chỗ không lệch nhau.
 *
 * Căn hộ rỗng (chưa có quan hệ / chưa có bảng kê) → service tự trả `families: []`.
 */
class DebtByServiceController extends ApiController
{
    public function __construct(private readonly DebtByServiceService $debts) {}

    /** GET /api/v1/resident/debts/by-service?family=&from=&to= */
    public function show(Request $request): JsonResponse
    {
        $data = $request->validate([
            'family' => ['nullable', 'string', 'in:all,management,electricity,water,vehicle,other'],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d'],
        ]);

        $tree = $this->debts->tree(
            $request->user(),
            $request->header('X-Context-Id'),
            ['family' => $data['family'] ?? null, 'from' => $data['from'] ?? null, 'to' => $data['to'] ?? null],
        );

        return ApiResponse::success($tree);
    }
}
