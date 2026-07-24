<?php

namespace App\Services\Resident;

use App\Models\User;
use App\Models\Voucher;
use Illuminate\Database\Eloquent\Builder;

/**
 * Vouchers cư dân được thấy (tab Ưu đãi — offers + quà đổi điểm).
 *
 * Cư dân có tenant_id=NULL → BelongsToTenant global scope là no-op → phải scope
 * TƯỜNG MINH theo tenant của user. Hai nguồn hợp nhất:
 *   1. Voucher của tenant mình  (owner_level='tenant', tenant_id ∈ tenantIds)
 *   2. Voucher nền tảng (SA)     (owner_level='platform') đã ROLLOUT xuống tenant
 *      mình và đang trong kỳ (voucher_tenant: status active, now ∈ [starts_at, ends_at]).
 *
 * Chỉ trả voucher status='active' còn hạn (valid_to >= hôm nay hoặc không hạn).
 * Xem docs/contracts/RESIDENT_API_DOMAIN.md §3/§4.
 */
class VoucherVisibilityService
{
    public function __construct(private readonly ResidentContextService $context)
    {
    }

    /**
     * Query voucher hiển thị cho user. Caller thêm filter points_cost + phân trang.
     */
    public function visibleQuery(User $user, ?string $contextId = null): Builder
    {
        $tenantIds = $this->context->tenantIds($user, $contextId);

        // Không thuộc tenant nào → không thấy voucher nào.
        if (empty($tenantIds)) {
            return Voucher::query()->whereRaw('1 = 0');
        }

        $today = now()->toDateString();

        return Voucher::query()
            ->where('status', 'active')
            ->where(function (Builder $q) use ($today) {
                $q->whereNull('valid_to')->orWhereDate('valid_to', '>=', $today);
            })
            ->where(function (Builder $q) use ($tenantIds) {
                // Nguồn 1: voucher của tenant mình.
                $q->where(function (Builder $own) use ($tenantIds) {
                    $own->where('owner_level', 'tenant')
                        ->whereIn('tenant_id', $tenantIds);
                })
                    // Nguồn 2: voucher platform đã rollout tới tenant mình, đang trong kỳ.
                    ->orWhere(function (Builder $plat) use ($tenantIds) {
                        $plat->where('owner_level', 'platform')
                            ->whereExists(function ($sub) use ($tenantIds) {
                                $sub->from('voucher_tenant')
                                    ->whereColumn('voucher_tenant.voucher_id', 'vouchers.id')
                                    ->whereIn('voucher_tenant.tenant_id', $tenantIds)
                                    ->where('voucher_tenant.status', 'active')
                                    ->whereRaw('NOW() >= voucher_tenant.starts_at')
                                    ->where(function ($w) {
                                        $w->whereNull('voucher_tenant.ends_at')
                                            ->orWhereRaw('NOW() <= voucher_tenant.ends_at');
                                    });
                            });
                    });
            });
    }
}
