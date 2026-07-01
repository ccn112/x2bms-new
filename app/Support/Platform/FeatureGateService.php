<?php

namespace App\Support\Platform;

use App\Models\Feature;
use App\Models\TenantSubscription;
use App\Models\TenantEntitlement;
use App\Models\TenantModuleOverride;
use App\Models\Tenant;
use Illuminate\Support\Collection;

/**
 * Addendum — Feature gate tập trung. Quyền hiệu lực giải theo thứ tự:
 *   plan_features + tenant_entitlements + tenant_module_overrides
 *   - expired_subscription - suspended_tenant = effective_feature_access
 *
 * KHÔNG hardcode hiển thị gói trong Blade/Filament — luôn hỏi service này.
 */
class FeatureGateService
{
    /** @return Collection<string> mã feature (feature.code) đang bật cho tenant */
    public function effectiveFeatureCodes(Tenant $tenant): Collection
    {
        // Tenant bị khoá → không có quyền.
        if (($tenant->status ?? 'active') === 'suspended') {
            return collect();
        }

        $enabled = collect();

        // 1) Từ gói của subscription còn hiệu lực.
        $sub = TenantSubscription::where('tenant_id', $tenant->id)
            ->whereIn('status', ['active', 'trial'])
            ->latest('start_date')->first();

        if ($sub && ! $this->subscriptionExpired($sub) && $sub->plan_id) {
            $planFeatureIds = \DB::table('plan_features')
                ->where('plan_id', $sub->plan_id)->where('enabled', true)->pluck('feature_id');
            $enabled = $enabled->merge($planFeatureIds);
        }

        // 2) Cộng/trừ theo tenant_entitlements (manual_override/add_on/trial).
        $now = now();
        foreach (TenantEntitlement::where('tenant_id', $tenant->id)->get() as $ent) {
            $active = (! $ent->starts_at || $ent->starts_at <= $now) && (! $ent->ends_at || $ent->ends_at >= $now);
            if (! $active) {
                continue;
            }
            $ent->enabled ? $enabled->push($ent->feature_id) : $enabled = $enabled->reject(fn ($id) => $id === $ent->feature_id);
        }

        $featureIds = $enabled->unique()->values();

        // 3) Trừ feature thuộc module bị override tắt.
        $disabledModuleIds = TenantModuleOverride::where('tenant_id', $tenant->id)->where('enabled', false)->pluck('module_id');
        $codes = Feature::whereIn('id', $featureIds)
            ->when($disabledModuleIds->isNotEmpty(), fn ($q) => $q->whereNotIn('module_id', $disabledModuleIds))
            ->pluck('code');

        return $codes->unique()->values();
    }

    public function tenantHasFeature(Tenant $tenant, string $featureCode): bool
    {
        return $this->effectiveFeatureCodes($tenant)->contains($featureCode);
    }

    public function moduleEnabled(Tenant $tenant, string $moduleCode): bool
    {
        $override = TenantModuleOverride::where('tenant_id', $tenant->id)
            ->whereHas('module', fn ($q) => $q->where('code', $moduleCode))->first();
        if ($override) {
            return (bool) $override->enabled;
        }

        // Mặc định: module bật nếu có ít nhất 1 feature hiệu lực thuộc module đó.
        $codes = $this->effectiveFeatureCodes($tenant);

        return Feature::whereHas('module', fn ($q) => $q->where('code', $moduleCode))
            ->whereIn('code', $codes)->exists();
    }

    private function subscriptionExpired(TenantSubscription $sub): bool
    {
        return $sub->end_date && $sub->end_date->isPast();
    }
}
