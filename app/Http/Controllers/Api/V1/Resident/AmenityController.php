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
use Illuminate\Support\Carbon;
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
        $contextId = $request->header('X-Context-Id');
        $projectIds = $this->context->projectIds($request->user(), $contextId);
        if (empty($projectIds)) {
            return ApiResponse::success([]);
        }

        $amenities = Amenity::query()
            ->whereIn('project_id', $projectIds)
            ->where('status', 'active')
            ->withCount($this->statsCounters($request, $contextId))
            ->orderBy('name')
            ->get();

        return ApiResponse::success(AmenityResource::collection($amenities)->resolve($request));
    }

    /** GET /resident/amenities/{amenity} — chi tiết + khung giờ (slots). */
    public function show(Request $request, Amenity $amenity): JsonResponse
    {
        $contextId = $request->header('X-Context-Id');
        $projectIds = $this->context->projectIds($request->user(), $contextId);
        if (! in_array($amenity->project_id, $projectIds, true)) {
            return ApiResponse::error('not_found', 'Không tìm thấy tiện ích.', 404);
        }

        $amenity->loadCount($this->statsCounters($request, $contextId));
        $amenity->load(['slots' => fn ($q) => $q->orderBy('day_of_week')->orderBy('start_time')]);

        return ApiResponse::success(AmenityResource::make($amenity)->resolve($request));
    }

    /**
     * GET /resident/amenities/{amenity}/availability?date=YYYY-MM-DD — khung giờ
     * ÁP DỤNG cho ngày đó kèm số đã giữ chỗ / còn trống, để app báo bận khi hết
     * slot. `booked` = tổng party_size các booking còn hiệu lực (pending/confirmed)
     * — cancelled/rejected/no_show không giữ chỗ.
     */
    public function availability(Request $request, Amenity $amenity): JsonResponse
    {
        $projectIds = $this->context->projectIds($request->user(), $request->header('X-Context-Id'));
        if (! in_array($amenity->project_id, $projectIds, true)) {
            return ApiResponse::error('not_found', 'Không tìm thấy tiện ích.', 404);
        }

        $data = $request->validate(['date' => ['required', 'date_format:Y-m-d']]);
        $date = Carbon::createFromFormat('Y-m-d', $data['date'])->startOfDay();
        $dow = $date->dayOfWeek; // 0=CN .. 6=T7

        // Slot cho ngày này: mọi ngày (day_of_week null) hoặc đúng thứ, đang mở.
        $slots = $amenity->slots()
            ->where('status', 'open')
            ->where(fn ($q) => $q->whereNull('day_of_week')->orWhere('day_of_week', $dow))
            ->orderBy('start_time')
            ->get();

        // Số đã giữ chỗ theo slot cho NGÀY đó.
        $used = $amenity->bookings()
            ->whereDate('booking_date', $date->toDateString())
            ->whereIn('status', ['pending', 'confirmed'])
            ->whereNotNull('amenity_slot_id')
            ->selectRaw('amenity_slot_id, COALESCE(SUM(party_size),0) as used')
            ->groupBy('amenity_slot_id')
            ->pluck('used', 'amenity_slot_id');

        $out = $slots->map(function ($s) use ($used) {
            $booked = (int) ($used[$s->id] ?? 0);
            $remaining = max(0, (int) $s->capacity - $booked);

            return [
                'slot_id' => (string) $s->id,
                'start_time' => $s->start_time,
                'end_time' => $s->end_time,
                'capacity' => (int) $s->capacity,
                'booked' => $booked,
                'remaining' => $remaining,
                'is_full' => $remaining <= 0,
            ];
        })->all();

        return ApiResponse::success(['date' => $date->toDateString(), 'slots' => $out]);
    }

    /**
     * withCount() closures cho dải thống kê trên thẻ tiện ích (chốt 30/07 lần 2):
     * - `bookings_today_total`: lượt đặt HÔM NAY của TẤT CẢ cư dân, chỉ tính
     *   booking còn hiệu lực (pending/confirmed) — cancelled/rejected không giữ
     *   chỗ nên không tính vào độ "hot".
     * - `my_bookings_total`: lịch sử all-time của RIÊNG cư dân đang đăng nhập
     *   (kể cả đã huỷ/từ chối — đây là "tôi từng đặt", không phải "đang giữ chỗ").
     * - `my_bookings_today`: lượt còn hiệu lực hôm nay của riêng cư dân đó.
     *
     * Dùng withCount (subquery) thay vì lặp qua từng amenity rồi query riêng —
     * danh sách tiện ích một dự án chỉ vài chục dòng, nhưng lặp query là kiểu
     * N+1 kinh điển, mở rộng dự án lớn sẽ chậm dần đều.
     *
     * @return array<string, \Closure>
     */
    private function statsCounters(Request $request, ?string $contextId): array
    {
        $apartmentIds = $this->context->apartmentIds($request->user(), $contextId);
        $residentIds = $request->user()->residentMemberships()->pluck('id')->all();
        $today = now()->toDateString();

        // Thu hẹp về đúng booking của resident hiện tại. Không có căn/hồ sơ nào
        // khớp thì ép về rỗng (1=0) — để trống where() sẽ khớp MỌI dòng và lộ
        // số liệu của người khác, đây là lỗi tệ hơn nhiều so với hiện số 0.
        $onlyMine = function ($q) use ($apartmentIds, $residentIds): void {
            if (empty($apartmentIds) && empty($residentIds)) {
                $q->whereRaw('1 = 0');

                return;
            }
            $q->where(function ($q2) use ($apartmentIds, $residentIds) {
                if (! empty($apartmentIds)) {
                    $q2->orWhereIn('apartment_id', $apartmentIds);
                }
                if (! empty($residentIds)) {
                    $q2->orWhereIn('resident_id', $residentIds);
                }
            });
        };

        return [
            'bookings as bookings_today_total' => fn ($q) => $q
                ->whereDate('booking_date', $today)
                ->whereIn('status', ['pending', 'confirmed']),
            'bookings as my_bookings_total' => fn ($q) => $onlyMine($q),
            'bookings as my_bookings_today' => function ($q) use ($today, $onlyMine): void {
                $q->whereDate('booking_date', $today)->whereIn('status', ['pending', 'confirmed']);
                $onlyMine($q);
            },
        ];
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
            // `qrPass` để lấy completed_at (used_at) — mốc THỰC SỰ dùng tiện
            // ích, khác với lúc BQL xác nhận. `withCount('comments')` để hiện
            // số bình luận trên thẻ lịch đặt mà không phải gọi thêm request.
            ->with(['amenity', 'qrPass'])
            ->withCount('comments')
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
            // Không cần duyệt thì tự động coi như BQL "xác nhận" ngay lúc đặt —
            // mốc quyết định trùng thời điểm tạo, để dòng thời gian phiếu không
            // trống bước này (không bịa giờ: đây đúng là lúc trạng thái = confirmed).
            'approved_at' => $status === 'confirmed' ? now() : null,
        ]);

        $booking->load(['amenity', 'qrPass']);
        $booking->loadCount('comments');

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

        $booking->update(['status' => 'cancelled', 'cancelled_at' => now()]);
        $booking->load(['amenity', 'qrPass']);
        $booking->loadCount('comments');

        return ApiResponse::success(AmenityBookingResource::make($booking)->resolve($request));
    }
}
