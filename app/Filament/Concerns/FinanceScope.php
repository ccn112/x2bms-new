<?php

namespace App\Filament\Concerns;

use App\Models\Building;
use App\Models\BillingPeriod;
use App\Support\Context\CurrentContext;

/**
 * Shared helpers for the BQL-03 finance screens: scope to the topbar building
 * (the project's primary building), the current billing period, and VND money
 * formatting. Keeps every finance page consistent with the seeded backbone.
 */
trait FinanceScope
{
    protected function financeBuildingId(): int
    {
        $pid = app(CurrentContext::class)->projectId();

        return (int) (Building::where('project_id', $pid)->orderBy('id')->value('id') ?? 0);
    }

    protected function currentPeriod(): ?BillingPeriod
    {
        $bid = $this->financeBuildingId();

        return BillingPeriod::where('building_id', $bid)->where('is_current', true)->first()
            ?? BillingPeriod::where('building_id', $bid)->orderByDesc('period_month')->first();
    }

    /** Full VND: "12.450.000". */
    public static function money(float $v): string
    {
        return number_format($v, 0, ',', '.');
    }

    /** Compact VND: "8,42 tỷ" / "650 triệu" / "12.450.000". */
    public static function moneyCompact(float $v): string
    {
        if ($v >= 1_000_000_000) {
            return rtrim(rtrim(number_format($v / 1_000_000_000, 2, ',', '.'), '0'), ',').' tỷ';
        }
        if ($v >= 1_000_000) {
            return number_format($v / 1_000_000, 0, ',', '.').' triệu';
        }

        return number_format($v, 0, ',', '.');
    }
}
