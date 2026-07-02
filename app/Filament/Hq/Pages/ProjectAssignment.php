<?php

namespace App\Filament\Hq\Pages;

use App\Filament\Concerns\HqScreen;
use App\Models\AuditLog;
use App\Models\EmployeeProjectAssignment;
use App\Models\Project;
use App\Models\StaffProfile;
use App\Support\Context\CurrentContext;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Carbon;

/**
 * HQ-01-06 — Phân công nhân sự vào dự án. Chọn dự án → nhân sự khả dụng + cấu hình phân công
 * (loại, workload) + bảng phân công hiện tại. Ghi thật vào employee_project_assignments + audit.
 */
class ProjectAssignment extends Page
{
    use HqScreen;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-user-plus';

    protected static string|\UnitEnum|null $navigationGroup = 'Nhân sự & BQL';

    protected static ?string $navigationLabel = 'Phân công nhân sự';

    protected static ?int $navigationSort = 2;

    protected static ?string $title = 'Phân công nhân sự vào dự án';

    protected static ?string $slug = 'project-assignments';

    protected string $view = 'filament.hq.pages.project-assignment';

    public ?int $projectId = null;

    public ?int $employeeId = null;

    public string $assignmentType = 'primary';

    public int $workload = 100;

    protected function tid(): ?int
    {
        return app(CurrentContext::class)->tenantId();
    }

    public function mount(): void
    {
        $this->projectId = Project::where('tenant_id', $this->tid())->where('status', 'active')->orderBy('code')->value('id');
    }

    public function assign(): void
    {
        $this->validate([
            'projectId' => 'required|integer',
            'employeeId' => 'required|integer',
            'assignmentType' => 'required|in:primary,secondary,temporary',
            'workload' => 'integer|min:1|max:100',
        ]);

        $emp = StaffProfile::where('tenant_id', $this->tid())->findOrFail($this->employeeId);

        EmployeeProjectAssignment::create([
            'tenant_id' => $this->tid(),
            'project_id' => $this->projectId,
            'employee_id' => $emp->id,
            'department_id' => $emp->department_id,
            'assignment_type' => $this->assignmentType,
            'workload_percent' => $this->workload,
            'priority' => 'normal',
            'effective_from' => Carbon::parse('2026-07-02'),
            'status' => 'active',
            'assigned_by' => auth()->id(),
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        if ($emp->status !== 'active') {
            $emp->update(['status' => 'active']);
        }

        AuditLog::create([
            'tenant_id' => $this->tid(),
            'user_id' => auth()->id(),
            'actor_name' => auth()->user()?->name,
            'action' => 'hq.assignment.create',
            'description' => 'Phân công '.$emp->user?->name.' vào dự án #'.$this->projectId,
        ]);

        $this->employeeId = null;
        Notification::make()->title('Đã phân công nhân sự')->success()->send();
    }

    protected function getViewData(): array
    {
        $tid = $this->tid();
        $projects = Project::where('tenant_id', $tid)->orderBy('code')->get(['id', 'code', 'name', 'status']);

        $assignedIds = EmployeeProjectAssignment::where('tenant_id', $tid)
            ->where('project_id', $this->projectId)->where('status', 'active')->pluck('employee_id')->all();

        $available = StaffProfile::where('tenant_id', $tid)->with(['user', 'department'])
            ->whereNotIn('id', $assignedIds)->orderBy('employee_code')->limit(40)->get()
            ->map(fn ($s) => ['id' => $s->id, 'code' => $s->employee_code, 'name' => $s->user?->name ?? '—',
                'position' => $s->position, 'dept' => $s->department?->name ?? '—', 'status' => $s->status]);

        $current = EmployeeProjectAssignment::where('tenant_id', $tid)
            ->where('project_id', $this->projectId)->where('status', 'active')
            ->with(['employee.user', 'department'])->get()
            ->map(fn ($a) => ['name' => $a->employee?->user?->name ?? '—', 'position' => $a->employee?->position ?? '—',
                'dept' => $a->department?->name ?? '—', 'type' => $a->assignment_type, 'workload' => $a->workload_percent]);

        return [
            'projects' => $projects,
            'selected' => $projects->firstWhere('id', $this->projectId),
            'available' => $available,
            'current' => $current,
        ];
    }
}
