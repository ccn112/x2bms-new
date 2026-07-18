<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\MobileDevice;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Device / push-token registry endpoints (docs §8). */
class DeviceController extends ApiController
{
    /** POST /api/v1/me/devices — upsert this install's push token for the current user. */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'installation_id' => ['required', 'uuid'],
            'platform' => ['required', 'in:ios,android'],
            'provider' => ['nullable', 'in:fcm,apns,hms'],
            'push_token' => ['nullable', 'string'],
            'app_version' => ['nullable', 'string'],
            'locale' => ['nullable', 'string'],
            'timezone' => ['nullable', 'string'],
            'notification_permission' => ['nullable', 'in:granted,denied,provisional'],
        ]);

        $device = MobileDevice::updateOrCreate(
            ['installation_id' => $data['installation_id']],
            array_merge($data, [
                'user_id' => $request->user()->id,
                'provider' => $data['provider'] ?? 'fcm',
                'last_seen_at' => now(),
                'token_refreshed_at' => now(),
                'revoked_at' => null,
            ]),
        );

        return ApiResponse::success(['device_id' => $device->id], status: 201);
    }

    /** DELETE /api/v1/me/devices/{installationId} — detach user (keep public subscription). */
    public function destroy(Request $request, string $installationId): JsonResponse
    {
        MobileDevice::where('installation_id', $installationId)
            ->where('user_id', $request->user()->id)
            ->update(['user_id' => null, 'revoked_at' => now()]);

        return ApiResponse::success(['detached' => true]);
    }
}
