<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Services\Localization\LocaleResolver;
use App\Services\Localization\LocalizationBootstrap;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Resident/BQL locale preference + localization bootstrap (I18N-003/004).
 *
 * Reads never write; the write path (updatePreference) upserts user_locale_preferences
 * and emits an audit event. Resolution order and supported-locale gating live in
 * LocaleResolver — this controller stays thin (x2bms-laravel-domain rules).
 */
final class LocaleController extends ApiController
{
    public function __construct(
        private readonly LocaleResolver $resolver,
        private readonly LocalizationBootstrap $bootstrap,
    ) {
    }

    /** GET /api/v1/localization/bootstrap — locale settings, supported locales, pack versions. */
    public function bootstrap(Request $request): JsonResponse
    {
        [$userId, $tenantId, $projectId] = $this->context($request);

        return ApiResponse::success(
            $this->bootstrap->build($request, $userId, $tenantId, $projectId),
        );
    }

    /** PATCH /api/v1/me/localization-preference — explicit locale + auto-translate opt-in. */
    public function updatePreference(Request $request): JsonResponse
    {
        [$userId, $tenantId, $projectId] = $this->context($request);

        abort_if($userId === null, 401);

        $supported = $this->resolver->supportedLocales($tenantId, $projectId)->all();

        $validated = $request->validate([
            'locale' => ['required', 'string', Rule::in($supported)],
            'follow_device' => ['required', 'boolean'],
            'auto_translate_content' => ['required', 'boolean'],
        ]);

        DB::table('user_locale_preferences')->updateOrInsert(
            ['user_id' => $userId],
            [
                'locale' => $validated['locale'],
                'follow_device' => $validated['follow_device'],
                'auto_translate_content' => $validated['auto_translate_content'],
                'updated_at' => now(),
                'created_at' => now(),
            ],
        );

        // Audit trail for the preference change. The canonical audit service can subscribe
        // to this event; kept as an event so this write path has no cross-domain coupling.
        event('localization.preference.updated', [[
            'user_id' => $userId,
            'locale' => $validated['locale'],
            'follow_device' => $validated['follow_device'],
            'auto_translate_content' => $validated['auto_translate_content'],
            'tenant_id' => $tenantId,
            'project_id' => $projectId,
        ]]);

        return ApiResponse::success([
            'message' => __('x2.x2.api.api.locale_updated'),
            'locale' => $validated['locale'],
            'follow_device' => $validated['follow_device'],
            'auto_translate_content' => $validated['auto_translate_content'],
        ]);
    }

    /**
     * Resolve (userId, tenantId, projectId) from the authenticated principal + request
     * context attributes. Tenant/project attributes are set by the platform context
     * middleware where present; null is a safe default (falls back to global locales).
     *
     * @return array{0:?int,1:?int,2:?int}
     */
    private function context(Request $request): array
    {
        $userId = $request->user()?->getAuthIdentifier();
        $tenantId = $request->attributes->get('tenant_id');
        $projectId = $request->attributes->get('project_id');

        return [
            is_numeric($userId) ? (int) $userId : null,
            is_numeric($tenantId) ? (int) $tenantId : null,
            is_numeric($projectId) ? (int) $projectId : null,
        ];
    }
}
