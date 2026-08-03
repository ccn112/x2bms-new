<?php

namespace App\Http\Controllers\Api\V1\Resident;

use App\Http\Controllers\Api\V1\ApiController;
use App\Models\ActivityNotification;
use App\Services\Notifications\BellReader;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * CHUÔNG hợp nhất (module notifications-multichannel, N0). Trộn broadcast BQL
 * (áp cho căn/toà/dự án của tôi, tính lúc đọc) + activity targeted của tôi. Mỗi
 * item có `type` (announcement|activity) để app điều hướng đúng.
 *
 * Khác `GET /resident/notifications` (announcement-centric, dùng cho màn "Thông báo
 * BQL"). Chuông = endpoint này.
 */
class BellController extends ApiController
{
    public function __construct(private readonly BellReader $bell) {}

    /** GET /api/v1/resident/bell?before=&limit= */
    public function index(Request $request): JsonResponse
    {
        $limit = min(max((int) $request->integer('limit', 30), 1), 50);
        $result = $this->bell->render(
            $request->user(),
            $request->header('X-Context-Id'),
            $request->string('before')->trim()->value() ?: null,
            $limit,
        );

        return ApiResponse::success($result);
    }

    /** POST /api/v1/resident/bell/seen — bump mốc đã-thấy (đưa chưa-đọc broadcast về 0). */
    public function seen(Request $request): JsonResponse
    {
        $this->bell->markSeen($request->user());

        return ApiResponse::success([
            'unread' => $this->bell->unreadCount($request->user(), $request->header('X-Context-Id')),
        ]);
    }

    /** POST /api/v1/resident/bell/activities/{activity}/read — đánh dấu đọc 1 activity. */
    public function readActivity(Request $request, int $activity): JsonResponse
    {
        // Scope theo recipient = biên bảo mật; của người khác → 404, không tiết lộ.
        $row = ActivityNotification::query()
            ->where('recipient_user_id', $request->user()->id)
            ->whereKey($activity)
            ->first();
        if ($row === null) {
            return ApiResponse::error('not_found', 'Không tìm thấy thông báo.', 404);
        }
        if ($row->read_at === null) {
            $row->forceFill(['read_at' => now()])->save();
        }

        return ApiResponse::success([
            'id' => (string) $row->id,
            'is_read' => true,
            'unread' => $this->bell->unreadCount($request->user(), $request->header('X-Context-Id')),
        ]);
    }
}
