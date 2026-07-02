<?php

namespace App\Filament\Concerns;

use Illuminate\Support\Facades\Auth;

/**
 * HQ Portal — cổng vào cho mọi màn dưới panel /hq (Tenant HQ / công ty vận hành).
 *
 * Quy tắc hiển thị (HQ_ACCEPTANCE_CRITERIA #1):
 *  - Platform admin (SuperAdmin) luôn thấy (để hỗ trợ/impersonate).
 *  - Tenant operator (công ty vận hành, có scope cấp tenant) thấy toàn bộ HQ.
 *  - BQL/cư dân: không thấy.
 *
 * Màn HQ nào cần feature-gate theo gói của tenant thì override static::hqFeature()
 * và giải qua FeatureGateService (không hardcode gói).
 */
trait HqScreen
{
    /** Feature code yêu cầu (null = mọi HQ operator đều thấy). Override ở từng Page nếu cần. */
    protected static function hqFeature(): ?string
    {
        return null;
    }

    public static function canAccess(): bool
    {
        $user = Auth::user();
        if (! $user) {
            return false;
        }

        if ($user->isPlatformAdmin()) {
            return true;
        }

        if (! $user->isTenantOperator()) {
            return false;
        }

        $feature = static::hqFeature();
        if ($feature && $user->tenant_id) {
            $tenant = \App\Models\Tenant::find($user->tenant_id);

            return $tenant
                ? app(\App\Support\Platform\FeatureGateService::class)->tenantHasFeature($tenant, $feature)
                : false;
        }

        return true;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }
}
