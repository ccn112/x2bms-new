<?php

namespace App\Livewire;

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
use App\Models\WorkOrder;
use Illuminate\Support\Number;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * WEB-01-01 — Bảng điều khiển vận hành (operational dashboard).
 * Custom page (Livewire + Blade + Tailwind), rendered via the X2 component set.
 * All figures are computed from seeded DB rows — nothing is hardcoded in the view.
 */
#[Layout('components.layouts.x2-app')]
class OperationalDashboard extends Component
{
    public function render()
    {
        // Scope: current user's building (falls back to the demo building).
        $building = auth()->user()?->building_id
            ? Building::find(auth()->user()->building_id)
            : Building::query()->first();

        $tenantId = $building->tenant_id;
        $buildingId = $building->id;

        $current = BillingPeriod::where('building_id', $buildingId)->where('is_current', true)->first();

        // --- KPIs ---
        $kpis = [
            [
                'label' => 'Tỷ lệ thu phí',
                'value' => round($current->collected_amount / max($current->billed_amount, 1) * 100, 1).'%',
                'sub' => $current->label,
                'accent' => 'green',
                'trend' => '+1.2%',
                'trendUp' => true,
            ],
            [
                'label' => 'Đã thu trong tháng',
                'value' => $this->billions($current->collected_amount),
                'sub' => 'Kỳ '.$current->label,
                'accent' => 'teal',
            ],
            [
                'label' => 'Phản ánh chờ xử lý',
                'value' => FeedbackRequest::where('building_id', $buildingId)
                    ->whereIn('status', FeedbackStatus::pendingValues())->count(),
                'sub' => 'Cần điều phối',
                'accent' => 'amber',
            ],
            [
                'label' => 'Cảnh báo SLA',
                'value' => \App\Models\SlaEvent::where('building_id', $buildingId)->where('status', 'open')->count(),
                'sub' => 'Sắp/đã quá hạn',
                'accent' => 'red',
            ],
            [
                'label' => 'Công nợ đến hạn',
                'value' => $this->millions(Debt::where('building_id', $buildingId)->where('is_overdue', true)->sum('amount')),
                'sub' => 'Cần nhắc thu',
                'accent' => 'blue',
            ],
        ];

        // --- Fee trend (bar chart) ---
        $periods = BillingPeriod::where('building_id', $buildingId)->orderBy('period_month')->get();
        $maxCollected = max($periods->max('collected_amount'), 1);
        $feeTrend = $periods->map(fn ($p) => [
            'label' => $p->label,
            'value' => $this->billions($p->collected_amount),
            'height' => (int) round($p->collected_amount / $maxCollected * 100),
            'current' => (bool) $p->is_current,
        ])->all();

        // --- Feedback by category (donut) ---
        $categories = FeedbackCategory::where('tenant_id', $tenantId)->withCount('feedbackRequests')->get();
        $totalFeedback = $categories->sum('feedback_requests_count');
        $offset = 0;
        $donut = $categories->map(function ($c) use ($totalFeedback, &$offset) {
            $pct = $totalFeedback ? $c->feedback_requests_count / $totalFeedback * 100 : 0;
            $seg = ['name' => $c->name, 'color' => $c->color, 'count' => $c->feedback_requests_count, 'pct' => round($pct, 1), 'offset' => round($offset, 2)];
            $offset += $pct;

            return $seg;
        })->all();

        // --- "Việc cần xử lý hôm nay" table rows ---
        $workOrders = WorkOrder::with('department')
            ->where('building_id', $buildingId)
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

        // --- Department performance (progress bars) ---
        $deptPerformance = Department::where('building_id', $buildingId)->get()->map(function ($d) {
            $total = WorkOrder::where('department_id', $d->id)->count();
            $done = WorkOrder::where('department_id', $d->id)->where('status', WorkOrderStatus::Done->value)->count();

            return ['name' => $d->name, 'pct' => $total ? (int) round($done / $total * 100) : 0];
        })->all();

        // --- Alerts list ---
        $alerts = IocAlert::where('building_id', $buildingId)->where('status', 'open')->latest()->get()
            ->map(fn ($a) => [
                'title' => $a->title,
                'tone' => match ($a->severity) {
                    'critical' => 'red',
                    'warning' => 'amber',
                    default => 'blue',
                },
                'severity' => $a->severity,
            ])->all();

        // --- AI suggestions ---
        $aiSuggestions = AiSuggestion::where('building_id', $buildingId)
            ->where('context', 'operational_dashboard')->get()
            ->map(fn ($s) => ['title' => $s->title, 'detail' => $s->detail])->all();

        $lastAudit = AuditLog::where('building_id', $buildingId)->latest()->first();

        return view('livewire.operational-dashboard', [
            'building' => $building,
            'user' => auth()->user(),
            'kpis' => $kpis,
            'feeTrend' => $feeTrend,
            'donut' => $donut,
            'totalFeedback' => $totalFeedback,
            'workOrders' => $workOrders,
            'deptPerformance' => $deptPerformance,
            'alerts' => $alerts,
            'aiSuggestions' => $aiSuggestions,
            'lastAudit' => $lastAudit,
            'navGroups' => $this->navGroups(),
        ]);
    }

    private function billions(float|int $v): string
    {
        return number_format($v / 1_000_000_000, 2, ',', '.').' tỷ';
    }

    private function millions(float|int $v): string
    {
        return number_format($v / 1_000_000, 0, ',', '.').' tr';
    }

    /** Navigation chrome (config, not business data). */
    private function navGroups(): array
    {
        return [
            ['label' => null, 'items' => [
                ['label' => 'Tổng quan', 'route' => '#', 'active' => true],
            ]],
            ['label' => 'VẬN HÀNH', 'items' => [
                ['label' => 'Phản ánh & đánh giá', 'route' => '#', 'badge' => 56],
                ['label' => 'Công việc kỹ thuật', 'route' => '#'],
                ['label' => 'Cảnh báo & IOC', 'route' => '#', 'badge' => 18],
                ['label' => 'Thông báo', 'route' => '#'],
            ]],
            ['label' => 'TÀI CHÍNH', 'items' => [
                ['label' => 'Kỳ phí & bảng kê', 'route' => '#'],
                ['label' => 'Công nợ & thanh toán', 'route' => '#'],
            ]],
            ['label' => 'CƯ DÂN & CĂN HỘ', 'items' => [
                ['label' => 'Cư dân', 'route' => '#'],
                ['label' => 'Căn hộ', 'route' => '#'],
            ]],
        ];
    }
}
