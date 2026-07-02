<?php

namespace App\Filament\Hq\Pages;

use App\Filament\Concerns\HqScreen;
use App\Models\BqlTeam;
use App\Models\Department;
use App\Models\EmployeeProjectAssignment;
use App\Models\Project;
use Filament\Pages\Page;

/**
 * HQ-01-04 — Thiết lập BQL dự án. Tóm tắt dự án + thẻ nhân sự theo phòng ban
 * (hiện có / yêu cầu) + kênh liên hệ + cảnh báo thiếu nhân sự + bảng nhân sự BQL.
 */
class BqlSetup extends Page
{
    use HqScreen;

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    protected static ?string $slug = 'projects/{project}/bql';

    protected string $view = 'filament.hq.pages.bql-setup';

    public Project $project;

    public function mount(Project $project): void
    {
        $user = auth()->user();
        if (! $user->isPlatformAdmin()) {
            abort_unless((int) $project->tenant_id === (int) $user->tenant_id, 404);
        }
        $this->project = $project;
    }

    public function getTitle(): string
    {
        return 'Thiết lập BQL — '.$this->project->name;
    }

    protected function getViewData(): array
    {
        $team = BqlTeam::where('project_id', $this->project->id)->with('manager.user')->first();
        $assignments = EmployeeProjectAssignment::where('project_id', $this->project->id)
            ->where('status', 'active')->with(['employee.user', 'department'])->get();

        // Headcount by department (current vs required demo target).
        $required = ['Ban giám đốc' => 1, 'Kỹ thuật' => 3, 'Kế toán' => 1, 'CSKH' => 2, 'Bảo vệ' => 4];
        $currentByDept = $assignments->groupBy(fn ($a) => $a->department?->name)->map->count();
        $depts = Department::where('tenant_id', $this->project->tenant_id)->get()
            ->map(fn ($d) => [
                'name' => $d->name,
                'current' => (int) ($currentByDept[$d->name] ?? 0),
                'required' => $required[$d->name] ?? 1,
            ]);

        return [
            'project' => $this->project,
            'team' => $team,
            'manager' => $team?->manager,
            'understaffed' => (bool) data_get($team?->metadata, 'understaffed'),
            'depts' => $depts,
            'assignments' => $assignments->map(fn ($a) => [
                'name' => $a->employee?->user?->name ?? '—',
                'position' => $a->employee?->position ?? '—',
                'dept' => $a->department?->name ?? '—',
                'phone' => $a->employee?->phone ?? '—',
                'type' => $a->assignment_type,
            ]),
        ];
    }
}
