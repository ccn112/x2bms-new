<?php
namespace App\Filament\Hq\Pages;
use App\Filament\Concerns\HqScreen;
use App\Models\MetricSnapshot;
use App\Models\StaffProfile;
use App\Models\EmployeeProjectAssignment;
use App\Support\Context\CurrentContext;
use BackedEnum;
use Filament\Pages\Page;
/** HQ-04-02 — Người dùng. */
class UserManagement extends Page {
    use HqScreen;
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-user-group';
    protected static string|\UnitEnum|null $navigationGroup = 'Hỗ trợ & Phân quyền';
    protected static ?int $navigationSort = 2;
    protected static ?string $navigationLabel = 'Người dùng';
    protected static ?string $title = 'Quản lý người dùng';
    protected static ?string $slug = 'users';
    protected string $view = 'filament.hq.pages.user-management';
    public string $search = '';
    protected function getViewData(): array {
        $tid = app(CurrentContext::class)->tenantId();
        $kpi = MetricSnapshot::where('tenant_id',$tid)->where('metric_key','hq04_kpi')->get()->mapWithKeys(fn($m)=>[$m->dimension['metric']=>(float)$m->value]);
        $us = MetricSnapshot::where('tenant_id',$tid)->where('metric_key','user_status')->get()->mapWithKeys(fn($m)=>[$m->dimension['label']=>(int)$m->value]);
        $proj = EmployeeProjectAssignment::where('tenant_id',$tid)->selectRaw('employee_id,count(distinct project_id) c')->groupBy('employee_id')->pluck('c','employee_id');
        $q = StaffProfile::where('staff_profiles.tenant_id',$tid)->with(['user','department']);
        if($this->search!=='') $q->whereHas('user',fn($u)=>$u->where('name','like','%'.$this->search.'%'))->orWhere('employee_code','like','%'.$this->search.'%');
        $rows = $q->orderBy('employee_code')->take(50)->get()->map(fn($s)=>['code'=>$s->employee_code,'name'=>$s->user?->name??'—','position'=>$s->position,'dept'=>$s->department?->name??'—','projects'=>(int)($proj[$s->id]??0),'status'=>$s->status,'hired'=>optional($s->hire_date)->format('d/m/Y')]);
        return ['kpi'=>$kpi,'us'=>$us,'rows'=>$rows];
    }
}
