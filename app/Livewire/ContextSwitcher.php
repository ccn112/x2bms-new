<?php

namespace App\Livewire;

use App\Models\AuditLog;
use App\Models\Project;
use App\Models\Tenant;
use App\Support\Context\CurrentContext;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * WEB-UX-03 — Chuyển ngữ cảnh làm việc (unified context switcher).
 * One modal combining Company (tenant) → Project → Workspace/Role selection, replacing
 * the separate header workspace switcher + project selector. Permission-gated: the
 * Company column shows only for platform admins; workspace cards show only those the user
 * may access; projects are limited to the ones granted to the user. Apply sets the
 * context + redirects to the chosen workspace's panel (bql→/admin, hq→/hq, superadmin→/sa).
 */
class ContextSwitcher extends Component
{
    public bool $open = false;

    public ?int $tenantId = null;

    public ?int $projectId = null;

    public string $workspace = 'bql';

    public bool $remember = true;

    public string $companyQuery = '';

    public string $projectQuery = '';

    #[On('open-x2-context')]
    public function openContext(): void
    {
        $ctx = app(CurrentContext::class);
        $this->tenantId = $ctx->tenantId();
        $this->projectId = $ctx->projectId();
        $this->workspace = $ctx->workspace();
        $this->open = true;
    }

    public function closeContext(): void
    {
        $this->open = false;
    }

    public function canSwitchCompany(): bool
    {
        return (bool) auth()->user()?->isPlatformAdmin();
    }

    public function selectCompany(int $id): void
    {
        if (! $this->canSwitchCompany()) {
            return;
        }
        $this->tenantId = $id;
        // Reset project to the first one of the newly selected company.
        $this->projectId = Project::where('tenant_id', $id)->orderBy('name')->value('id');
    }

    public function selectProject(int $id): void
    {
        $this->projectId = $id;
    }

    public function selectWorkspace(string $key): void
    {
        if (app(CurrentContext::class)->workspaceAllowed($key)) {
            $this->workspace = $key;
        }
    }

    /** @return \Illuminate\Support\Collection<int,Tenant> */
    public function getCompaniesProperty()
    {
        if (! $this->canSwitchCompany()) {
            return collect();
        }

        return Tenant::query()
            ->when($this->companyQuery !== '', fn ($q) => $q->where('name', 'like', '%'.$this->companyQuery.'%'))
            ->orderBy('name')->limit(50)->get();
    }

    /** @return \Illuminate\Support\Collection<int,Project> */
    public function getProjectsProperty()
    {
        $ctx = app(CurrentContext::class);

        // Platform admin: projects of the (tentatively) selected company. Others: only granted projects.
        if ($this->canSwitchCompany()) {
            $query = Project::query()->when($this->tenantId, fn ($q) => $q->where('tenant_id', $this->tenantId));
        } else {
            $ids = $ctx->availableProjects()->pluck('id')->all();
            $query = Project::query()->whereIn('id', $ids ?: [0]);
        }

        return $query->when($this->projectQuery !== '', fn ($q) => $q->where('name', 'like', '%'.$this->projectQuery.'%'))
            ->orderBy('name')->limit(100)->get();
    }

    /** @return array<int,array{key:string,label:string,desc:string,allowed:bool,active:bool}> */
    public function getWorkspacesProperty(): array
    {
        // Only workspaces the user may access (hide HQ / SuperAdmin when not permitted).
        return array_values(array_filter(
            app(CurrentContext::class)->availableWorkspaces(),
            fn ($w) => $w['allowed']
        ));
    }

    /** @return array<int,string> */
    public function getRolesProperty(): array
    {
        $u = auth()->user();

        return method_exists($u, 'getRoleNames') && $u->getRoleNames()->isNotEmpty()
            ? $u->getRoleNames()->all()
            : [app(CurrentContext::class)->workspaceLabel()];
    }

    public function apply(): void
    {
        $ctx = app(CurrentContext::class);
        $user = auth()->user();

        if ($this->canSwitchCompany() && $this->tenantId) {
            session(['hq_tenant_id' => $this->tenantId]);
        }
        if ($this->projectId) {
            $ctx->setProject($this->projectId);
        }
        if ($ctx->workspaceAllowed($this->workspace)) {
            $ctx->setWorkspace($this->workspace);
        }

        AuditLog::create([
            'tenant_id' => $user->tenant_id, 'building_id' => $user->building_id,
            'user_id' => $user->id, 'actor_name' => $user->name,
            'action' => 'context.switch',
            'description' => 'Chuyển ngữ cảnh: '.($ctx->project()?->name ?? '—').' · '.$ctx->workspaceLabel(),
        ]);

        $this->redirect(match ($this->workspace) {
            'hq' => '/hq',
            'superadmin' => '/sa',
            default => '/admin',
        });
    }

    public function render()
    {
        return view('livewire.context-switcher');
    }
}
