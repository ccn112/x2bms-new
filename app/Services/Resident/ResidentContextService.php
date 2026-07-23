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
            $relations = $relations->where('id', $relationId);
        }

        return $relations->pluck('apartment_id')->unique()->values()->all();
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
