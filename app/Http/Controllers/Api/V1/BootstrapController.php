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
        // Không auth → BelongsToTenant/BelongsToProject đều no-op → hiển thị showcase.
        $projects = \App\Models\Project::query()
            ->whereNotIn('status', ['archived', 'inactive'])
            ->orderByDesc('id')
            ->limit(12)
            ->get();

        $featuredProjects = $projects->map(fn ($p) => [
            'id' => (string) $p->id,
            'slug' => $p->code ?: (string) $p->id,
            'name' => $p->name,
            'location' => collect([$p->district, $p->city])->filter()->implode(', ') ?: ($p->address ?? ''),
            'status' => $p->status,
            'image' => \App\Support\DemoImage::url('building,residential,skyline', $p->id, 1200, 700),
            'summary' => $p->description,
        ])->all();

        // Nội dung công khai = thông báo cấp nền tảng (platform) đã publish (an toàn đa tenant).
        $content = \App\Models\Notification::query()
            ->where('owner_level', 'platform')
            ->where('status', 'published')
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->limit(6)
            ->get()
            ->map(fn ($n) => [
                'id' => (string) $n->id,
                'slug' => $n->code ?: (string) $n->id,
                'type' => $n->type,
                'title' => $n->title,
                'published_at' => optional($n->published_at ?? $n->created_at)->toIso8601String(),
                'body' => $n->body ?? $n->summary,
            ])->all();

        return ApiResponse::success([
            'experience_mode' => 'public',
            'branding' => $this->defaultBranding(),
            'city' => ['name' => $projects->first()->city ?? 'TP. Hồ Chí Minh'],
            'enabled_modules' => ['projects', 'content', 'community', 'offers'],
            'featured_projects' => $featuredProjects,
            'content' => $content,
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
                'email' => $user->email,
                'phone' => $user->phone,
                'gender' => $user->gender,
                'nationality' => $user->nationality,
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
