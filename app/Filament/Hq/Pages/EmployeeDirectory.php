<?php

namespace App\Filament\Hq\Pages;

use App\Filament\Concerns\HqScreen;
use App\Models\BqlTeam;
use App\Models\Department;
use App\Models\EmployeeProjectAssignment;
use App\Models\StaffProfile;
use App\Support\Context\CurrentContext;
use BackedEnum;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;

/**
 * HQ-01-05 — Danh sách nhân sự công ty.
 *
 * KPI (Tổng/Đang làm việc/Đa dự án/Chờ phân công) + tab phòng ban + bảng nhân sự +
 * donut phòng ban + dự án thiếu nhân sự + hoạt động nhân sự. Số liệu từ DB (tenant HQ).
 */
class EmployeeDirectory extends Page
{
    use HqScreen;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-users';

    protected static string|\UnitEnum|null $navigationGroup = 'Nhân sự & BQL';

    protected static ?string $navigationLabel = 'Quản lý nhân sự';

    protected static ?int $navigationSort = 1;

    protected static ?string $title = 'Danh sách nhân sự công ty';

    protected static ?string $slug = 'employees';

    protected string $view = 'filament.hq.pages.employee-directory';

    public string $dept = 'all';

    public string $search = '';

    private const DEPT_LABEL = ['Ban giám đốc' => 'Quản lý'];

    private const DEPT_COLOR = [
        'Ban giám đốc' => '#7c3aed', 'Kỹ thuật' => '#2563eb', 'Kế toán' => '#f59e0b',
        'CSKH' => '#14b8a6', 'Bảo vệ' => '#64748b',
    ];

    protected function tenantId(): ?int
    {
        return app(CurrentContext::class)->tenantId();
    }

    protected function getViewData(): array
    {
        $tid = $this->tenantId();
        $staff = StaffProfile::query()->with(['user', 'department'])
            ->where('staff_profiles.tenant_id', $tid);

        $all = (clone $staff)->get();

        // Assignment count per employee (distinct projects).
        $projCount = EmployeeProjectAssignment::where('tenant_id', $tid)
            ->select('employee_id', DB::raw('count(distinct project_id) c'))
            ->groupBy('employee_id')->pluck('c', 'employee_id');

        $total = $all->count();
        $working = $all->where('status', 'active')->count();
        $pending = $all->where('status', 'pending')->count();
        $multi = $projCount->filter(fn ($c) => $c > 1)->count();

        // Department donut + tabs.
        $depts = Department::where('tenant_id', $tid)->get();
        $countByDept = $all->groupBy('department_id')->map->count();
        $donut = $depts->map(fn ($d) => [
            'id' => $d->id, 'name' => $d->name,
            'label' => self::DEPT_LABEL[$d->name] ?? $d->name,
            'value' => $countByDept[$d->id] ?? 0,
            'color' => self::DEPT_COLOR[$d->name] ?? '#94a3b8',
            'pct' => $total ? round(($countByDept[$d->id] ?? 0) / $total * 100) : 0,
        ])->sortByDesc('value')->values();

        // Rows (filter by dept + search).
        $rows = $all->filter(function ($s) {
            if ($this->dept !== 'all' && (string) $s->department_id !== $this->dept) {
                return false;
            }
            if ($this->search !== '') {
                $q = mb_strtolower($this->search);

                return str_contains(mb_strtolower((string) $s->user?->name), $q)
                    || str_contains(mb_strtolower($s->employee_code), $q);
            }

            return true;
        })->sortBy('employee_code')->take(60)->map(fn ($s) => [
            'code' => $s->employee_code,
            'name' => $s->user?->name ?? '—',
            'position' => $s->position,
            'dept' => $s->department?->name ?? '—',
            'dept_label' => self::DEPT_LABEL[$s->department?->name] ?? $s->department?->name,
            'projects' => (int) ($projCount[$s->id] ?? 0),
            'status' => $s->status,
            'hired' => optional($s->hire_date)->format('d/m/Y') ?? '—',
        ])->values();

        // Understaffed projects (right panel).
        $understaffed = BqlTeam::where('tenant_id', $tid)->with('project')->get()
            ->filter(fn ($b) => data_get($b->metadata, 'understaffed'))
            ->map(fn ($b) => [
                'name' => $b->project?->name ?? $b->name,
                'missing' => (int) data_get($b->metadata, 'required_headcount', 6) - (int) data_get($b->metadata, 'current_headcount', 0),
            ])->values();

        return [
            'kpi' => compact('total', 'working', 'multi', 'pending')
                + ['workingPct' => $total ? round($working / $total * 100, 1) : 0,
                    'multiPct' => $total ? round($multi / $total * 100, 1) : 0,
                    'pendingPct' => $total ? round($pending / $total * 100, 1) : 0],
            'tabs' => collect([['id' => 'all', 'label' => 'Tất cả', 'count' => $total]])
                ->merge($donut->map(fn ($d) => ['id' => (string) $d['id'], 'label' => $d['label'], 'count' => $d['value']]))
                ->all(),
            'donut' => $donut->all(),
            'rows' => $rows,
            'understaffed' => $understaffed,
        ];
    }
}
