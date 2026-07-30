<?php

namespace App\Http\Controllers\Api\V1\Resident;

use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Resources\Api\V1\PaymentResource;
use App\Models\Apartment;
use App\Models\Payment;
use App\Models\Statement;
use App\Services\Resident\ResidentContextService;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Lịch sử thanh toán của cư dân (tab Hoá đơn — CD-PAY-05). Scope theo căn của user
 * (`apartment_id ∈ apartmentIds`) hoặc resident của user. Xem
 * docs/contracts/RESIDENT_API_DOMAIN.md §3 (P3).
 *
 * KHỞI TẠO thanh toán (POST) chờ owner chốt cổng thanh toán (VietQR/VNPay…) — xem §5.
 */
class PaymentController extends ApiController
{
    public function __construct(private readonly ResidentContextService $context) {}

    /** GET /resident/payments?cursor= — lịch sử, mới nhất trước. */
    public function index(Request $request): JsonResponse
    {
        $apartmentIds = $this->context->apartmentIds($request->user(), $request->header('X-Context-Id'));
        $residentIds = $request->user()->residentMemberships()->pluck('id')->all();

        if (empty($apartmentIds) && empty($residentIds)) {
            return ApiResponse::paginated([], null);
        }

        $perPage = min((int) $request->integer('per_page', 20), 50);

        $paginator = Payment::query()
            ->with('method')
            ->where(function ($q) use ($apartmentIds, $residentIds) {
                if (! empty($apartmentIds)) {
                    $q->orWhereIn('apartment_id', $apartmentIds);
                }
                if (! empty($residentIds)) {
                    $q->orWhereIn('resident_id', $residentIds);
                }
            })
            ->orderByDesc('paid_at')
            ->orderByDesc('id')
            ->cursorPaginate($perPage);

        $items = PaymentResource::collection($paginator->getCollection())->resolve($request);

        return ApiResponse::paginated($items, $paginator->nextCursor()?->encode());
    }

    /** GET /resident/payments/{payment} — chi tiết + phân bổ (allocations). */
    public function show(Request $request, Payment $payment): JsonResponse
    {
        $apartmentIds = $this->context->apartmentIds($request->user(), $request->header('X-Context-Id'));
        $residentIds = $request->user()->residentMemberships()->pluck('id')->all();

        $owns = in_array($payment->apartment_id, $apartmentIds, true)
            || ($payment->resident_id !== null && in_array($payment->resident_id, $residentIds, true));
        if (! $owns) {
            return ApiResponse::error('not_found', 'Không tìm thấy thanh toán.', 404);
        }

        $payment->load(['method', 'allocations', 'receipt', 'attachments']);

        return ApiResponse::success(PaymentResource::make($payment)->resolve($request));
    }

    /**
     * POST /resident/payments/claim — cư dân tự chuyển khoản rồi nộp chứng từ.
     *
     * Tạo `Payment` ở trạng thái `pending`, KHÔNG phân bổ vào hoá đơn: công nợ
     * chỉ được giảm khi BQL duyệt. Nếu phân bổ ngay thì cư dân gửi ảnh bất kỳ là
     * thấy hết nợ, còn tiền thật thì chưa về.
     *
     * Ảnh chứng từ là BẮT BUỘC — không có ảnh thì BQL không có gì để đối chiếu
     * với sao kê, khai báo trở thành vô nghĩa và chỉ làm rác hàng chờ.
     */
    public function claim(Request $request): JsonResponse
    {
        $user = $request->user();
        $apartmentIds = $this->context->apartmentIds($user, $request->header('X-Context-Id'));

        if (empty($apartmentIds)) {
            return ApiResponse::error('no_apartment',
                'Chưa xác định được căn hộ của bạn.', 422);
        }

        $data = $request->validate([
            // Hoá đơn cư dân muốn trả. Để trống = trả vào công nợ chung, BQL tự
            // phân bổ khi duyệt.
            'statement_id' => ['nullable', 'integer'],
            'amount' => ['required', 'numeric', 'min:1000', 'max:5000000000'],
            // Chỉ kiểm được định dạng ở đây; khoảng thời gian kiểm bên dưới vì
            // còn phải xử lý múi giờ (xem ghi chú ở $paidAt).
            'paid_at' => ['required', 'date'],
            'reference_no' => ['nullable', 'string', 'max:100'],
            'bank_account_id' => ['nullable', 'integer'],
            'note' => ['nullable', 'string', 'max:500'],
            'attachment_ids' => ['required', 'array', 'min:1', 'max:5'],
            'attachment_ids.*' => ['integer'],
        ], [
            'attachment_ids.required' => 'Vui lòng đính kèm ảnh chứng từ chuyển khoản.',
        ]);

        // Căn để ghi khoản tiền: căn đang chọn (ngữ cảnh đã thu hẹp còn 1 khi có
        // X-Context-Id), nếu không thì căn đầu tiên.
        $apartment = Apartment::query()->whereIn('id', $apartmentIds)->orderBy('id')->first();
        if ($apartment === null) {
            return ApiResponse::error('no_apartment',
                'Chưa xác định được căn hộ của bạn.', 422);
        }

        // Hoá đơn phải thuộc chính căn của cư dân — nếu không thì cư dân trả tiền
        // vào hoá đơn nhà khác, và BQL duyệt xong mới phát hiện.
        $statementId = $data['statement_id'] ?? null;
        if ($statementId !== null) {
            $owned = Statement::query()
                ->whereKey($statementId)
                ->whereIn('apartment_id', $apartmentIds)
                ->exists();
            if (! $owned) {
                return ApiResponse::error('statement_not_found',
                    'Không tìm thấy hoá đơn này trong căn hộ của bạn.', 404);
            }
        }

        // `config('app.timezone')` là **UTC**, còn cư dân ở giờ Việt Nam. Một chuỗi
        // ISO KHÔNG kèm offset (Dart: `DateTime.now().toIso8601String()` trả
        // "2026-07-30T21:05:00.000", không có hậu tố nào) mà đem parse thẳng sẽ bị
        // hiểu là UTC — sớm hơn 7 tiếng so với ý người gửi, thành "tương lai" và
        // bị chặn oan.
        //
        // Chủ dự án chốt 30/07: mặc định là **UTC+7 (Việt Nam)**. Nên chuỗi thiếu
        // múi giờ được hiểu theo `config('x2.timezone')` rồi đổi về UTC để lưu.
        // Chuỗi CÓ kèm offset (`Z` hoặc `+07:00`) vẫn được tôn trọng nguyên vẹn —
        // client nào gửi đúng chuẩn thì không bị đoán lại.
        $raw = trim((string) $data['paid_at']);
        $hasOffset = (bool) preg_match('/(Z|z|[+-]\d{2}:?\d{2})$/', $raw);

        $paidAt = $hasOffset
            ? Carbon::parse($raw)
            : Carbon::parse($raw, config('x2.timezone'))->utc();

        // Chừa 10 phút cho lệch đồng hồ máy — chặn cứng ở đúng `now()` thì cư dân
        // có máy nhanh vài phút sẽ bị từ chối mà không hiểu vì sao.
        if ($paidAt->gt(now()->addMinutes(10))) {
            return ApiResponse::error('paid_at_future',
                'Thời điểm chuyển khoản không thể ở tương lai.', 422);
        }
        // Sao kê BQL đối soát chỉ lùi được một khoảng; khai báo cũ hơn thế thì
        // không còn đối chiếu được, phải làm việc trực tiếp với BQL.
        if ($paidAt->lt(now()->subDays(180))) {
            return ApiResponse::error('paid_at_too_old',
                'Khoản chuyển khoản quá 180 ngày, vui lòng liên hệ trực tiếp Ban quản lý.', 422);
        }

        // Chặn khai trùng: bấm gửi hai lần (mạng chậm) không được tạo hai khoản
        // chờ duyệt giống nhau, vì BQL sẽ duyệt cả hai và ghi nhận tiền gấp đôi.
        $duplicate = Payment::query()
            ->where('apartment_id', $apartment->id)
            ->where('status', Payment::STATUS_PENDING)
            ->where('source', Payment::SOURCE_RESIDENT_APP)
            ->where('amount', $data['amount'])
            ->whereBetween('paid_at', [$paidAt->copy()->subMinutes(5), $paidAt->copy()->addMinutes(5)])
            ->first();
        if ($duplicate !== null) {
            // 409 chứ không 422: đây không phải dữ liệu sai, mà là trạng thái đã
            // tồn tại. App bắt mã này thì làm mới danh sách là thấy khoản cũ.
            return ApiResponse::error('duplicate_claim',
                'Bạn đã gửi một khai báo giống vậy và đang chờ BQL duyệt.', 409);
        }

        $payment = Payment::create([
            'tenant_id' => $apartment->tenant_id,
            'building_id' => $apartment->building_id,
            'apartment_id' => $apartment->id,
            'resident_id' => $user->residentMemberships()->value('id'),
            'code' => 'TT'.Str::upper(Str::random(8)),
            'amount' => $data['amount'],
            'paid_at' => $paidAt,
            'reference_no' => $data['reference_no'] ?? null,
            'status' => Payment::STATUS_PENDING,
            'source' => Payment::SOURCE_RESIDENT_APP,
            'submitted_by_id' => $user->id,
            'submitted_at' => now(),
            'claimed_statement_id' => $statementId,
            'claimed_bank_account_id' => $data['bank_account_id'] ?? null,
            'note' => $data['note'] ?? null,
        ]);

        $payment->linkAttachments($data['attachment_ids'], $user->id);

        // Ảnh phải link được, nếu không thì BQL nhận một khai báo trắng. Xảy ra
        // khi client gửi attachment_id của người khác hoặc đã gắn phiếu khác.
        if ($payment->attachments()->count() === 0) {
            $payment->delete();

            return ApiResponse::error('attachment_invalid',
                'Ảnh chứng từ không hợp lệ, vui lòng chọn lại.', 422);
        }

        $payment->load(['method', 'attachments']);

        return ApiResponse::success(
            PaymentResource::make($payment)->resolve($request), [], 201);
    }
}
