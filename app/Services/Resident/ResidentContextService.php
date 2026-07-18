<?php

namespace App\Services\Resident;

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
}
