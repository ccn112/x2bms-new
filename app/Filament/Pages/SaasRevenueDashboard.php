<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\PlatformScreen;
use App\Models\BillingInvoice;
use App\Models\Plan;
use App\Models\QuotaAlert;
use App\Models\TenantSubscription;
use App\Models\UsageRecord;
use BackedEnum;
use Filament\Pages\Page;

/**
 * WEB-UX-27-01 — Tổng quan doanh thu SaaS.
 *
 * MRR/ARR, churn, doanh thu overage, hóa đơn quá hạn, top tenant, dự báo gia hạn.
 * Chỉ SuperAdmin/Billing admin. Mọi số liệu tính từ DB (không hardcode).
 */
class SaasRevenueDashboard extends Page
{
    use PlatformScreen;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar-square';

    protected static string|\UnitEnum|null $navigationGroup = 'SaaS Billing';

    protected static ?string $navigationLabel = 'Tổng quan doanh thu';

    protected static ?int $navigationSort = 1;

    protected static ?string $title = 'Tổng quan doanh thu SaaS';

    protected static ?string $slug = 'billing/revenue';

    protected string $view = 'filament.pages.saas-revenue-dashboard';

    private function money(float $v): string
    {
        return number_format($v / 1_000_000, 1).'tr';
    }

    protected function getViewData(): array
    {
        $activeStatuses = ['active', 'trial', 'pending_renewal'];
        $mrr = (float) TenantSubscription::whereIn('status', $activeStatuses)->sum('mrr');
        $overageRev = (float) UsageRecord::sum('overage_amount');
        $overdue = BillingInvoice::where('status', 'overdue')
            ->orWhere(fn ($q) => $q->whereIn('status', ['issued', 'sent', 'partially_paid'])->whereDate('due_date', '<', now()));
        $overdueAmount = (float) (clone $overdue)->sum('remaining_amount');

        // MRR theo plan.
        $byPlan = TenantSubscription::whereIn('status', $activeStatuses)
            ->selectRaw('plan_id, sum(mrr) m')->groupBy('plan_id')->pluck('m', 'plan_id');
        $planNames = Plan::pluck('name', 'id');
        $mrrByPlan = collect($byPlan)->map(fn ($m, $pid) => ['label' => $planNames[$pid] ?? '—', 'value' => (float) $m])
            ->sortByDesc('value')->values();

        // Phân bố trạng thái thuê bao.
        $byStatus = TenantSubscription::selectRaw('status, count(*) c')->groupBy('status')->pluck('c', 'status');

        return [
            'kpis' => [
                ['label' => 'MRR', 'value' => $this->money($mrr).'đ', 'accent' => 'green'],
                ['label' => 'ARR', 'value' => $this->money($mrr * 12).'đ', 'accent' => 'green'],
                ['label' => 'Thuê bao active', 'value' => TenantSubscription::where('status', 'active')->count(), 'accent' => 'blue'],
                ['label' => 'Trial', 'value' => TenantSubscription::where('status', 'trial')->count(), 'accent' => 'amber'],
                ['label' => 'Churn (suspended/cancelled)', 'value' => TenantSubscription::whereIn('status', ['suspended', 'cancelled'])->count(), 'accent' => 'red'],
                ['label' => 'DT overage', 'value' => $this->money($overageRev).'đ', 'accent' => 'blue'],
                ['label' => 'Hóa đơn quá hạn', 'value' => (clone $overdue)->count(), 'sub' => $this->money($overdueAmount).'đ', 'accent' => 'red'],
                ['label' => 'Chờ gia hạn', 'value' => TenantSubscription::where('status', 'pending_renewal')->count(), 'accent' => 'amber'],
            ],
            'mrrByPlan' => $mrrByPlan,
            'mrrByPlanMax' => max(1, (float) $mrrByPlan->max('value')),
            'byStatus' => $byStatus,
            'topTenants' => TenantSubscription::with(['tenant', 'plan'])->whereIn('status', $activeStatuses)
                ->orderByDesc('mrr')->limit(6)->get(),
            'renewalForecast' => TenantSubscription::with('tenant')->whereIn('status', ['active', 'pending_renewal'])
                ->whereNotNull('end_date')->orderBy('end_date')->limit(6)->get(),
            'overdueInvoices' => (clone $overdue)->with('tenant')->latest('due_date')->limit(6)->get(),
            'openAlerts' => QuotaAlert::where('status', 'open')->count(),
        ];
    }
}
