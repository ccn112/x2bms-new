<?php

namespace App\Http\Controllers\Api\V1\Resident;

use App\Enums\NotificationChannel;
use App\Http\Controllers\Api\V1\ApiController;
use App\Models\NotificationPreference;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Cư dân bật/tắt từng KÊNH thông báo push. Kênh khẩn cấp không cho tắt. Không có
 * dòng preference = mặc định bật (theo enum).
 */
class NotificationPreferenceController extends ApiController
{
    /** GET /resident/notification-preferences — catalog kênh + trạng thái người dùng. */
    public function index(Request $request): JsonResponse
    {
        $prefs = NotificationPreference::query()
            ->where('user_id', $request->user()->id)
            ->pluck('enabled', 'channel');

        $channels = array_map(function (array $c) use ($prefs) {
            $c['enabled'] = $prefs->has($c['channel'])
                ? (bool) $prefs[$c['channel']]
                : $c['default_on'];

            return $c;
        }, NotificationChannel::catalog());

        return ApiResponse::success(['channels' => $channels]);
    }

    /** PUT /resident/notification-preferences — bật/tắt một kênh. */
    public function update(Request $request): JsonResponse
    {
        $data = $request->validate([
            'channel' => ['required', 'string', Rule::in(array_column(NotificationChannel::cases(), 'value'))],
            'enabled' => ['required', 'boolean'],
        ]);

        $channel = NotificationChannel::from($data['channel']);
        if (! $channel->canDisable() && ! $data['enabled']) {
            return ApiResponse::error('channel_locked',
                'Kênh này không thể tắt.', 422);
        }

        NotificationPreference::updateOrCreate(
            ['user_id' => $request->user()->id, 'channel' => $channel->value],
            ['enabled' => $data['enabled']],
        );

        return ApiResponse::success(['channel' => $channel->value, 'enabled' => $data['enabled']]);
    }
}
