<?php

namespace App\Filament\Hq\Pages;

use App\Filament\Concerns\HqScreen;
use App\Models\BillingRateCard;
use App\Models\MetricSnapshot;
use App\Models\UsageRecord;
use App\Support\Context\CurrentContext;
use BackedEnum;
use Filament\Pages\Page;

/** HQ-02-06 — Pass-through: SMS, Zalo, Email, Payment Gateway. */
class PassThrough extends Page
{
    use HqScreen;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-arrows-right-left';

    protected static string|\UnitEnum|null $navigationGroup = 'Billing & Gói dịch vụ';

    protected static ?string $navigationLabel = 'Pass-through';

    protected static ?int $navigationSort = 6;

    protected static ?string $title = 'Pass-through: SMS, Zalo, Email, Payment Gateway';

    protected static ?string $slug = 'billing/pass-through';

    protected string $view = 'filament.hq.pages.pass-through';

    protected function getViewData(): array
    {
        $tid = app(CurrentContext::class)->tenantId();
        $costs = MetricSnapshot::where('tenant_id', $tid)->where('metric_key', 'cost_component')->where('period', '2026-07')
            ->get()->keyBy(fn ($m) => $m->dimension['channel'] ?? '');
        $usage = UsageRecord::where('tenant_id', $tid)->get()->keyBy('meter_type');
        $rates = BillingRateCard::where('is_active', true)->get()->keyBy('meter_code');

        $channels = [
            'sms' => ['SMS Brandname', 'Viettel / VNPT / MobiFone', '#f59e0b'],
            'zalo' => ['Zalo ZNS', 'Zalo Official Account', '#10b981'],
            'email' => ['Email', 'Amazon SES', '#8b5cf6'],
            'payment_gateway' => ['Payment Gateway', 'VNPAY / MoMo', '#14b8a6'],
        ];

        $rows = collect($channels)->map(fn ($meta, $key) => [
            'name' => $meta[0], 'provider' => $meta[1], 'color' => $meta[2],
            'used' => (int) ($usage->get($key)?->usage_value ?? 0),
            'limit' => (int) ($usage->get($key)?->included_limit ?? 0),
            'unit' => (float) ($rates->get($key)?->unit_price ?? 0),
            'markup' => (float) ($rates->get($key)?->markup_percent ?? 0),
            'cost' => (float) ($costs->get($key)?->value ?? 0),
        ])->values();

        return ['rows' => $rows, 'totalCost' => (float) $rows->sum('cost')];
    }
}
