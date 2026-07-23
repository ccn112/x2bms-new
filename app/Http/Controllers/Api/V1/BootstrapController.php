<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\User;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Bootstrap endpoints (docs §4.3 + Flutter foundation bootstrap_response.schema.json).
 * The response tells the app which of the 4 experience modes to render and which
 * contexts (apartment relations / staff scopes) the person may switch into.
 */
class BootstrapController extends ApiController
{
    /** GET /api/v1/public/bootstrap — no auth. Branding + enabled modules + min version. */
    public function public(Request $request): JsonResponse
    {
        return ApiResponse::success([
            'experience_mode' => 'public',
            'branding' => $this->defaultBranding(),
            'enabled_modules' => ['projects', 'content', 'community', 'offers'],
            'minimum_app_version' => config('mobile.min_app_version'),
        ]);
    }

    /** GET /api/v1/me/bootstrap — auth:sanctum. Resolves the person's contexts + mode. */
    public function me(Request $request, \App\Services\Resident\ResidentNotificationService $notifications): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $residentContexts = $user->residentMemberships()
            ->with('apartmentRelations')
            ->get()
            ->flatMap(fn ($resident) => $resident->apartmentRelations->map(fn ($rel) => [
                'context_id' => 'apartment:'.$rel->id,
                'type' => 'resident',
                'apartment_id' => $rel->apartment_id,
                'role' => $rel->role,
                'is_primary' => (bool) $rel->is_primary,
            ]))
            ->values()
            ->all();

        $staffContexts = $user->roleScopes()
            ->get()
            ->map(fn ($scope) => [
                'context_id' => 'scope:'.$scope->id,
                'type' => 'staff',
                'scope_type' => $scope->scope_type,
                'tenant_id' => $scope->tenant_id,
                'project_id' => $scope->project_id,
                'building_id' => $scope->building_id,
            ])
            ->all();

        $contexts = array_merge($residentContexts, $staffContexts);

        return ApiResponse::success([
            'experience_mode' => $this->resolveExperienceMode($user, $residentContexts),
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'kyc_status' => $user->kyc_status,
                'abilities' => $user->tokenAbilities(),
            ],
            'available_contexts' => $contexts,
            'branding' => $this->defaultBranding(),
            'enabled_modules' => ['projects', 'content', 'community', 'offers', 'billing', 'feedback', 'amenities', 'notifications'],
            'minimum_app_version' => config('mobile.min_app_version'),
            'unread_notification_count' => empty($residentContexts)
                ? 0
                : $notifications->unreadCount($user, $request->header('X-Context-Id')),
        ]);
    }

    private function resolveExperienceMode(User $user, array $residentContexts): string
    {
        if (! empty($residentContexts)) {
            return 'verified_resident';
        }
        // A pending application (ResidentApprovalRequest) would flip this to resident_applicant
        // once that slice is wired; for now an authenticated person with no active relation = member.
        return 'member';
    }

    private function defaultBranding(): array
    {
        return [
            'theme_id' => 'navy',
            'app_name' => 'X2 Resident',
        ];
    }
}
