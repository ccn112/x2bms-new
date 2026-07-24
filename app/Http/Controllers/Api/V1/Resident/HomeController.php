<?php

namespace App\Http\Controllers\Api\V1\Resident;

use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Resources\Api\V1\NotificationResource;
use App\Models\Building;
use App\Models\Statement;
use App\Services\Resident\AqiService;
use App\Services\Resident\ResidentContextService;
use App\Services\Resident\ResidentNotificationService;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * GET /api/v1/resident/home — tổng hợp nhẹ cho first-paint tab Home (CD-HOME).
 * Compose từ nhiều nguồn: metrics (AQI), tasks (công nợ/khách/phản ánh),
 * notices_preview (2 thông báo mới nhất). Xem docs/contracts/RESIDENT_API_DOMAIN.md §3.
 */
class HomeController extends ApiController
{
    public function __construct(
        private readonly ResidentContextService $context,
        private readonly ResidentNotificationService $notifications,
        private readonly AqiService $aqi,
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $contextId = $request->header('X-Context-Id');

        $apartmentIds = $this->context->apartmentIds($user, $contextId);
        $residentIds = $user->residentMemberships()->pluck('id')->all();

        return ApiResponse::success([
            'metrics' => $this->metrics($user, $contextId),
            'tasks' => $this->tasks($apartmentIds, $residentIds),
            'notices_preview' => $this->noticesPreview($request, $user, $contextId),
        ]);
    }

    /** @return array<int,array<string,mixed>> */
    private function metrics($user, ?string $contextId): array
    {
        $projectIds = $this->context->projectIds($user, $contextId);

        // AQI theo project đầu tiên có toạ độ.
        foreach ($projectIds as $projectId) {
            $aqi = $this->aqi->forProject($projectId);
            if ($aqi !== null) {
                return [$aqi];
            }
        }

        return [];
    }

    /**
     * @param  array<int>  $apartmentIds
     * @param  array<int>  $residentIds
     * @return array<int,array<string,mixed>>
     */
    private function tasks(array $apartmentIds, array $residentIds): array
    {
        $tasks = [];

        // Công nợ (chưa thanh toán).
        $debt = '0';
        $unpaidCount = 0;
        if (! empty($apartmentIds)) {
            $unpaid = Statement::query()
                ->whereIn('apartment_id', $apartmentIds)
                ->where('status', '!=', 'paid')
                ->get(['total_amount', 'paid_amount']);
            foreach ($unpaid as $s) {
                $out = bcsub((string) ($s->total_amount ?? '0'), (string) ($s->paid_amount ?? '0'), 2);
                if (bccomp($out, '0', 2) > 0) {
                    $debt = bcadd($debt, $out, 2);
                    $unpaidCount++;
                }
            }
        }
        $tasks[] = [
            'key' => 'fee',
            'title' => 'Công nợ',
            'value' => $debt,
            'count' => $unpaidCount,
            'status' => $unpaidCount > 0 ? 'due' : 'clear',
        ];

        // Khách sắp đến (visitor_registrations còn hiệu lực).
        $guestCount = 0;
        if (! empty($apartmentIds)) {
            $guestCount = (int) DB::table('visitor_registrations')
                ->whereIn('apartment_id', $apartmentIds)
                ->whereNull('deleted_at')
                ->whereIn('status', ['pending', 'approved', 'registered'])
                ->where('expected_at', '>=', now())
                ->count();
        }
        $tasks[] = [
            'key' => 'guest',
            'title' => 'Khách sắp đến',
            'value' => (string) $guestCount,
            'count' => $guestCount,
            'status' => $guestCount > 0 ? 'active' : 'none',
        ];

        // Phản ánh đang mở.
        $feedbackCount = 0;
        if (! empty($residentIds)) {
            $feedbackCount = (int) DB::table('feedback_requests')
                ->whereIn('resident_id', $residentIds)
                ->whereNull('deleted_at')
                ->whereNotIn('status', ['resolved', 'closed', 'cancelled'])
                ->count();
        }
        $tasks[] = [
            'key' => 'feedback',
            'title' => 'Phản ánh đang xử lý',
            'value' => (string) $feedbackCount,
            'count' => $feedbackCount,
            'status' => $feedbackCount > 0 ? 'active' : 'none',
        ];

        return $tasks;
    }

    /** @return array<int,array<string,mixed>> */
    private function noticesPreview(Request $request, $user, ?string $contextId): array
    {
        $notices = $this->notifications->visibleQuery($user, $contextId)
            ->orderByDesc('is_pinned')
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->limit(2)
            ->get();

        return NotificationResource::collection($notices)->resolve($request);
    }
}
