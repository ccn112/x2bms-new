<?php

namespace App\Support\Context;

use App\Models\Building;
use App\Models\Project;
use App\Models\Tenant;
use Illuminate\Support\Collection;

/**
 * WEB-UX-01 — Current working context.
 *
 * The context scope is the PROJECT (a management board manages one project that
 * contains several buildings). Buildings are NOT the context; they are used as a
 * filter inside data tables. Selection is session-backed and validated against
 * the projects the user may access.
 */
class CurrentContext
{
    public function projectId(): ?int
    {
        $id = session('current_project_id');

        if ($id && $this->availableProjects()->contains('id', (int) $id)) {
            return (int) $id;
        }

        // Default: the project of the user's home building, else the first project.
        $homeBuildingId = auth()->user()?->building_id;
        $projectId = $homeBuildingId ? Building::find($homeBuildingId)?->project_id : null;

        return $projectId ?? $this->availableProjects()->value('id');
    }

    public function project(): ?Project
    {
        $id = $this->projectId();

        return $id ? Project::find($id) : null;
    }

    public function tenantId(): ?int
    {
        return auth()->user()?->tenant_id ?? $this->project()?->tenant_id;
    }

    public function tenant(): ?Tenant
    {
        $id = $this->tenantId();

        return $id ? Tenant::find($id) : null;
    }

    /**
     * Projects the current user may switch between — the work "workspaces".
     *
     * 3-tier scope: platform admin → all projects; tenant operator (công ty vận
     * hành) → every project in their tenant; project-level BQL staff → only the
     * projects granted to them (∪ home project).
     */
    public function availableProjects(): Collection
    {
        $user = auth()->user();

        if (! $user) {
            return new Collection();
        }

        if ($user->isPlatformAdmin()) {
            return Project::query()->orderBy('name')->get();
        }

        $query = Project::query()
            ->when($user->tenant_id, fn ($q) => $q->where('tenant_id', $user->tenant_id));

        $projectIds = $user->accessibleProjectIds();
        if (! $user->isTenantOperator() && ! empty($projectIds)) {
            $query->whereIn('id', $projectIds);
        }

        return $query->orderBy('name')->get();
    }

    /** Buildings inside the current project — used as a filter in data tables. */
    public function buildings(): Collection
    {
        $projectId = $this->projectId();

        return Building::query()
            ->when($projectId, fn ($q) => $q->where('project_id', $projectId))
            ->orderBy('name')
            ->get();
    }

    /** @return array<int> building ids inside the current project */
    public function buildingIds(): array
    {
        return $this->buildings()->pluck('id')->all();
    }

    public function setProject(int $projectId): void
    {
        session(['current_project_id' => $projectId]);
    }

    /* =========================================================================
     * WEB-UX-03 — Workspace switcher. The 3-tier scope as selectable workspaces:
     * BQL Dự án (project) → Cổng Công ty/HQ (tenant) → SuperAdmin (platform).
     * ========================================================================= */

    /** @var array<string, array{0:string,1:string}> key => [label, description] */
    public const WORKSPACES = [
        'bql' => ['BQL Dự án', 'Quản lý vận hành một dự án'],
        'hq' => ['Cổng Công ty (HQ)', 'Điều hành đa dự án cấp công ty'],
        'superadmin' => ['SuperAdmin', 'Quản trị nền tảng SaaS'],
    ];

    public function workspace(): string
    {
        $w = session('current_workspace', 'bql');

        return $this->workspaceAllowed($w) ? $w : 'bql';
    }

    public function workspaceLabel(): string
    {
        return self::WORKSPACES[$this->workspace()][0] ?? 'BQL Dự án';
    }

    public function setWorkspace(string $key): void
    {
        if ($this->workspaceAllowed($key)) {
            session(['current_workspace' => $key]);
        }
    }

    public function workspaceAllowed(string $key): bool
    {
        $user = auth()->user();

        return match ($key) {
            'superadmin' => (bool) $user?->isPlatformAdmin(),
            'hq' => (bool) ($user?->isPlatformAdmin() || $user?->isTenantOperator()),
            default => true, // bql — every staff member works within a project
        };
    }

    /** @return array<int, array{key:string, label:string, desc:string, allowed:bool, active:bool}> */
    public function availableWorkspaces(): array
    {
        $current = $this->workspace();

        return collect(self::WORKSPACES)->map(fn (array $meta, string $key) => [
            'key' => $key,
            'label' => $meta[0],
            'desc' => $meta[1],
            'allowed' => $this->workspaceAllowed($key),
            'active' => $key === $current,
        ])->values()->all();
    }
}
