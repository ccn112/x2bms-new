<?php

namespace App\Http\Controllers\Api\V1\Resident;

use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Resources\Api\V1\PaymentResource;
use App\Models\Payment;
use App\Services\Resident\ResidentContextService;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Lịch sử thanh toán của cư dân (tab Hoá đơn — CD-PAY-05). Scope theo căn của user
 * (`apartment_id ∈ apartmentIds`) hoặc resident của user. Xem
 * docs/contracts/RESIDENT_API_DOMAIN.md §3 (P3).
 *
 * KHỞI TẠO thanh toán (POST) chờ owner chốt cổng thanh toán (VietQR/VNPay…) — xem §5.
 */
class PaymentController extends ApiController
{
    public function __construct(private readonly ResidentContextService $context)
    {
    }

    /** GET /resident/payments?cursor= — lịch sử, mới nhất trước. */
    public function index(Request $request): JsonResponse
    {
        $apartmentIds = $this->context->apartmentIds($request->user(), $request->header('X-Context-Id'));
        $residentIds = $request->user()->residentMemberships()->pluck('id')->all();

        if (empty($apartmentIds) && empty($residentIds)) {
            return ApiResponse::paginated([], null);
        }

        $perPage = min((int) $request->integer('per_page', 20), 50);

        $paginator = Payment::query()
            ->with('method')
            ->where(function ($q) use ($apartmentIds, $residentIds) {
                if (! empty($apartmentIds)) {
                    $q->orWhereIn('apartment_id', $apartmentIds);
                }
                if (! empty($residentIds)) {
                    $q->orWhereIn('resident_id', $residentIds);
                }
            })
            ->orderByDesc('paid_at')
            ->orderByDesc('id')
            ->cursorPaginate($perPage);

        $items = PaymentResource::collection($paginator->getCollection())->resolve($request);

        return ApiResponse::paginated($items, $paginator->nextCursor()?->encode());
    }

    /** GET /resident/payments/{payment} — chi tiết + phân bổ (allocations). */
    public function show(Request $request, Payment $payment): JsonResponse
    {
        $apartmentIds = $this->context->apartmentIds($request->user(), $request->header('X-Context-Id'));
        $residentIds = $request->user()->residentMemberships()->pluck('id')->all();

        $owns = in_array($payment->apartment_id, $apartmentIds, true)
            || ($payment->resident_id !== null && in_array($payment->resident_id, $residentIds, true));
        if (! $owns) {
            return ApiResponse::error('not_found', 'Không tìm thấy thanh toán.', 404);
        }

        $payment->load(['method', 'allocations', 'receipt']);

        return ApiResponse::success(PaymentResource::make($payment)->resolve($request));
    }
}
