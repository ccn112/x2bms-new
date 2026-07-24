<?php

namespace App\Http\Controllers\Api\V1\Resident;

use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Resources\Api\V1\VisitorRegistrationResource;
use App\Models\Apartment;
use App\Models\Building;
use App\Models\VisitorRegistration;
use App\Services\Resident\ResidentContextService;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Đăng ký khách (C12 — visitor_registrations). Cư dân đăng ký khách sẽ đến căn của
 * mình; BQL/an ninh duyệt & phát pass. Scope theo `apartment_id ∈` căn của user.
 * status: pending|approved|rejected|checked_in|checked_out|expired|cancelled.
 */
class VisitorController extends ApiController
{
    public function __construct(private readonly ResidentContextService $context) {}

    /** GET /resident/visitors?cursor= — khách của căn user, mới nhất trước. */
    public function index(Request $request): JsonResponse
    {
        $apartmentIds = $this->context->apartmentIds($request->user(), $request->header('X-Context-Id'));
        if (empty($apartmentIds)) {
            return ApiResponse::paginated([], null);
        }

        $perPage = min((int) $request->integer('per_page', 20), 50);

        $paginator = VisitorRegistration::query()
            ->whereIn('apartment_id', $apartmentIds)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->cursorPaginate($perPage);

        $items = VisitorRegistrationResource::collection($paginator->getCollection())->resolve($request);

        return ApiResponse::paginated($items, $paginator->nextCursor()?->encode());
    }

    /** POST /resident/visitors — tạo đăng ký khách cho căn đang chọn. */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'visitor_name' => ['required', 'string', 'max:255'],
            'visitor_phone' => ['nullable', 'string', 'max:50'],
            'purpose' => ['nullable', 'string', 'max:255'],
            'vehicle_plate' => ['nullable', 'string', 'max:50'],
            'num_guests' => ['nullable', 'integer', 'min:1', 'max:100'],
            'expected_at' => ['required', 'date'],
            'expected_leave_at' => ['nullable', 'date', 'after_or_equal:expected_at'],
        ]);

        $contextId = $request->header('X-Context-Id');
        $apartmentIds = $this->context->apartmentIds($request->user(), $contextId);
        if (empty($apartmentIds)) {
            return ApiResponse::error('no_apartment', 'Tài khoản chưa gắn căn hộ.', 403);
        }

        $apartment = Apartment::query()->find($apartmentIds[0]);
        if ($apartment === null) {
            return ApiResponse::error('no_apartment', 'Không tìm thấy căn hộ.', 403);
        }

        $projectId = $apartment->building_id
            ? Building::query()->whereKey($apartment->building_id)->value('project_id')
            : null;
        $residentId = $request->user()->residentMemberships()->value('id');

        $visitor = VisitorRegistration::create([
            'tenant_id' => $apartment->tenant_id,
            'project_id' => $projectId,
            'building_id' => $apartment->building_id,
            'apartment_id' => $apartment->id,
            'resident_id' => $residentId,
            'host_user_id' => $request->user()->id,
            'code' => 'KH'.Str::upper(Str::random(8)),
            'visitor_name' => $validated['visitor_name'],
            'visitor_phone' => $validated['visitor_phone'] ?? null,
            'purpose' => $validated['purpose'] ?? null,
            'vehicle_plate' => $validated['vehicle_plate'] ?? null,
            'num_guests' => $validated['num_guests'] ?? 1,
            'expected_at' => $validated['expected_at'],
            'expected_leave_at' => $validated['expected_leave_at'] ?? null,
            'status' => 'pending',
        ]);

        return ApiResponse::success(VisitorRegistrationResource::make($visitor)->resolve($request), [], 201);
    }

    /** POST /resident/visitors/{visitor}/cancel — chủ căn huỷ đăng ký. */
    public function cancel(Request $request, VisitorRegistration $visitor): JsonResponse
    {
        $apartmentIds = $this->context->apartmentIds($request->user(), $request->header('X-Context-Id'));
        if (! in_array($visitor->apartment_id, $apartmentIds, true)) {
            return ApiResponse::error('not_found', 'Không tìm thấy đăng ký khách.', 404);
        }

        if (in_array($visitor->status, ['checked_in', 'checked_out'], true)) {
            return ApiResponse::error('cannot_cancel', 'Khách đã vào/ra, không thể huỷ.', 422);
        }

        $visitor->update(['status' => 'cancelled']);

        return ApiResponse::success(VisitorRegistrationResource::make($visitor)->resolve($request));
    }
}
