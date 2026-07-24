<?php

namespace App\Http\Controllers\Api\V1\Resident;

use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Resources\Api\V1\GiftResource;
use App\Http\Resources\Api\V1\LoyaltyActivityResource;
use App\Models\LoyaltyAccount;
use App\Models\LoyaltyTier;
use App\Models\LoyaltyTransaction;
use App\Services\Resident\VoucherVisibilityService;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Điểm thưởng & hạng thành viên của cư dân (tab Ưu đãi — CD-LY-01).
 * Scope theo resident của user (loyalty_accounts.resident_id ∈ residentIds).
 * next_tier tính từ bảng loyalty_tiers; benefits lấy theo tier hiện tại.
 */
class LoyaltyController extends ApiController
{
    /** GET /api/v1/resident/loyalty */
    public function show(Request $request): JsonResponse
    {
        $residentIds = $request->user()->residentMemberships()->pluck('id')->all();

        $account = empty($residentIds)
            ? null
            : LoyaltyAccount::query()
                ->whereIn('resident_id', $residentIds)
                ->orderByDesc('points_balance')
                ->first();

        $points = (int) ($account->points_balance ?? 0);
        $tierKey = $account->tier ?? 'silver';

        $tiers = LoyaltyTier::query()->orderBy('min_points')->get();
        $currentTier = $tiers->firstWhere('key', $tierKey) ?? $tiers->first();
        $nextTier = $tiers->first(fn ($t) => $t->min_points > $points);

        $benefits = $currentTier
            ? $currentTier->benefits()->orderBy('sort')->get()->map(fn ($b) => [
                'icon_key' => $b->icon_key,
                'title' => $b->title,
                'subtitle' => $b->subtitle,
            ])->all()
            : [];

        return ApiResponse::success([
            'points' => $points,
            'status' => $account->status ?? 'active',
            'tier' => [
                'key' => $currentTier?->key ?? $tierKey,
                'name' => $currentTier?->name ?? ucfirst($tierKey),
            ],
            'next_tier' => $nextTier === null ? null : [
                'key' => $nextTier->key,
                'name' => $nextTier->name,
                'target' => (int) $nextTier->min_points,
                'points_to_next' => max(0, (int) $nextTier->min_points - $points),
            ],
            'benefits' => $benefits,
            'updated_at' => optional($account?->updated_at)->toIso8601String(),
        ]);
    }

    /** GET /api/v1/resident/loyalty/activities — cursor, mới nhất trước. */
    public function activities(Request $request): JsonResponse
    {
        $residentIds = $request->user()->residentMemberships()->pluck('id')->all();
        if (empty($residentIds)) {
            return ApiResponse::paginated([], null);
        }

        $accountIds = LoyaltyAccount::query()
            ->whereIn('resident_id', $residentIds)
            ->pluck('id');

        $perPage = min((int) $request->integer('per_page', 20), 50);

        $paginator = LoyaltyTransaction::query()
            ->whereIn('loyalty_account_id', $accountIds)
            ->orderByDesc('transacted_at')
            ->orderByDesc('id')
            ->cursorPaginate($perPage);

        $items = LoyaltyActivityResource::collection($paginator->getCollection())->resolve($request);

        return ApiResponse::paginated($items, $paginator->nextCursor()?->encode());
    }

    /**
     * GET /api/v1/resident/loyalty/gifts — quà đổi điểm (voucher points_cost > 0)
     * hiển thị cho cư dân theo tenant (∪ voucher platform đã rollout). cursor.
     */
    public function gifts(Request $request, VoucherVisibilityService $vouchers): JsonResponse
    {
        $perPage = min((int) $request->integer('per_page', 20), 50);

        $paginator = $vouchers
            ->visibleQuery($request->user(), $request->header('X-Context-Id'))
            ->where('points_cost', '>', 0)
            ->orderBy('points_cost')
            ->orderByDesc('id')
            ->cursorPaginate($perPage);

        $items = GiftResource::collection($paginator->getCollection())->resolve($request);

        return ApiResponse::paginated($items, $paginator->nextCursor()?->encode());
    }
}
