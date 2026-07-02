<?php

namespace App\Filament\Hq\Pages;

use App\Filament\Concerns\HqScreen;
use App\Models\Expense;
use App\Models\MetricSnapshot;
use App\Support\Context\CurrentContext;
use BackedEnum;
use Filament\Pages\Page;

/** HQ-05-07 — Thu chi & dòng tiền đa dự án. */
class Cashflow extends Page
{
    use HqScreen;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-arrow-trending-up';

    protected static string|\UnitEnum|null $navigationGroup = 'Báo cáo';

    protected static ?string $navigationLabel = 'Thu chi đa dự án';

    protected static ?int $navigationSort = 7;

    protected static ?string $title = 'Thu chi & dòng tiền đa dự án';

    protected static ?string $slug = 'finance/cashflow';

    protected string $view = 'filament.hq.pages.cashflow';

    protected function getViewData(): array
    {
        $tid = app(CurrentContext::class)->tenantId();
        $kpi = MetricSnapshot::where('tenant_id', $tid)->where('metric_key', 'cashflow_kpi')->get()
            ->mapWithKeys(fn ($m) => [$m->dimension['metric'] => (float) $m->value]);
        $projects = MetricSnapshot::where('tenant_id', $tid)->where('metric_key', 'project_cashflow')->get()
            ->sortBy(fn ($m) => $m->dimension['sort'] ?? 0)->map(fn ($m) => $m->dimension)->values();
        $pending = Expense::where('tenant_id', $tid)->where('status', 'pending')->latest('id')->get()
            ->map(fn ($e) => ['desc' => $e->description, 'amount' => (float) $e->amount, 'category' => $e->category]);

        return ['kpi' => $kpi, 'projects' => $projects, 'pending' => $pending];
    }
}
