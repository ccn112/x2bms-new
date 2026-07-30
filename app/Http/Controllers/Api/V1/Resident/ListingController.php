<?php

namespace App\Http\Controllers\Api\V1\Resident;

use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Controllers\Api\V1\Resident\Concerns\AttachesListingMeta;
use App\Http\Resources\Api\V1\RealEstateListingResource;
use App\Models\Apartment;
use App\Models\ListingInquiry;
use App\Models\Project;
use App\Models\RealEstateListing;
use App\Services\Resident\ListingAccessService;
use App\Services\Resident\ListingFeedPublisher;
use App\Support\Api\ApiResponse;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Tin rao BĐS (CD-MK "tin rao"): tạo · tin của tôi · rút tin · quan tâm ·
 * để lại thông tin (xem nhà / liên hệ). Quyết định nghiệp vụ 2026-07-30:
 *
 *   1. Chủ căn rao trực tiếp; người thuê/môi giới cần BQL cấp quyền
 *      (`ListingPostingGrant`, xem `ListingAccessService::residentAllowedToPost`).
 *   2. Tin cần BQL DUYỆT trước, trừ khi dự án bật `listings_auto_approve`.
 *   3. Người có tài khoản (kể cả chưa là cư dân) được để lại thông tin liên hệ.
 *
 * Đọc tin công khai (danh sách/lọc) vẫn ở `MarketController::realEstate` —
 * controller này chỉ phần GHI + "tin của tôi", tránh trộn hai vòng đời khác
 * nhau (đọc công khai vs quản lý tin của chính mình) vào một class.
 */
class ListingController extends ApiController
{
    use AttachesListingMeta;

    public function __construct(
        private readonly ListingAccessService $access,
        private readonly ListingFeedPublisher $feed,
    ) {}

    /** POST /resident/listings */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'apartment_id' => ['required', 'integer'],
            'type' => ['required', 'string', 'in:sale,rent'],
            'title' => ['required', 'string', 'max:150'],
            'price' => ['required', 'numeric', 'min:0'],
            'area' => ['nullable', 'numeric', 'min:0'],
            'bedrooms' => ['nullable', 'integer', 'min:0', 'max:20'],
        ]);

        $apartment = Apartment::withoutGlobalScope('tenant')->find($data['apartment_id']);
        if ($apartment === null) {
            return ApiResponse::error('not_found', 'Không tìm thấy căn hộ.', 404);
        }

        $user = $request->user();
        $residentId = $this->access->residentAllowedToPost($user, $apartment->id);
        if ($residentId === null) {
            return ApiResponse::error(
                'not_authorized_to_post',
                'Bạn chưa được phép rao tin cho căn hộ này. Người thuê/môi giới cần Ban quản lý xác minh trước khi đăng.',
                403,
            );
        }

        $projectId = $this->access->projectIdForApartment($apartment->id);
        $project = $projectId === null ? null : Project::withoutGlobalScope('tenant')->find($projectId);
        $autoApprove = (bool) ($project?->listings_auto_approve);
        $now = now();

        $listing = new RealEstateListing([
            'tenant_id' => $apartment->tenant_id,
            'project_id' => $projectId,
            'apartment_id' => $apartment->id,
            'owner_resident_id' => $residentId,
            'created_by_user_id' => $user->id,
            'code' => 'RE-'.strtoupper(Str::random(8)),
            'type' => $data['type'],
            'title' => trim($data['title']),
            'price' => $data['price'],
            'area' => $data['area'] ?? null,
            'bedrooms' => $data['bedrooms'] ?? null,
            'status' => 'active',
            'approval_status' => $autoApprove ? 'approved' : 'pending',
            'approved_at' => $autoApprove ? $now : null,
            'published_at' => $autoApprove ? $now : null,
        ]);
        $listing->save();

        if ($autoApprove) {
            $this->feed->publish($listing);
        }

        return ApiResponse::success($this->one($request, $listing->fresh()), [], 201);
    }

    /** GET /resident/listings/mine — tin của tôi, mọi trạng thái duyệt. */
    public function mine(Request $request): JsonResponse
    {
        $user = $request->user();

        $listings = RealEstateListing::withoutGlobalScope('tenant')
            ->with(['owner', 'apartment'])
            ->where('created_by_user_id', $user->id)
            ->orderByDesc('created_at')
            ->get();

        $this->attachListingMeta($request, $listings);

        return ApiResponse::success(
            RealEstateListingResource::collection($listings)->resolve($request)
        );
    }

    /** DELETE /resident/listings/{id} — chủ tin tự rút. */
    public function destroy(Request $request, int $listing): JsonResponse
    {
        $user = $request->user();
        $model = RealEstateListing::withoutGlobalScope('tenant')
            ->where('created_by_user_id', $user->id)
            ->find($listing);

        if ($model === null) {
            return ApiResponse::error('not_found', 'Không tìm thấy tin rao.', 404);
        }

        $model->delete();
        $this->feed->unpublish($model);

        return response()->json(null, 204);
    }

    /** POST /resident/listings/{id}/interest — bấm "Quan tâm" (không PII). */
    public function interest(Request $request, int $listing): JsonResponse
    {
        $found = $this->findInteractable($request, $listing);
        if ($found instanceof JsonResponse) {
            return $found;
        }
        [$model, $errorIfOwn] = $found;
        if ($errorIfOwn) {
            return $errorIfOwn;
        }

        $user = $request->user();
        $residentId = $user->residentMemberships()->value('id');

        // `firstOrCreate` — idempotent: bấm hai lần không cộng hai lần, và
        // khớp cách `CommunityController::joinGroup` đã làm cho member_count.
        $row = ListingInquiry::firstOrCreate(
            [
                'real_estate_listing_id' => $model->id,
                'user_id' => $user->id,
                'kind' => ListingInquiry::KIND_INTEREST,
            ],
            ['resident_id' => $residentId, 'status' => 'new'],
        );

        if ($row->wasRecentlyCreated) {
            RealEstateListing::withoutGlobalScope('tenant')->where('id', $model->id)->increment('interest_count');
        }

        return ApiResponse::success($this->one($request, $model->fresh()));
    }

    /** DELETE /resident/listings/{id}/interest — bỏ "Quan tâm". */
    public function uninterest(Request $request, int $listing): JsonResponse
    {
        $found = $this->findInteractable($request, $listing, allowOwnRead: true);
        if ($found instanceof JsonResponse) {
            return $found;
        }
        [$model] = $found;

        $user = $request->user();
        $deleted = ListingInquiry::query()
            ->where('real_estate_listing_id', $model->id)
            ->where('user_id', $user->id)
            ->where('kind', ListingInquiry::KIND_INTEREST)
            ->delete();

        if ($deleted > 0) {
            // Sàn 0 — dữ liệu có thể lệch, trừ xuống âm thì hiển thị vô nghĩa.
            RealEstateListing::withoutGlobalScope('tenant')->where('id', $model->id)
                ->where('interest_count', '>', 0)->decrement('interest_count');
        }

        return ApiResponse::success($this->one($request, $model->fresh()));
    }

    /** POST /resident/listings/{id}/inquiries — kind=viewing|contact. */
    public function inquire(Request $request, int $listing): JsonResponse
    {
        $found = $this->findInteractable($request, $listing);
        if ($found instanceof JsonResponse) {
            return $found;
        }
        [$model, $errorIfOwn] = $found;
        if ($errorIfOwn) {
            return $errorIfOwn;
        }

        $data = $request->validate([
            'kind' => ['required', 'string', 'in:viewing,contact'],
            'preferred_at' => ['required_if:kind,viewing', 'nullable', 'date', 'after:now'],
            'name' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:20'],
            'message' => ['nullable', 'string', 'max:500'],
        ]);

        if ($data['kind'] === 'contact' && empty($data['phone']) && empty($data['message'])) {
            return ApiResponse::error(
                'validation_failed',
                'Nhập số điện thoại hoặc lời nhắn để người bán liên hệ lại.',
                422,
                ['phone' => ['Cần số điện thoại hoặc lời nhắn.']],
            );
        }

        $user = $request->user();
        $residentId = $user->residentMemberships()->value('id');

        $created = false;
        DB::transaction(function () use (&$created, $model, $user, $residentId, $data) {
            // Một lead / người / loại — gửi lại (đổi giờ xem nhà, viết lại lời
            // nhắn) thì CẬP NHẬT lead đó, không tạo thêm dòng rác trong hộp thư
            // của người bán.
            $row = ListingInquiry::updateOrCreate(
                [
                    'real_estate_listing_id' => $model->id,
                    'user_id' => $user->id,
                    'kind' => $data['kind'],
                ],
                [
                    'resident_id' => $residentId,
                    'name' => $data['name'] ?? $user->name,
                    'phone' => $data['phone'] ?? null,
                    'message' => $data['message'] ?? null,
                    'preferred_at' => $data['kind'] === 'viewing' ? ($data['preferred_at'] ?? null) : null,
                    'status' => 'new',
                ],
            );
            $created = $row->wasRecentlyCreated;
        });

        if ($created) {
            RealEstateListing::withoutGlobalScope('tenant')->where('id', $model->id)->increment('inquiry_count');
        }

        return ApiResponse::success($this->one($request, $model->fresh()), [], $created ? 201 : 200);
    }

    /**
     * POST /resident/listings/{id}/moderate — BQL duyệt/từ chối.
     * Route nằm ở nhóm `ability:resident,staff`, giống
     * `CommunityPostController::moderate` — nhân sự BQL dùng cùng app/API.
     */
    public function moderate(Request $request, int $listing): JsonResponse
    {
        $data = $request->validate([
            'action' => ['required', 'string', 'in:approve,reject'],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $model = RealEstateListing::withoutGlobalScope('tenant')->find($listing);
        if ($model === null) {
            return ApiResponse::error('not_found', 'Không tìm thấy tin rao.', 404);
        }

        $user = $request->user();
        // Nhân sự BQL có tenant_id thật — chặn duyệt tin của tenant khác. Tài
        // khoản cư dân (tenant_id null) không lọt qua middleware ability ở
        // route này nên nhánh còn lại luôn là staff.
        if ($user->tenant_id !== null && (int) $model->tenant_id !== (int) $user->tenant_id) {
            return ApiResponse::error('forbidden', 'Bạn không quản lý dự án này.', 403);
        }

        $reason = trim((string) ($data['reason'] ?? ''));
        if ($data['action'] === 'reject' && $reason === '') {
            return ApiResponse::error(
                'validation_failed',
                'Cần nhập lý do từ chối — cư dân sẽ nhìn thấy lý do này.',
                422,
                ['reason' => ['Bắt buộc nhập lý do.']],
            );
        }

        $now = now();
        if ($data['action'] === 'approve') {
            $model->forceFill([
                'approval_status' => 'approved',
                'approved_by_user_id' => $user->id,
                'approved_at' => $now,
                'rejection_reason' => null,
                'published_at' => $model->published_at ?? $now,
            ])->save();
            $this->feed->publish($model);
        } else {
            $model->forceFill([
                'approval_status' => 'rejected',
                'approved_by_user_id' => $user->id,
                'approved_at' => $now,
                'rejection_reason' => $reason,
            ])->save();
        }

        return ApiResponse::success($this->one($request, $model->fresh()));
    }

    // ── Helpers ────────────────────────────────────────────────────────────────

    /**
     * Tin trong phạm vi TƯƠNG TÁC của người xem + có bị chặn vì là tin của
     * chính mình hay không (một số hành động — quan tâm/liên hệ — không áp
     * dụng cho tin của chính mình).
     *
     * Trả `[model, errorResponseOrNull]`, hoặc thẳng `JsonResponse` khi bị
     * chặn ở tầng phạm vi/hiển thị (404/403) — gọi nơi dùng phải kiểm
     * `instanceof JsonResponse` trước.
     *
     * @return array{0:RealEstateListing,1:?JsonResponse}|JsonResponse
     */
    private function findInteractable(Request $request, int $id, bool $allowOwnRead = false): array|JsonResponse
    {
        $model = RealEstateListing::withoutGlobalScope('tenant')->find($id);
        if ($model === null) {
            return ApiResponse::error('not_found', 'Không tìm thấy tin rao.', 404);
        }

        $user = $request->user();
        $isOwn = $model->created_by_user_id !== null && $model->created_by_user_id === $user->id;

        // Cách ly BẮT BUỘC: dự án khác (có thể khác TENANT) → 403, kể cả khi
        // tin đã duyệt. Không tin id client gửi lên là phạm vi hợp lệ.
        $visibleProjectIds = $this->access->visibleProjectIds($user, $request->header('X-Context-Id'));
        if (! $isOwn && ! in_array($model->project_id, $visibleProjectIds, true)) {
            return ApiResponse::error('forbidden', 'Bạn không xem được tin rao của dự án này.', 403);
        }

        // Trong phạm vi dự án nhưng CHƯA công khai (chờ duyệt/bị từ chối/hết
        // hiệu lực) → 404 chứ không phải 403, để không lộ là tin có tồn tại —
        // giống nguyên tắc `findVisible` của bài cộng đồng.
        if (! $isOwn && ! $model->isPubliclyVisible()) {
            return ApiResponse::error('not_found', 'Tin rao không còn khả dụng.', 404);
        }

        if ($isOwn && ! $allowOwnRead) {
            return [$model, ApiResponse::error('own_listing', 'Không thể tự tương tác với tin của chính bạn.', 422)];
        }

        return [$model, null];
    }

    private function one(Request $request, RealEstateListing $listing): array
    {
        $this->attachListingMeta($request, new Collection([$listing]));

        return RealEstateListingResource::make($listing)->resolve($request);
    }
}
