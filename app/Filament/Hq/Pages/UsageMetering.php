<?php

namespace App\Filament\Hq\Pages;

use App\Filament\Concerns\HqScreen;
use App\Models\BillingRateCard;
use App\Models\UsageRecord;
use App\Support\Context\CurrentContext;
use BackedEnum;
use Filament\Pages\Page;

/** HQ-02-05 — Chi tiết usage / metering. */
class UsageMetering extends Page
{
    use HqScreen;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar';

    protected static string|\UnitEnum|null $navigationGroup = 'Billing & Gói dịch vụ';

    protected static ?string $navigationLabel = 'Usage / Metering';

    protected static ?int $navigationSort = 5;

    protected static ?string $title = 'Chi tiết usage / metering';

    protected static ?string $slug = 'billing/usage';

    protected string $view = 'filament.hq.pages.usage-metering';

    protected function getViewData(): array
    {
        $tid = app(CurrentContext::class)->tenantId();
        $rates = BillingRateCard::where('is_active', true)->get()->keyBy('meter_code');
        $labels = ['sms' => 'SMS Brandname', 'zalo' => 'Zalo ZNS', 'email' => 'Email', 'payment_gateway' => 'Payment Gateway', 'platform' => 'Dự án (nền tảng)'];

        $rows = UsageRecord::where('tenant_id', $tid)->get()->map(function ($u) use ($rates, $labels) {
            $rate = $rates->get($u->meter_type);
            $cost = (float) ($rate?->unit_price ?? 0) * (int) $u->usage_value;

            return [
                'meter' => $labels[$u->meter_type] ?? $u->meter_type,
                'used' => (int) $u->usage_value, 'limit' => (int) $u->included_limit,
                'overage' => (int) $u->overage_value, 'unit' => (float) ($rate?->unit_price ?? 0),
                'cost' => $cost, 'pct' => $u->included_limit ? round($u->usage_value / $u->included_limit * 100, 1) : 0,
            ];
        })->sortByDesc('cost')->values();

        return ['rows' => $rows, 'totalCost' => (float) $rows->sum('cost')];
    }
}
