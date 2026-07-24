<?php

namespace App\Http\Controllers\Api\V1\Resident;

use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Resources\Api\V1\OfferResource;
use App\Services\Resident\VoucherVisibilityService;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * GET /api/v1/resident/offers — ưu đãi (voucher không cần đổi điểm) hiển thị cho
 * cư dân theo tenant của họ (∪ voucher platform đã rollout). Xem
 * docs/contracts/RESIDENT_API_DOMAIN.md §3.
 */
class OfferController extends ApiController
{
    public function __construct(private readonly VoucherVisibilityService $vouchers)
    {
    }

    /** GET /api/v1/resident/offers?cursor= */
    public function index(Request $request): JsonResponse
    {
        $perPage = min((int) $request->integer('per_page', 20), 50);

        $paginator = $this->vouchers
            ->visibleQuery($request->user(), $request->header('X-Context-Id'))
            ->where(function ($q) {
                $q->whereNull('points_cost')->orWhere('points_cost', '=', 0);
            })
            ->orderByDesc('valid_from')
            ->orderByDesc('id')
            ->cursorPaginate($perPage);

        $items = OfferResource::collection($paginator->getCollection())->resolve($request);

        return ApiResponse::paginated($items, $paginator->nextCursor()?->encode());
    }
}
