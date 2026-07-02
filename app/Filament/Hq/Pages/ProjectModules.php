<?php

namespace App\Filament\Hq\Pages;

use App\Filament\Concerns\HqScreen;
use App\Models\Plan;
use App\Models\PlanFeature;
use App\Models\Project;
use App\Models\ProjectModuleOverride;
use App\Models\ProjectSubscriptionPeriod;
use Filament\Pages\Page;

/**
 * HQ-01-09 — Trạng thái module theo dự án.
 * Metrics (total/enabled/addon/pending/locked) + bảng entitlement + panel nguồn/pending/log.
 */
class ProjectModules extends Page
{
    use HqScreen;

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    protected static ?string $slug = 'projects/{project}/modules';

    protected string $view = 'filament.hq.pages.project-modules';

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
        return 'Module — '.$this->project->name;
    }

    protected function getViewData(): array
    {
        $period = ProjectSubscriptionPeriod::where('project_id', $this->project->id)->latest('id')->first();
        $plan = $period ? Plan::find($period->plan_id) : null;

        // Module keys from plan features (source = package) + overrides.
        $planFeatures = $plan
            ? PlanFeature::where('plan_id', $plan->id)->with('feature')->get()
                ->map(fn ($pf) => $pf->feature?->code)->filter()->values()
            : collect();

        $overrides = ProjectModuleOverride::where('project_id', $this->project->id)->get()->keyBy('module_key');

        $rows = collect();
        foreach ($planFeatures as $key) {
            $ov = $overrides->get($key);
            $rows->push([
                'key' => $key,
                'source' => $ov?->source ?? 'package',
                'status' => $ov?->status ?? 'enabled',
                'approver' => $ov?->approvedBy?->name ?? '—',
                'from' => optional($ov?->effective_from)->format('d/m/Y') ?? '—',
            ]);
        }
        // Overrides not in plan (addons).
        foreach ($overrides as $key => $ov) {
            if (! $planFeatures->contains($key)) {
                $rows->push(['key' => $key, 'source' => $ov->source, 'status' => $ov->status,
                    'approver' => $ov->approvedBy?->name ?? '—', 'from' => optional($ov->effective_from)->format('d/m/Y') ?? '—']);
            }
        }

        return [
            'project' => $this->project,
            'planName' => $plan?->name,
            'rows' => $rows,
            'metrics' => [
                'total' => $rows->count(),
                'enabled' => $rows->where('status', 'enabled')->count(),
                'addon' => $rows->where('source', 'addon')->count(),
                'pending' => $rows->where('status', 'pending')->count(),
                'locked' => $rows->where('status', 'locked')->count(),
            ],
            'pending' => $rows->where('status', 'pending')->values(),
        ];
    }
}
