<?php

namespace App\Http\Controllers\Api\V1\Resident;

use App\Http\Controllers\Api\V1\ApiController;
use App\Models\Apartment;
use App\Models\SosAlert;
use App\Services\Resident\ResidentContextService;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * POST /api/v1/resident/sos — cư dân bấm nút SOS (an ninh). Tạo bản ghi
 * `sos_alerts` (source='app', status='triggered') scope theo căn đang chọn.
 * KHÔNG tạo bảng mới. Xem docs/contracts/RESIDENT_API_DOMAIN.md §4.
 */
class SosController extends ApiController
{
    public function __construct(private readonly ResidentContextService $context)
    {
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'location' => ['nullable', 'string', 'max:255'],
            'lat' => ['nullable', 'numeric'],
            'lng' => ['nullable', 'numeric'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $contextId = $request->header('X-Context-Id');
        $apartmentIds = $this->context->apartmentIds($request->user(), $contextId);
        if (empty($apartmentIds)) {
            return ApiResponse::error('no_apartment', 'Tài khoản chưa gắn căn hộ.', 403);
        }

        $apartment = Apartment::query()->with('building')->find($apartmentIds[0]);
        $residentId = $request->user()->residentMemberships()->value('id');

        // location = "lat,lng" nếu app gửi toạ độ, else mô tả text, else nhãn căn.
        $location = $validated['location'] ?? null;
        if ($location === null && isset($validated['lat'], $validated['lng'])) {
            $location = $validated['lat'].','.$validated['lng'];
        }
        $location ??= $apartment?->code;

        $alert = SosAlert::create([
            'tenant_id' => $apartment?->tenant_id,
            'project_id' => $apartment?->building?->project_id,
            'building_id' => $apartment?->building_id,
            'apartment_id' => $apartment?->id,
            'resident_id' => $residentId,
            'source' => 'app',
            'status' => 'triggered',
            'location' => $location,
            'triggered_at' => now(),
            'note' => $validated['note'] ?? null,
        ]);

        return ApiResponse::success([
            'id' => (string) $alert->id,
            'status' => $alert->status,
            'triggered_at' => optional($alert->triggered_at)->toIso8601String(),
        ], [], 201);
    }
}
