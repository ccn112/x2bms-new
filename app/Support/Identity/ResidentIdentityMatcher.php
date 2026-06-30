<?php

namespace App\Support\Identity;

use App\Models\User;

/**
 * Resolves a per-tenant resident record to the GLOBAL X2BMS account (person).
 *
 * Match key is CCCD (id_no), phone as fallback — never the name, which is exactly
 * the field that diverges between what each BQL typed and the person's KYC name.
 *
 * Tenant isolation: matches only against global `users` (account_type=resident).
 * It never reveals resident records belonging to other companies/tenants, so a
 * BQL only ever sees "this CCCD already has a verified X2BMS account", not where
 * else that person lives.
 */
class ResidentIdentityMatcher
{
    public function findAccount(?string $idNo, ?string $phone = null): ?User
    {
        $idNo = trim((string) $idNo) ?: null;
        $phone = trim((string) $phone) ?: null;

        if (! $idNo && ! $phone) {
            return null;
        }

        return User::query()
            ->where('account_type', 'resident')
            ->where(function ($q) use ($idNo, $phone) {
                if ($idNo) {
                    $q->orWhere('id_no', $idNo);
                }
                if ($phone) {
                    $q->orWhere('phone', $phone);
                }
            })
            ->first();
    }
}
