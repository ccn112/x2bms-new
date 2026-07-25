<?php

namespace App\Http\Controllers\Api\V1\Resident;

use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Resources\Api\V1\NotificationCommentResource;
use App\Http\Resources\Api\V1\NotificationDetailResource;
use App\Http\Resources\Api\V1\NotificationResource;
use App\Models\NotificationComment;
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
            ->withCount('comments')
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

    /** GET /api/v1/resident/notifications/{notification} — chi tiết FULL + đánh dấu đã đọc. */
    public function show(Request $request, int $notification): JsonResponse
    {
        $user = $request->user();
        $contextId = $request->header('X-Context-Id');

        $model = $this->notifications->visibleQuery($user, $contextId)
            ->withCount('comments')
            ->whereKey($notification)
            ->first();
        if ($model === null) {
            return ApiResponse::error('not_found', 'Không tìm thấy thông báo.', 404);
        }

        // Đánh dấu đã đọc (idempotent) rồi phản ánh vào response.
        $this->notifications->markRead($user, $model->id, $contextId);
        $model->is_read = true;

        return ApiResponse::success(NotificationDetailResource::make($model)->resolve($request));
    }

    /** GET /api/v1/resident/notifications/{notification}/comments — cursor, mới nhất trước. */
    public function comments(Request $request, int $notification): JsonResponse
    {
        $user = $request->user();
        $model = $this->notifications->visibleQuery($user, $request->header('X-Context-Id'))
            ->whereKey($notification)->first();
        if ($model === null) {
            return ApiResponse::error('not_found', 'Không tìm thấy thông báo.', 404);
        }

        $perPage = min((int) $request->integer('per_page', 20), 50);
        $paginator = NotificationComment::query()
            ->with('user:id,name,avatar_path')
            ->where('notification_id', $model->id)
            ->orderByDesc('id')
            ->cursorPaginate($perPage);

        $paginator->getCollection()->each(function ($c) use ($user): void {
            $c->is_mine = $user->id !== null && $c->user_id === $user->id;
        });

        $items = NotificationCommentResource::collection($paginator->getCollection())->resolve($request);

        return ApiResponse::paginated($items, $paginator->nextCursor()?->encode());
    }

    /** POST /api/v1/resident/notifications/{notification}/comments {body} — cư dân bình luận. */
    public function storeComment(Request $request, int $notification): JsonResponse
    {
        $user = $request->user();
        $model = $this->notifications->visibleQuery($user, $request->header('X-Context-Id'))
            ->whereKey($notification)->first();
        if ($model === null) {
            return ApiResponse::error('not_found', 'Không tìm thấy thông báo.', 404);
        }

        $data = $request->validate([
            'body' => ['required', 'string', 'max:2000'],
        ]);

        $comment = NotificationComment::create([
            'notification_id' => $model->id,
            'user_id' => $user->id,
            'author_name' => $user->name,
            'body' => trim($data['body']),
        ]);
        $comment->setRelation('user', $user);
        $comment->is_mine = true;

        return ApiResponse::success(
            NotificationCommentResource::make($comment)->resolve($request),
            [],
            201,
        );
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
