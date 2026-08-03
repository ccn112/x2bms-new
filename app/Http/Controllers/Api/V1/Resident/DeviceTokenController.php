<?php

namespace App\Http\Controllers\Api\V1\Resident;

use App\Http\Controllers\Api\V1\ApiController;
use App\Models\DeviceToken;
use App\Services\Notifications\ResidentTopics;
use App\Services\Push\PushService;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Đăng ký / gỡ token thiết bị nhận push (FCM). Dùng chung cho app cư dân
 * (android/ios) và WEB ADMIN (web) — cùng cơ chế token FCM. Token là duy nhất;
 * updateOrCreate theo token nên đổi user trên cùng máy sẽ gắn lại đúng người.
 */
class DeviceTokenController extends ApiController
{
    /** POST /resident/device-tokens */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'token' => ['required', 'string', 'max:512'],
            'platform' => ['nullable', 'string', 'in:android,ios,web'],
            'device_label' => ['nullable', 'string', 'max:120'],
        ]);

        DeviceToken::updateOrCreate(
            ['token' => $data['token']],
            [
                'user_id' => $request->user()->id,
                'platform' => $data['platform'] ?? 'android',
                'device_label' => $data['device_label'] ?? null,
                'last_used_at' => now(),
            ],
        );

        // N1: đăng ký thiết bị vào topic theo tenant/dự án/toà để broadcast bằng topic.
        // App đăng ký lại token mỗi lần mở → topic tự cập nhật khi cư dân đổi căn.
        // No-op khi FCM tắt (dev/test). Không chặn response nếu FCM lỗi.
        app(PushService::class)->subscribeToTopics(
            [$data['token']],
            app(ResidentTopics::class)->for($request->user(), $request->header('X-Context-Id')),
        );

        return ApiResponse::success(['registered' => true]);
    }

    /** DELETE /resident/device-tokens — gỡ khi đăng xuất. */
    public function destroy(Request $request): JsonResponse
    {
        $data = $request->validate(['token' => ['required', 'string']]);

        DeviceToken::query()
            ->where('token', $data['token'])
            ->where('user_id', $request->user()->id)
            ->delete();

        return ApiResponse::success(['unregistered' => true]);
    }
}
