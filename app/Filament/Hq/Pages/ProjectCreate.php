<?php

namespace App\Filament\Hq\Pages;

use App\Filament\Concerns\HqScreen;
use App\Models\AuditLog;
use App\Models\BqlTeam;
use App\Models\Plan;
use App\Models\Project;
use App\Models\ProjectSubscriptionPeriod;
use App\Support\Context\CurrentContext;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Carbon;

/**
 * HQ-01-02 — Tạo / chỉnh sửa hồ sơ dự án (wizard 5 bước + tóm tắt trực tiếp).
 * Ghi thật: projects + project_subscription_periods + bql_teams + audit_logs.
 */
class ProjectCreate extends Page
{
    use HqScreen;

    public static function shouldRegisterNavigation(): bool
    {
        return false; // reached via "Thêm dự án"
    }

    protected static ?string $slug = 'projects/create';

    protected static ?string $title = 'Tạo hồ sơ dự án';

    protected string $view = 'filament.hq.pages.project-create';

    public string $code = '';
    public string $name = '';
    public string $type = 'Chung cư cao cấp';
    public string $address = '';
    public string $city = 'TP. Hồ Chí Minh';
    public int $building_count = 1;
    public int $apartment_count = 0;
    public int $land_area_sqm = 0;
    public string $manager_name = '';
    public string $hotline = '';
    public string $plan_code = 'popular';
    public string $started_at = '';
    public bool $auto_renew = true;

    public function mount(): void
    {
        $this->started_at = Carbon::parse('2026-07-02')->format('Y-m-d');
    }

    public function getPlans()
    {
        return Plan::whereIn('code', ['popular', 'full', 'intelligent'])->get();
    }

    public function save()
    {
        $data = $this->validate([
            'code' => 'required|string|max:32',
            'name' => 'required|string|max:120',
            'type' => 'required|string',
            'building_count' => 'integer|min:0',
            'apartment_count' => 'integer|min:0',
            'plan_code' => 'required|in:popular,full,intelligent',
            'started_at' => 'required|date',
        ]);

        $ctx = app(CurrentContext::class);
        $tenantId = $ctx->tenantId();
        $plan = Plan::where('code', $this->plan_code)->firstOrFail();
        $started = Carbon::parse($this->started_at);

        $project = Project::create([
            'tenant_id' => $tenantId,
            'code' => $this->code,
            'name' => $this->name,
            'type' => $this->type,
            'status' => 'active',
            'address' => $this->address,
            'city' => $this->city,
            'building_count' => $this->building_count,
            'apartment_count' => $this->apartment_count,
            'land_area_sqm' => $this->land_area_sqm,
            'investor' => $ctx->tenant()?->short_name,
            'contact_person' => $this->manager_name,
            'contact_phone' => $this->hotline,
        ]);

        ProjectSubscriptionPeriod::create([
            'tenant_id' => $tenantId,
            'project_id' => $project->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'started_at' => $started,
            'current_period_start' => $started,
            'current_period_end' => $started->copy()->addYear(),
            'billing_anchor_day' => 1,
            'auto_renew' => $this->auto_renew,
            'price_snapshot_json' => ['plan' => $this->plan_code],
            'approved_by_platform_at' => now(),
        ]);

        BqlTeam::create([
            'tenant_id' => $tenantId,
            'project_id' => $project->id,
            'code' => 'BQL-'.$this->code,
            'name' => 'BQL '.$this->name,
            'hotline' => $this->hotline,
            'status' => 'active',
            'metadata' => ['understaffed' => true, 'required_headcount' => 6, 'current_headcount' => 0],
        ]);

        AuditLog::create([
            'tenant_id' => $tenantId,
            'user_id' => auth()->id(),
            'actor_name' => auth()->user()?->name,
            'action' => 'hq.project.create',
            'description' => 'Tạo dự án mới: '.$this->name.' ('.$this->code.')',
        ]);

        Notification::make()->title('Đã tạo dự án '.$this->name)->success()->send();

        return redirect('/hq/projects/'.$project->id);
    }
}
