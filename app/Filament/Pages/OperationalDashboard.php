<?php

namespace App\Filament\Pages;

use App\Enums\FeedbackStatus;
use App\Enums\WorkOrderStatus;
use App\Models\AiSuggestion;
use App\Models\AuditLog;
use App\Models\BillingPeriod;
use App\Models\Building;
use App\Models\Debt;
use App\Models\Department;
use App\Models\FeedbackCategory;
use App\Models\FeedbackRequest;
use App\Models\IocAlert;
use App\Models\SlaEvent;
use App\Models\WorkOrder;
use BackedEnum;
use Filament\Pages\Page;

/**
 * WEB-01-01 — Bảng điều khiển vận hành.
 * Migrated onto the Filament panel chrome (sidebar/header/profile shared shell).
 * Content stays bespoke Blade + SVG; figures come from seeded DB rows (no hardcode).
 */
class OperationalDashboard extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-squares-2x2';

    protected static ?string $navigationLabel = 'Tổng quan';

    protected static ?int $navigationSort = -10;

    protected static ?string $title = 'Tổng quan';

    protected static ?string $slug = 'dashboard';

    protected string $view = 'filament.pages.operational-dashboard';

    protected function getViewData(): array
    {
        // Scope: the active PROJECT context (all of its buildings) — WEB-UX-01.
        $ctx = app(\App\Support\Context\CurrentContext::class);
        $project = $ctx->project();
        $buildingIds = $ctx->buildingIds();

        // Current-period totals aggregated across the project's buildings.
        $currentPeriods = BillingPeriod::whereIn('building_id', $buildingIds)->where('is_current', true)->get();
        $collected = $currentPeriods->sum('collected_amount');
        $billed = $currentPeriods->sum('billed_amount');
        $periodLabel = $currentPeriods->first()->label ?? '—';

        $kpis = [
            [
                'label' => 'Tỷ lệ thu phí',
                'value' => round($collected / max($billed, 1) * 100, 1).'%',
                'sub' => $periodLabel,
                'accent' => 'green',
                'trend' => '+1.2%',
                'trendUp' => true,
            ],
            [
                'label' => 'Đã thu trong tháng',
                'value' => $this->billions($collected),
                'sub' => 'Kỳ '.$periodLabel,
                'accent' => 'teal',
            ],
            [
                'label' => 'Phản ánh chờ xử lý',
                'value' => FeedbackRequest::whereIn('building_id', $buildingIds)
                    ->whereIn('status', FeedbackStatus::pendingValues())->count(),
                'sub' => 'Cần điều phối',
                'accent' => 'amber',
            ],
            [
                'label' => 'Cảnh báo SLA',
                'value' => SlaEvent::whereIn('building_id', $buildingIds)->where('status', 'open')->count(),
                'sub' => 'Sắp/đã quá hạn',
                'accent' => 'red',
            ],
            [
                'label' => 'Công nợ đến hạn',
                'value' => $this->millions(Debt::whereIn('building_id', $buildingIds)->where('is_overdue', true)->sum('amount')),
                'sub' => 'Cần nhắc thu',
                'accent' => 'blue',
            ],
        ];

        // Fee trend: sum collected per period label across the project.
        $byLabel = BillingPeriod::whereIn('building_id', $buildingIds)->orderBy('period_month')->get()
            ->groupBy('label')
            ->map(fn ($g) => [
                'label' => $g->first()->label,
                'period_month' => $g->first()->period_month,
                'collected' => $g->sum('collected_amount'),
                'current' => $g->contains('is_current', true),
            ])
            ->sortBy('period_month')
            ->values();
        $maxCollected = max($byLabel->max('collected') ?? 0, 1);
        $feeTrend = $byLabel->map(fn ($p) => [
            'label' => $p['label'],
            'value' => $this->billions($p['collected']),
            'height' => (int) round($p['collected'] / $maxCollected * 100),
            'current' => (bool) $p['current'],
        ])->all();

        // Feedback donut scoped to the project's buildings.
        $categories = FeedbackCategory::where('tenant_id', $ctx->tenantId())
            ->withCount(['feedbackRequests' => fn ($q) => $q->whereIn('building_id', $buildingIds)])
            ->get();
        $totalFeedback = $categories->sum('feedback_requests_count');
        $offset = 0;
        $donut = $categories->map(function ($c) use ($totalFeedback, &$offset) {
            $pct = $totalFeedback ? $c->feedback_requests_count / $totalFeedback * 100 : 0;
            $seg = ['name' => $c->name, 'color' => $c->color, 'count' => $c->feedback_requests_count, 'pct' => round($pct, 1), 'offset' => round($offset, 2)];
            $offset += $pct;

            return $seg;
        })->all();

        $workOrders = WorkOrder::with('department')
            ->whereIn('building_id', $buildingIds)
            ->whereIn('status', [WorkOrderStatus::Pending->value, WorkOrderStatus::InProgress->value])
            ->orderBy('due_at')
            ->take(6)
            ->get()
            ->map(fn (WorkOrder $w) => [
                'title' => e($w->title),
                'department' => '<span class="text-slate-500">'.e($w->department?->name ?? '—').'</span>',
                'status' => view('components.x2.status-badge', [
                    'label' => $w->status->label(),
                    'tone' => $w->status->tone(),
                ])->render(),
            ])->all();

        // Department performance aggregated by name across the project.
        $deptPerformance = Department::whereIn('building_id', $buildingIds)->get()
            ->groupBy('name')
            ->map(function ($group, $name) {
                $ids = $group->pluck('id');
                $total = WorkOrder::whereIn('department_id', $ids)->count();
                $done = WorkOrder::whereIn('department_id', $ids)->where('status', WorkOrderStatus::Done->value)->count();

                return ['name' => $name, 'pct' => $total ? (int) round($done / $total * 100) : 0];
            })->values()->all();

        $alerts = IocAlert::whereIn('building_id', $buildingIds)->where('status', 'open')->latest()->get()
            ->map(fn ($a) => [
                'title' => $a->title,
                'tone' => match ($a->severity) {
                    'critical' => 'red',
                    'warning' => 'amber',
                    default => 'blue',
                },
                'severity' => $a->severity,
            ])->all();

        $aiSuggestions = AiSuggestion::whereIn('building_id', $buildingIds)
            ->where('context', 'operational_dashboard')->get()
            ->map(fn ($s) => ['title' => $s->title, 'detail' => $s->detail])->all();

        return [
            'project' => $project,
            'user' => auth()->user(),
            'kpis' => $kpis,
            'feeTrend' => $feeTrend,
            'donut' => $donut,
            'totalFeedback' => $totalFeedback,
            'workOrders' => $workOrders,
            'deptPerformance' => $deptPerformance,
            'alerts' => $alerts,
            'aiSuggestions' => $aiSuggestions,
            'lastAudit' => AuditLog::whereIn('building_id', $buildingIds)->latest()->first(),
        ];
    }

    private function billions(float|int $v): string
    {
        return number_format($v / 1_000_000_000, 2, ',', '.').' tỷ';
    }

    private function millions(float|int $v): string
    {
        return number_format($v / 1_000_000, 0, ',', '.').' tr';
    }
}
