<?php

namespace App\Http\Controllers\Api\V1\Resident;

use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Resources\Api\V1\CommentResource;
use App\Models\AmenityBooking;
use App\Models\Apartment;
use App\Models\Payment;
use App\Models\VisitorRegistration;
use App\Services\Resident\ResidentContextService;
use App\Support\Api\ApiResponse;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Bình luận dùng chung cho các PHIẾU tương tác cư dân–BQL (đăng ký khách, thanh
 * toán, đặt tiện ích…). Cùng cơ chế polymorphic `HasComments` như thông báo —
 * BQL trả lời/tương tác ngay trên phiếu. Scope theo căn hộ của cư dân.
 *
 * (Phản ánh/ticket dùng hệ bình luận riêng ở FeedbackController.)
 */
class SlipCommentController extends ApiController
{
    /** slug tài nguyên → lớp model (đều có cột apartment_id + trait HasComments). */
    private const RESOURCES = [
        'visitor-registrations' => VisitorRegistration::class,
        'payments' => Payment::class,
        'amenity-bookings' => AmenityBooking::class,
    ];

    public function __construct(private readonly ResidentContextService $context) {}

    /** GET /resident/{resource}/{id}/comments */
    public function index(Request $request, string $resource, int $id): JsonResponse
    {
        $model = $this->resolve($request, $resource, $id);
        if (! $model) {
            return ApiResponse::error('not_found', 'Không tìm thấy phiếu.', 404);
        }
        $user = $request->user();

        $comments = $model->comments()
            ->with(['user:id,name,avatar_path', 'attachments'])
            ->orderBy('id')
            ->limit(500)
            ->get();

        $comments->each(function ($c) use ($user): void {
            $c->is_mine = $user->id !== null && $c->user_id === $user->id;
        });

        return ApiResponse::paginated(
            CommentResource::collection($comments)->resolve($request),
            null,
        );
    }

    /** POST /resident/{resource}/{id}/comments */
    public function store(Request $request, string $resource, int $id): JsonResponse
    {
        $model = $this->resolve($request, $resource, $id);
        if (! $model) {
            return ApiResponse::error('not_found', 'Không tìm thấy phiếu.', 404);
        }

        $data = $request->validate([
            'body' => ['required', 'string', 'max:2000'],
            'parent_id' => ['nullable', 'integer'],
            'attachment_ids' => ['nullable', 'array'],
            'attachment_ids.*' => ['integer'],
        ]);

        // Chỉ 1 cấp lồng (reply-của-reply gộp về cha), giống thông báo.
        $parentId = null;
        if (! empty($data['parent_id'])) {
            $parent = $model->comments()->whereKey($data['parent_id'])->first();
            $parentId = $parent?->parent_id ?? $parent?->id;
        }

        $user = $request->user();
        $isStaff = ! $user->hasResidentMembership() && $user->isStaffOperator();
        if ($isStaff) {
            // BQL trả lời trên phiếu — nhãn chung, không lộ tên/ảnh cá nhân.
            $authorName = 'Ban quản lý';
            $authorSubtitle = $this->projectName($model);
        } else {
            $authorName = $user->name;
            $apartmentIds = $this->context->apartmentIds($user, $request->header('X-Context-Id'));
            $authorSubtitle = $apartmentIds
                ? Apartment::query()->whereKey($apartmentIds[0])->value('code')
                : null;
        }

        $comment = $model->comments()->create([
            'parent_id' => $parentId,
            'user_id' => $user->id,
            'author_name' => $authorName,
            'author_subtitle' => $authorSubtitle,
            'is_staff' => $isStaff,
            'body' => trim($data['body']),
        ]);
        $comment->linkAttachments($data['attachment_ids'] ?? [], $user->id);
        $comment->setRelation('user', $user);
        $comment->load('attachments');
        $comment->is_mine = true;

        return ApiResponse::success(
            CommentResource::make($comment)->resolve($request),
            [],
            201,
        );
    }

    /** Tìm phiếu và kiểm tra thuộc căn hộ của cư dân (không lộ phiếu căn khác). */
    private function resolve(Request $request, string $resource, int $id): ?Model
    {
        $class = self::RESOURCES[$resource] ?? null;
        if (! $class) {
            return null;
        }
        $user = $request->user();
        $apartmentIds = $this->context->apartmentIds($user, $request->header('X-Context-Id'));
        // Sở hữu = căn hộ HOẶC resident, khớp đúng cách list/detail của phiếu quyết
        // định (PaymentController / AmenityController). Trước đây chỉ lọc
        // `apartment_id` nên phiếu thuộc về cư dân qua `resident_id` (apartment_id
        // null/khác — hay gặp với phiếu BQL tạo) hiện trong danh sách nhưng mở bình
        // luận lại 404.
        $residentIds = $user->residentMemberships()->pluck('id')->all();
        if (empty($apartmentIds) && empty($residentIds)) {
            return null;
        }

        /** @var Model|null $model */
        $model = $class::query()
            ->whereKey($id)
            ->where(function ($q) use ($apartmentIds, $residentIds): void {
                if (! empty($apartmentIds)) {
                    $q->orWhereIn('apartment_id', $apartmentIds);
                }
                if (! empty($residentIds)) {
                    $q->orWhereIn('resident_id', $residentIds);
                }
            })
            ->first();

        return $model;
    }

    private function projectName(Model $model): ?string
    {
        foreach (['project', 'building'] as $rel) {
            if (method_exists($model, $rel)) {
                $related = $model->{$rel};
                if ($related && isset($related->name)) {
                    return $related->name;
                }
            }
        }

        return null;
    }
}
