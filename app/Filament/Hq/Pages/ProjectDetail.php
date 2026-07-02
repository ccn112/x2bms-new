<?php

namespace App\Filament\Hq\Pages;

use App\Filament\Concerns\HqScreen;
use App\Models\BqlTeam;
use App\Models\EmployeeProjectAssignment;
use App\Models\Plan;
use App\Models\Project;
use App\Models\ProjectModuleOverride;
use App\Models\ProjectSubscriptionPeriod;
use App\Support\Context\CurrentContext;
use BackedEnum;
use Filament\Pages\Page;

/**
 * HQ-01-03 — Chi tiết dự án (tổng quan vận hành).
 *
 * Record page: header badges, lifecycle path, thông tin dự án, BQL & liên hệ, KPI nhanh,
 * gói dịch vụ, trạng thái module, tab nhân sự dự án. Hidden khỏi nav (record sub-page).
 */
class ProjectDetail extends Page
{
    use HqScreen;

    public static function shouldRegisterNavigation(): bool
    {
        return false; // record sub-page, reached from the directory
    }

    protected static ?string $slug = 'projects/{project}';

    protected string $view = 'filament.hq.pages.project-detail';

    public Project $project;

    public function mount(Project $project): void
    {
        $user = auth()->user();
        // Tenant operator restricted to own tenant; platform admin may view any project.
        if (! $user->isPlatformAdmin()) {
            abort_unless((int) $project->tenant_id === (int) $user->tenant_id, 404);
        }

        $this->project = $project;
    }

    public function getTitle(): string
    {
        return $this->project->code.' — '.$this->project->name;
    }

    protected function getViewData(): array
    {
        $p = $this->project;
        $period = ProjectSubscriptionPeriod::where('project_id', $p->id)->latest('id')->first();
        $planName = $period ? Plan::find($period->plan_id)?->name : null;
        $team = BqlTeam::where('project_id', $p->id)->with('manager.user')->first();

        $assignments = EmployeeProjectAssignment::where('project_id', $p->id)
            ->with(['employee.user', 'department'])->where('status', 'active')->get()
            ->map(fn ($a) => [
                'name' => $a->employee?->user?->name ?? '—',
                'position' => $a->employee?->position ?? '—',
                'dept' => $a->department?->name ?? '—',
                'type' => $a->assignment_type,
            ]);

        $modules = ProjectModuleOverride::where('project_id', $p->id)->get()
            ->map(fn ($m) => ['key' => $m->module_key, 'status' => $m->status]);

        return [
            'p' => $p,
            'planName' => $planName,
            'period' => $period,
            'team' => $team,
            'manager' => $team?->manager,
            'assignments' => $assignments,
            'modules' => $modules,
            'statusLabel' => match ($p->status) {
                'active' => 'Đang hoạt động', 'trial' => 'Trial', 'suspended' => 'Tạm ngừng', default => $p->status,
            },
        ];
    }
}
