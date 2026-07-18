<?php

namespace App\Http\Controllers\Api\V1\Resident;

use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Resources\Api\V1\StatementResource;
use App\Models\Statement;
use App\Services\Resident\ResidentContextService;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Resident-facing fee statements (hóa đơn). Scoped EXPLICITLY to the caller's apartments
 * (residents have tenant_id NULL, so the tenant global scope must not be relied on).
 */
class StatementController extends ApiController
{
    public function __construct(private readonly ResidentContextService $context) {}

    /** GET /api/v1/resident/statements — cursor-paginated, newest first. */
    public function index(Request $request): JsonResponse
    {
        $apartmentIds = $this->context->apartmentIds($request->user(), $request->header('X-Context-Id'));
        if (empty($apartmentIds)) {
            return ApiResponse::paginated([], null);
        }

        $perPage = min((int) $request->integer('per_page', 20), 50);

        $paginator = Statement::query()
            ->whereIn('apartment_id', $apartmentIds)
            ->orderByDesc('issued_at')
            ->orderByDesc('id')
            ->cursorPaginate($perPage);

        $items = StatementResource::collection($paginator->getCollection())->resolve($request);

        return ApiResponse::paginated($items, $paginator->nextCursor()?->encode());
    }

    /** GET /api/v1/resident/statements/{statement} */
    public function show(Request $request, Statement $statement): JsonResponse
    {
        $apartmentIds = $this->context->apartmentIds($request->user(), $request->header('X-Context-Id'));
        if (! in_array($statement->apartment_id, $apartmentIds, true)) {
            throw new NotFoundHttpException; // don't leak existence of other apartments' statements
        }

        $statement->load('lines');

        return ApiResponse::success(StatementResource::make($statement)->resolve($request));
    }
}
