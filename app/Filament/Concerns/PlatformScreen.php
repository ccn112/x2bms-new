<?php

namespace App\Filament\Concerns;

use App\Models\Tenant;
use App\Support\Platform\FeatureGateService;
use Illuminate\Support\Facades\Auth;

/**
 * Addendum WEB-UX-22 — cổng vào cho mọi màn "Nền tảng (SuperAdmin)".
 *
 * Quy tắc hiển thị (AC-01, AC-28..32):
 *  - SuperAdmin (isPlatformAdmin) luôn thấy toàn bộ.
 *  - Tenant operator (HQ) chỉ thấy màn khi gói của tenant BẬT feature tương ứng
 *    (giải qua FeatureGateService — KHÔNG hardcode gói).
 *  - BQL/khác: không thấy.
 *
 * Màn nào chỉ dành riêng SuperAdmin thì để $platformFeatureCode = null.
 * Màn chia sẻ cho HQ (thư viện/KB…) đặt $platformFeatureCode = 'sa_xxx'.
 */
trait PlatformScreen
{
    /** Feature code cho phép HQ (tenant) thấy màn; null = chỉ SuperAdmin. Override ở từng Page. */
    protected static function platformFeature(): ?string
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

        // HQ (tenant operator) chỉ vào được khi màn cho phép + gói bật feature.
        $feature = static::platformFeature();
        if ($feature && $user->isTenantOperator() && $user->tenant_id) {
            $tenant = Tenant::find($user->tenant_id);

            return $tenant
                ? app(FeatureGateService::class)->tenantHasFeature($tenant, $feature)
                : false;
        }

        return false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }
}
