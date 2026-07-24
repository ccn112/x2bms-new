<?php

namespace App\Http\Controllers\Api\V1\Resident;

use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Resources\Api\V1\AmenityBookingResource;
use App\Http\Resources\Api\V1\AmenityResource;
use App\Models\Amenity;
use App\Models\AmenityBooking;
use App\Models\AmenitySlot;
use App\Models\Apartment;
use App\Services\Resident\ResidentContextService;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Đặt tiện ích nội khu (amenities/amenity_slots/amenity_bookings). Danh mục tiện ích
 * scope theo DỰ ÁN của user; booking scope theo resident/căn của user.
 * Booking status: pending|confirmed|rejected|cancelled|completed|no_show.
 */
class AmenityController extends ApiController
{
    public function __construct(private readonly ResidentContextService $context) {}

    /** GET /resident/amenities — tiện ích active thuộc dự án của user. */
    public function index(Request $request): JsonResponse
    {
        $projectIds = $this->context->projectIds($request->user(), $request->header('X-Context-Id'));
        if (empty($projectIds)) {
            return ApiResponse::success([]);
        }

        $amenities = Amenity::query()
            ->whereIn('project_id', $projectIds)
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        return ApiResponse::success(AmenityResource::collection($amenities)->resolve($request));
    }

    /** GET /resident/amenities/{amenity} — chi tiết + khung giờ (slots). */
    public function show(Request $request, Amenity $amenity): JsonResponse
    {
        $projectIds = $this->context->projectIds($request->user(), $request->header('X-Context-Id'));
        if (! in_array($amenity->project_id, $projectIds, true)) {
            return ApiResponse::error('not_found', 'Không tìm thấy tiện ích.', 404);
        }

        $amenity->load(['slots' => fn ($q) => $q->orderBy('day_of_week')->orderBy('start_time')]);

        return ApiResponse::success(AmenityResource::make($amenity)->resolve($request));
    }

    /** GET /resident/amenity-bookings?cursor= — lượt đặt của user, mới nhất trước. */
    public function bookings(Request $request): JsonResponse
    {
        $apartmentIds = $this->context->apartmentIds($request->user(), $request->header('X-Context-Id'));
        $residentIds = $request->user()->residentMemberships()->pluck('id')->all();

        if (empty($apartmentIds) && empty($residentIds)) {
            return ApiResponse::paginated([], null);
        }

        $perPage = min((int) $request->integer('per_page', 20), 50);

        $paginator = AmenityBooking::query()
            ->with('amenity')
            ->where(function ($q) use ($apartmentIds, $residentIds) {
                if (! empty($apartmentIds)) {
                    $q->orWhereIn('apartment_id', $apartmentIds);
                }
                if (! empty($residentIds)) {
                    $q->orWhereIn('resident_id', $residentIds);
                }
            })
            ->orderByDesc('booking_date')
            ->orderByDesc('id')
            ->cursorPaginate($perPage);

        $items = AmenityBookingResource::collection($paginator->getCollection())->resolve($request);

        return ApiResponse::paginated($items, $paginator->nextCursor()?->encode());
    }

    /** POST /resident/amenity-bookings — đặt tiện ích. */
    public function book(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'amenity_id' => ['required', 'integer'],
            'amenity_slot_id' => ['nullable', 'integer'],
            'booking_date' => ['required', 'date'],
            'start_time' => ['nullable', 'string', 'max:20'],
            'end_time' => ['nullable', 'string', 'max:20'],
            'party_size' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $contextId = $request->header('X-Context-Id');
        $projectIds = $this->context->projectIds($request->user(), $contextId);

        $amenity = Amenity::query()
            ->whereIn('project_id', $projectIds)
            ->where('status', 'active')
            ->find($validated['amenity_id']);
        if ($amenity === null) {
            return ApiResponse::error('not_found', 'Không tìm thấy tiện ích.', 404);
        }

        // Slot (tuỳ chọn) phải thuộc tiện ích này.
        $slotId = null;
        if (! empty($validated['amenity_slot_id'])) {
            $slot = AmenitySlot::query()
                ->where('amenity_id', $amenity->id)
                ->find($validated['amenity_slot_id']);
            if ($slot === null) {
                return ApiResponse::error('invalid_slot', 'Khung giờ không thuộc tiện ích này.', 422);
            }
            $slotId = $slot->id;
        }

        $apartmentIds = $this->context->apartmentIds($request->user(), $contextId);
        $apartment = empty($apartmentIds) ? null : Apartment::query()->find($apartmentIds[0]);
        $residentId = $request->user()->residentMemberships()->value('id');

        $status = $amenity->requires_approval ? 'pending' : 'confirmed';

        $booking = AmenityBooking::create([
            'tenant_id' => $amenity->tenant_id,
            'building_id' => $amenity->building_id ?? $apartment?->building_id,
            'amenity_id' => $amenity->id,
            'amenity_slot_id' => $slotId,
            'apartment_id' => $apartment?->id,
            'resident_id' => $residentId,
            'user_id' => $request->user()->id,
            'code' => 'BK'.Str::upper(Str::random(8)),
            'booking_date' => $validated['booking_date'],
            'start_time' => $validated['start_time'] ?? null,
            'end_time' => $validated['end_time'] ?? null,
            'party_size' => $validated['party_size'] ?? 1,
            'price' => $amenity->price ?? 0,
            'note' => $validated['note'] ?? null,
            'status' => $status,
        ]);

        $booking->load('amenity');

        return ApiResponse::success(AmenityBookingResource::make($booking)->resolve($request), [], 201);
    }

    /** DELETE /resident/amenity-bookings/{booking} — chủ booking huỷ. */
    public function cancelBooking(Request $request, AmenityBooking $booking): JsonResponse
    {
        $apartmentIds = $this->context->apartmentIds($request->user(), $request->header('X-Context-Id'));
        $residentIds = $request->user()->residentMemberships()->pluck('id')->all();

        $owns = in_array($booking->apartment_id, $apartmentIds, true)
            || ($booking->resident_id !== null && in_array($booking->resident_id, $residentIds, true))
            || $booking->user_id === $request->user()->id;
        if (! $owns) {
            return ApiResponse::error('not_found', 'Không tìm thấy lượt đặt.', 404);
        }

        if (in_array($booking->status, ['completed', 'no_show'], true)) {
            return ApiResponse::error('cannot_cancel', 'Lượt đặt đã hoàn tất, không thể huỷ.', 422);
        }

        $booking->update(['status' => 'cancelled']);
        $booking->load('amenity');

        return ApiResponse::success(AmenityBookingResource::make($booking)->resolve($request));
    }
}
