<?php

namespace App\Http\Controllers\Api\V1\Resident;

use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Resources\Api\V1\NotificationResource;
use App\Services\Resident\ResidentNotificationService;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Thông báo cho cư dân — chỉ những thông báo đã published mà audience nhắm tới
 * căn hộ/toà của người dùng (xem ResidentNotificationService).
 */
class NotificationController extends ApiController
{
    public function __construct(private readonly ResidentNotificationService $notifications) {}

    /** GET /api/v1/resident/notifications — cursor, mới nhất trước. */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $contextId = $request->header('X-Context-Id');
        $perPage = min((int) $request->integer('per_page', 20), 50);

        $paginator = $this->notifications->visibleQuery($user, $contextId)
            ->orderByDesc('is_pinned')
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->cursorPaginate($perPage);

        // Đọc trạng thái đã đọc của trang hiện tại trong 1 query.
        $readIds = $user->id === null ? [] : \App\Models\NotificationRead::query()
            ->where('user_id', $user->id)
            ->whereNotNull('read_at')
            ->whereIn('notification_id', $paginator->getCollection()->pluck('id'))
            ->pluck('notification_id')
            ->all();

        $paginator->getCollection()->each(function ($n) use ($readIds): void {
            $n->is_read = in_array($n->id, $readIds, true);
        });

        $items = NotificationResource::collection($paginator->getCollection())->resolve($request);

        return ApiResponse::paginated($items, $paginator->nextCursor()?->encode());
    }

    /** POST /api/v1/resident/notifications/{notification}/read */
    public function read(Request $request, int $notification): JsonResponse
    {
        $ok = $this->notifications->markRead($request->user(), $notification, $request->header('X-Context-Id'));
        if (! $ok) {
            return ApiResponse::error('not_found', 'Không tìm thấy thông báo.', 404);
        }

        return ApiResponse::success([
            'id' => (string) $notification,
            'is_read' => true,
            'unread_notification_count' => $this->notifications->unreadCount($request->user(), $request->header('X-Context-Id')),
        ]);
    }
}
