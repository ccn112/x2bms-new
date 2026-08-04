<?php

namespace App\Http\Controllers\Api\V1\Resident;

use App\Http\Controllers\Api\V1\ApiController;
use App\Services\Resident\Interaction\InteractionAggregator;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Trung tâm tương tác (handoff v1.1) — read-model HỢP NHẤT phiếu của cư dân.
 * KPI summary theo context (không theo filter list); list gom đa nguồn + lọc/sort/cursor.
 * Scope theo cư dân trong {@see InteractionAggregator} (BOLA-safe).
 */
class InteractionController extends ApiController
{
    public function __construct(private readonly InteractionAggregator $aggregator) {}

    /** GET /api/v1/resident/interactions/summary — 3 KPI (không nhận filter list). */
    public function summary(Request $request): JsonResponse
    {
        return ApiResponse::success($this->aggregator->summary($request->user()));
    }

    /** GET /api/v1/resident/interactions?q&type&subtype&status_family&sort&cursor&limit */
    public function index(Request $request): JsonResponse
    {
        $result = $this->aggregator->list(
            $request->user(),
            $request->only(['q', 'type', 'subtype', 'status_family', 'sort', 'cursor', 'limit']),
        );

        return ApiResponse::paginated($result['items'], $result['next_cursor']);
    }
}
