<?php

namespace App\Http\Controllers\Api\V1\Resident;

use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Resources\Api\V1\MarketProductResource;
use App\Http\Resources\Api\V1\RealEstateListingResource;
use App\Http\Resources\Api\V1\ServiceProviderResource;
use App\Models\MarketplaceProduct;
use App\Models\RealEstateListing;
use App\Models\ServiceProvider;
use App\Services\Resident\ResidentContextService;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Tab Chợ nội khu (CD-MK-*). 3 nguồn tách biệt:
 *   - listings  ← marketplace_products (scope project_id ∈ projectIds)
 *   - services  ← service_providers    (scope tenant_id ∈ tenantIds — bảng KHÔNG có project_id)
 *   - real-estate ← real_estate_listings (scope project_id ∈ projectIds, TÁCH riêng)
 * Xem docs/contracts/RESIDENT_API_DOMAIN.md §3.
 */
class MarketController extends ApiController
{
    public function __construct(private readonly ResidentContextService $context)
    {
    }

    /** GET /resident/market/listings?cursor=&category= */
    public function listings(Request $request): JsonResponse
    {
        $projectIds = $this->context->projectIds($request->user(), $request->header('X-Context-Id'));
        if (empty($projectIds)) {
            return ApiResponse::paginated([], null);
        }

        $perPage = min((int) $request->integer('per_page', 15), 50);

        $paginator = MarketplaceProduct::query()
            ->with('seller.building')
            ->whereIn('project_id', $projectIds)
            ->where('status', 'active')
            ->when($request->filled('category'), fn ($q) => $q->where('category', $request->string('category')))
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->cursorPaginate($perPage);

        $items = MarketProductResource::collection($paginator->getCollection())->resolve($request);

        return ApiResponse::paginated($items, $paginator->nextCursor()?->encode());
    }

    /** GET /resident/market/services?cursor= */
    public function services(Request $request): JsonResponse
    {
        $tenantIds = $this->context->tenantIds($request->user(), $request->header('X-Context-Id'));
        if (empty($tenantIds)) {
            return ApiResponse::paginated([], null);
        }

        $perPage = min((int) $request->integer('per_page', 15), 50);

        $paginator = ServiceProvider::query()
            ->whereIn('tenant_id', $tenantIds)
            ->where('status', 'active')
            ->orderByDesc('rating')
            ->orderBy('name')
            ->cursorPaginate($perPage);

        $items = ServiceProviderResource::collection($paginator->getCollection())->resolve($request);

        return ApiResponse::paginated($items, $paginator->nextCursor()?->encode());
    }

    /** GET /resident/market/categories — danh mục sản phẩm (distinct) của dự án. */
    public function categories(Request $request): JsonResponse
    {
        $projectIds = $this->context->projectIds($request->user(), $request->header('X-Context-Id'));
        if (empty($projectIds)) {
            return ApiResponse::success([]);
        }

        $categories = MarketplaceProduct::query()
            ->whereIn('project_id', $projectIds)
            ->where('status', 'active')
            ->whereNotNull('category')
            ->distinct()
            ->orderBy('category')
            ->pluck('category')
            ->map(fn ($c) => ['key' => $c, 'label' => $c])
            ->values()
            ->all();

        return ApiResponse::success($categories);
    }

    /** GET /resident/real-estate?cursor=&type=sale|rent */
    public function realEstate(Request $request): JsonResponse
    {
        $projectIds = $this->context->projectIds($request->user(), $request->header('X-Context-Id'));
        if (empty($projectIds)) {
            return ApiResponse::paginated([], null);
        }

        $perPage = min((int) $request->integer('per_page', 15), 50);

        $paginator = RealEstateListing::query()
            ->with(['owner', 'apartment'])
            ->whereIn('project_id', $projectIds)
            ->where('status', 'active')
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->string('type')))
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->cursorPaginate($perPage);

        $items = RealEstateListingResource::collection($paginator->getCollection())->resolve($request);

        return ApiResponse::paginated($items, $paginator->nextCursor()?->encode());
    }
}
