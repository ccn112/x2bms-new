<?php

namespace App\Filament\Hq\Pages;

use App\Filament\Concerns\HqScreen;
use App\Models\Plan;
use App\Models\Project;
use App\Models\ProjectSubscriptionPeriod;
use App\Support\Context\CurrentContext;
use BackedEnum;
use Filament\Pages\Page;
use Illuminate\Support\Carbon;

/**
 * HQ-01-01 — Danh sách dự án quản lý.
 *
 * KPI (Tổng/Đang hoạt động/Gói sắp gia hạn/BQL thiếu nhân sự) + bảng dự án + donut
 * phân bổ gói + tổng quan nhanh + hoạt động gần đây. Mọi số liệu từ DB theo tenant +
 * phạm vi đa dự án của CurrentContext.
 */
class ProjectDirectory extends Page
{
    use HqScreen;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-building-office-2';

    protected static string|\UnitEnum|null $navigationGroup = 'Quản lý dự án';

    protected static ?string $navigationLabel = 'Danh sách dự án';

    protected static ?int $navigationSort = 1;

    protected static ?string $title = 'Danh mục dự án quản lý';

    protected static ?string $slug = 'projects';

    protected string $view = 'filament.hq.pages.project-directory';

    public string $tab = 'all';

    public string $search = '';

    /** @var array<string,string> package badge colours */
    private const PLAN_BADGE = [
        'Đầy đủ' => 'violet', 'Phổ biến' => 'blue', 'Thông minh' => 'amber',
    ];

    protected function baseQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $ctx = app(CurrentContext::class);

        return Project::query()
            ->when($ctx->tenantId(), fn ($q) => $q->where('tenant_id', $ctx->tenantId()))
            ->whereIn('id', $ctx->hqProjectIds() ?: [0]);
    }

    protected function getViewData(): array
    {
        $now = Carbon::parse('2026-07-02');
        $planNames = Plan::pluck('name', 'id');

        $all = $this->baseQuery()->orderBy('code')->get();

        // Subscription period per project (latest).
        $periods = ProjectSubscriptionPeriod::whereIn('project_id', $all->pluck('id'))
            ->get()->keyBy('project_id');

        // KPI cards.
        $total = $all->count();
        $active = $all->where('status', 'active')->count();
        $renewSoon = $periods->filter(fn ($p) => $p->current_period_end
            && $p->current_period_end->between($now, $now->copy()->addDays(30)))->count();
        $understaffed = \App\Models\BqlTeam::whereIn('project_id', $all->pluck('id'))
            ->get()->filter(fn ($b) => data_get($b->metadata, 'understaffed'))->count();

        // Donut — package distribution (active by plan; trial/suspended as their status).
        $dist = [];
        foreach ($all as $p) {
            $per = $periods->get($p->id);
            $bucket = match ($p->status) {
                'trial' => 'Trial',
                'suspended' => 'Tạm ngừng',
                default => $planNames[$per?->plan_id] ?? '—',
            };
            $dist[$bucket] = ($dist[$bucket] ?? 0) + 1;
        }
        $donutColors = ['Đầy đủ' => '#7c3aed', 'Phổ biến' => '#2563eb', 'Thông minh' => '#f59e0b', 'Trial' => '#14b8a6', 'Tạm ngừng' => '#94a3b8'];
        $donut = collect($donutColors)->map(fn ($color, $label) => [
            'label' => $label, 'value' => $dist[$label] ?? 0, 'color' => $color,
            'pct' => $total ? round(($dist[$label] ?? 0) / $total * 100) : 0,
        ])->filter(fn ($s) => $s['value'] > 0)->values()->all();

        // Table rows (apply tab + search).
        $rows = $all->filter(function ($p) use ($periods) {
            if ($this->tab !== 'all' && $p->status !== $this->tab) {
                return false;
            }
            if ($this->search !== '') {
                $s = mb_strtolower($this->search);

                return str_contains(mb_strtolower($p->name), $s) || str_contains(mb_strtolower($p->code), $s);
            }

            return true;
        })->map(function ($p) use ($periods, $planNames, $now) {
            $per = $periods->get($p->id);
            $planName = $planNames[$per?->plan_id] ?? '—';
            $team = \App\Models\BqlTeam::where('project_id', $p->id)->with('manager.user')->first();
            $renewSoon = $per?->current_period_end && $per->current_period_end->between($now, $now->copy()->addDays(30));

            return [
                'id' => $p->id, 'code' => $p->code, 'name' => $p->name, 'type' => $p->type,
                'manager' => $team?->manager?->user?->name ?? '—',
                'plan' => $planName, 'plan_badge' => self::PLAN_BADGE[$planName] ?? 'gray',
                'started' => $per?->started_at?->format('d/m/Y') ?? '—',
                'renew' => $per?->current_period_end?->format('d/m/Y') ?? '—',
                'status' => $p->status, 'renew_soon' => $renewSoon,
            ];
        })->values();

        return [
            'kpi' => ['total' => $total, 'active' => $active, 'renewSoon' => $renewSoon, 'understaffed' => $understaffed,
                'activePct' => $total ? round($active / $total * 100) : 0],
            'tabs' => [
                'all' => $all->count(), 'active' => $all->where('status', 'active')->count(),
                'trial' => $all->where('status', 'trial')->count(), 'suspended' => $all->where('status', 'suspended')->count(),
            ],
            'donut' => $donut,
            'rows' => $rows,
            'quick' => [
                'bql' => \App\Models\BqlTeam::whereIn('project_id', $all->pluck('id'))->count(),
                'buildings' => (int) $all->sum('building_count'),
                'residents' => (int) $all->sum('apartment_count'),
                'area' => (int) $all->sum('land_area_sqm'),
            ],
        ];
    }
}
