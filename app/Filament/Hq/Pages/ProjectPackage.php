<?php

namespace App\Filament\Hq\Pages;

use App\Filament\Concerns\HqScreen;
use App\Models\Feature;
use App\Models\Plan;
use App\Models\PlanFeature;
use App\Models\Project;
use App\Models\ProjectSubscriptionPeriod;
use Filament\Pages\Page;

/**
 * HQ-01-08 — Chọn gói dịch vụ cho dự án. Thẻ gói (Phổ biến/Đầy đủ/Thông minh) +
 * ma trận so sánh tính năng + cấu hình thuê bao + tóm tắt & trạng thái duyệt platform.
 */
class ProjectPackage extends Page
{
    use HqScreen;

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    protected static ?string $slug = 'projects/{project}/package';

    protected string $view = 'filament.hq.pages.project-package';

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
        return 'Gói dịch vụ — '.$this->project->name;
    }

    protected function getViewData(): array
    {
        $plans = Plan::whereIn('code', ['popular', 'full', 'intelligent'])->orderBy('monthly_base_price')->get();
        $period = ProjectSubscriptionPeriod::where('project_id', $this->project->id)->latest('id')->first();

        // Feature matrix: which plan includes which feature.
        $features = Feature::orderBy('id')->get();
        $planFeatureMap = [];
        foreach ($plans as $plan) {
            $planFeatureMap[$plan->id] = PlanFeature::where('plan_id', $plan->id)->pluck('feature_id')->all();
        }

        return [
            'project' => $this->project,
            'plans' => $plans,
            'currentPlanId' => $period?->plan_id,
            'period' => $period,
            'features' => $features->take(14),
            'planFeatureMap' => $planFeatureMap,
        ];
    }
}
