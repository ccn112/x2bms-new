<?php

namespace App\Services\Resident;

use App\Models\Apartment;
use App\Models\ListingPostingGrant;
use App\Models\Project;
use App\Models\ResidentApartmentRelation;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Quyền liên quan tới TIN RAO (real_estate_listings) — tách khỏi
 * [ResidentContextService] vì hai câu hỏi khác nhau: "cư dân xem được dự án
 * nào" (căn hộ đang đứng) vs "cư dân/người quan tâm được TƯƠNG TÁC tin rao của
 * dự án nào" (rộng hơn — gồm cả người CHƯA mua nhà nhưng đã đánh dấu quan tâm
 * dự án lúc đăng ký).
 */
class ListingAccessService
{
    public function __construct(private readonly ResidentContextService $context) {}

    /**
     * Dự án người dùng được phép TƯƠNG TÁC tin rao — hợp của:
     *   - dự án có căn hộ thật (chủ hoặc thuê) — theo ngữ cảnh đang chọn.
     *   - dự án đã đánh dấu QUAN TÂM lúc đăng ký (quyết định #3: có tài khoản
     *     mà chưa là cư dân vẫn được để lại thông tin liên hệ).
     *
     * Không gộp làm một với `projectIds()` gốc vì endpoint đọc feed/hoá đơn
     * KHÔNG được nới cho người chưa mua nhà — chỉ tin rao mới nới, vì tin rao
     * vốn dĩ là nội dung "mời chào" người ngoài.
     *
     * @return array<int>
     */
    public function visibleProjectIds(User $user, ?string $contextId = null): array
    {
        $apartmentBased = $this->context->projectIds($user, $contextId);

        $publicProjectIds = $user->interestedProjects()->pluck('public_projects.id')->all();
        $interestBased = empty($publicProjectIds) ? [] : Project::withoutGlobalScope('tenant')
            ->whereIn('public_project_id', $publicProjectIds)
            ->pluck('id')
            ->all();

        return array_values(array_unique(array_merge($apartmentBased, $interestBased)));
    }

    /**
     * Cư dân này có được RAO tin cho căn hộ đó không — và nếu có, trả về
     * resident_id đứng tên (để ghi `owner_resident_id`/`created_by`).
     *
     * Quyết định #1: chủ căn (role=owner) rao trực tiếp; người thuê/môi giới
     * phải có `listing_posting_grants` còn hiệu lực cho ĐÚNG cặp (căn, người).
     */
    public function residentAllowedToPost(User $user, int $apartmentId): ?int
    {
        $residentIds = $user->residentMemberships()->pluck('id')->all();
        if (empty($residentIds)) {
            return null;
        }

        $relation = ResidentApartmentRelation::withoutGlobalScope('tenant')
            ->where('apartment_id', $apartmentId)
            ->whereIn('resident_id', $residentIds)
            ->first();

        if ($relation === null) {
            // Tài khoản này không gắn với căn hộ đó — không thể suy quyền rao.
            return null;
        }

        if ($relation->role === 'owner') {
            return $relation->resident_id;
        }

        $granted = ListingPostingGrant::query()
            ->where('apartment_id', $apartmentId)
            ->where('resident_id', $relation->resident_id)
            ->where('status', ListingPostingGrant::STATUS_ACTIVE)
            ->exists();

        return $granted ? $relation->resident_id : null;
    }

    /**
     * Dự án của một căn hộ. `apartments` KHÔNG có cột `project_id` trực tiếp —
     * project suy qua `apartments.building_id → buildings.project_id` (xem
     * `BelongsToProject`). Tra bằng sub-select một lượt, không N+1.
     */
    public function projectIdForApartment(int $apartmentId): ?int
    {
        $apartment = Apartment::withoutGlobalScope('tenant')->find($apartmentId);
        if ($apartment === null || $apartment->building_id === null) {
            return null;
        }

        return DB::table('buildings')->where('id', $apartment->building_id)->value('project_id');
    }

    /** Dự án của một căn hộ (dùng để tra `listings_auto_approve`). */
    public function projectFor(int $apartmentId): ?Project
    {
        $projectId = $this->projectIdForApartment($apartmentId);

        return $projectId === null ? null : Project::withoutGlobalScope('tenant')->find($projectId);
    }
}
