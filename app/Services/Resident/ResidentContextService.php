<?php

namespace App\Services\Resident;

use App\Models\Apartment;
use App\Models\Building;
use App\Models\User;

/**
 * Resolves which apartments the current resident user may see. Residents have
 * tenant_id = NULL, so the BelongsToTenant global scope is a no-op for them — every
 * resident-facing query MUST scope explicitly by these apartment ids (never rely on
 * the tenant scope). See docs/ARCHITECTURE_X2_PLATFORM_V1.md §5.
 */
class ResidentContextService
{
    /**
     * Apartment ids the user has an active relation to. When $contextId is given
     * (from X-Context-Id = "apartment:<relationId>"), narrows to that one apartment.
     *
     * KHÔNG có $contextId thì thu hẹp về **một căn** — căn `is_primary`, không có
     * thì căn có id nhỏ nhất. Trước 2026-07-30 nhánh này trả về TẤT CẢ các căn,
     * và đó là một lỗ rò dữ liệu thật: app chỉ gửi `X-Context-Id` sau khi cư dân
     * tự mở bảng chọn căn hộ, nên ngay sau khi đăng nhập mọi truy vấn đi ra
     * không kèm header. Người có căn ở hai dự án thấy feed/sự kiện/khảo sát của
     * cả hai trộn vào nhau — mà hai dự án có thể thuộc HAI TENANT khác nhau.
     * Tệ hơn: nhãn dự án trên header lại lấy căn primary, nên giao diện ghi một
     * dự án trong khi dữ liệu là của mọi dự án.
     *
     * Thu hẹp về một căn cũng đúng hợp đồng sản phẩm: app cư dân luôn đứng ở
     * MỘT căn hộ, mọi nội dung ăn theo dự án của căn đó.
     *
     * @return array<int>
     */
    public function apartmentIds(User $user, ?string $contextId = null): array
    {
        $relations = $user->residentMemberships()
            ->with('apartmentRelations')
            ->get()
            ->flatMap->apartmentRelations;

        if ($contextId && str_starts_with($contextId, 'apartment:')) {
            $relationId = (int) substr($contextId, strlen('apartment:'));

            return $relations->where('id', $relationId)
                ->pluck('apartment_id')->unique()->values()->all();
        }

        // Tie-break theo id để hai lần gọi liên tiếp không ra hai căn khác nhau
        // (thứ tự quan hệ không được bảo đảm), và để khớp cách app chọn nhãn:
        // primary trước, không có thì căn đầu danh sách.
        $default = $relations->sortBy('id')->firstWhere('is_primary', true)
            ?? $relations->sortBy('id')->first();

        return $default === null ? [] : [$default->apartment_id];
    }

    /**
     * Building ids derived from the user's apartments (for building-wide notices etc.).
     * Bỏ qua global scope tenant vì cư dân tenant_id = NULL.
     *
     * @return array<int>
     */
    public function buildingIds(User $user, ?string $contextId = null): array
    {
        $apartmentIds = $this->apartmentIds($user, $contextId);
        if (empty($apartmentIds)) {
            return [];
        }

        return Apartment::query()
            ->whereIn('id', $apartmentIds)
            ->pluck('building_id')
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Project ids của user (community/market/loyalty scope theo dự án).
     * buildingIds → buildings.project_id.
     *
     * @return array<int>
     */
    public function projectIds(User $user, ?string $contextId = null): array
    {
        $buildingIds = $this->buildingIds($user, $contextId);
        if (empty($buildingIds)) {
            return [];
        }

        return Building::query()
            ->whereIn('id', $buildingIds)
            ->pluck('project_id')
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Tenant ids của user (offers/voucher toàn tenant). Cư dân tenant_id=NULL nên suy
     * từ apartments.tenant_id.
     *
     * @return array<int>
     */
    public function tenantIds(User $user, ?string $contextId = null): array
    {
        $apartmentIds = $this->apartmentIds($user, $contextId);
        if (empty($apartmentIds)) {
            return [];
        }

        return Apartment::query()
            ->whereIn('id', $apartmentIds)
            ->pluck('tenant_id')
            ->filter()
            ->unique()
            ->values()
            ->all();
    }
}
