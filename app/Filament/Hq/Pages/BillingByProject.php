<?php

namespace App\Filament\Hq\Pages;

use App\Filament\Concerns\HqScreen;
use App\Models\MetricSnapshot;
use App\Models\Plan;
use App\Models\Project;
use App\Models\ProjectSubscriptionPeriod;
use App\Support\Context\CurrentContext;
use BackedEnum;
use Filament\Pages\Page;

/** HQ-02-02 — Chi tiết billing theo dự án. */
class BillingByProject extends Page
{
    use HqScreen;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-building-office';

    protected static string|\UnitEnum|null $navigationGroup = 'Billing & Gói dịch vụ';

    protected static ?string $navigationLabel = 'Billing theo dự án';

    protected static ?int $navigationSort = 2;

    protected static ?string $title = 'Chi tiết billing theo dự án';

    protected static ?string $slug = 'billing/by-project';

    protected string $view = 'filament.hq.pages.billing-by-project';

    protected function getViewData(): array
    {
        $tid = app(CurrentContext::class)->tenantId();
        $projects = Project::where('tenant_id', $tid)->orderBy('code')->get();
        $periods = ProjectSubscriptionPeriod::whereIn('project_id', $projects->pluck('id'))->get()->keyBy('project_id');
        $planNames = Plan::pluck('name', 'id');
        $planPrices = Plan::pluck('monthly_base_price', 'id');
        $costByProject = MetricSnapshot::where('tenant_id', $tid)->where('metric_key', 'project_cost')->where('period', '2026-07')
            ->get()->keyBy('project_id');

        $rows = $projects->map(function ($p) use ($periods, $planNames, $planPrices, $costByProject) {
            $per = $periods->get($p->id);
            $fee = (float) ($planPrices[$per?->plan_id] ?? 0);
            $totalCost = (float) ($costByProject->get($p->id)?->value ?? $fee);

            return [
                'code' => $p->code, 'name' => $p->name, 'status' => $p->status,
                'plan' => $planNames[$per?->plan_id] ?? '—',
                'fee' => $fee, 'passthrough' => max(0, $totalCost - $fee), 'total' => $totalCost,
            ];
        })->sortByDesc('total')->values();

        return ['rows' => $rows, 'grandTotal' => (float) $rows->sum('total')];
    }
}
