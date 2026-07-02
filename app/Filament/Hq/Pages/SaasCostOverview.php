<?php

namespace App\Filament\Hq\Pages;

use App\Filament\Concerns\HqScreen;
use App\Models\MetricSnapshot;
use App\Models\ProjectSubscriptionPeriod;
use App\Models\UsageRecord;
use App\Models\Wallet;
use App\Support\Context\CurrentContext;
use BackedEnum;
use Filament\Pages\Page;

/**
 * HQ-02-01 — Tổng quan chi phí SaaS công ty.
 * KPI + xu hướng 6 tháng + cơ cấu chi phí (donut) + dự án chi phí cao nhất + hạn mức usage.
 */
class SaasCostOverview extends Page
{
    use HqScreen;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-credit-card';

    protected static string|\UnitEnum|null $navigationGroup = 'Billing & Gói dịch vụ';

    protected static ?string $navigationLabel = 'Tổng quan chi phí SaaS';

    protected static ?int $navigationSort = 1;

    protected static ?string $title = 'Tổng quan chi phí SaaS công ty';

    protected static ?string $slug = 'billing/overview';

    protected string $view = 'filament.hq.pages.saas-cost-overview';

    protected function getViewData(): array
    {
        $tid = app(CurrentContext::class)->tenantId();
        $period = '2026-07';

        $components = MetricSnapshot::where('tenant_id', $tid)->where('metric_key', 'cost_component')->where('period', $period)->get();
        $total = (float) $components->sum('value');
        $platformFee = (float) $components->firstWhere('dimension.channel', 'platform_fee')?->value;
        $passThrough = $total - $platformFee;

        $trend = MetricSnapshot::where('tenant_id', $tid)->where('metric_key', 'monthly_cost')->orderBy('period')->get()
            ->map(fn ($m) => ['period' => $m->period, 'value' => (float) $m->value]);

        $compColors = ['platform_fee' => '#2563eb', 'sms' => '#f59e0b', 'zalo' => '#10b981', 'email' => '#8b5cf6', 'payment_gateway' => '#14b8a6', 'other' => '#94a3b8'];
        $compLabels = ['platform_fee' => 'Phí nền tảng', 'sms' => 'SMS', 'zalo' => 'Zalo', 'email' => 'Email', 'payment_gateway' => 'Payment Gateway', 'other' => 'Khác'];
        $donut = $components->map(fn ($c) => [
            'label' => $compLabels[$c->dimension['channel']] ?? $c->dimension['channel'],
            'value' => (float) $c->value, 'color' => $compColors[$c->dimension['channel']] ?? '#94a3b8',
            'pct' => $total ? round($c->value / $total * 100, 1) : 0,
        ])->sortByDesc('value')->values();

        $topProjects = MetricSnapshot::where('tenant_id', $tid)->where('metric_key', 'project_cost')->where('period', $period)
            ->orderByDesc('value')->get()->map(fn ($m) => [
                'name' => $m->dimension['project'] ?? '—', 'value' => (float) $m->value,
                'pct' => $total ? round($m->value / $total * 100, 1) : 0,
            ]);

        $wallet = Wallet::where('tenant_id', $tid)->first();
        $projectsUsing = ProjectSubscriptionPeriod::where('tenant_id', $tid)->distinct('project_id')->count('project_id');

        $usage = UsageRecord::where('tenant_id', $tid)->whereIn('meter_type', ['sms', 'zalo', 'email'])->get()
            ->map(fn ($u) => ['meter' => strtoupper($u->meter_type), 'used' => (int) $u->usage_value,
                'limit' => (int) $u->included_limit, 'pct' => $u->included_limit ? round($u->usage_value / $u->included_limit * 100, 1) : 0]);

        return [
            'total' => $total, 'platformFee' => $platformFee, 'passThrough' => $passThrough,
            'walletBalance' => (float) ($wallet?->balance ?? 0), 'walletLimit' => (float) ($wallet?->credit_limit ?? 0),
            'projectsUsing' => $projectsUsing,
            'trend' => $trend, 'donut' => $donut, 'topProjects' => $topProjects, 'usage' => $usage,
        ];
    }
}
