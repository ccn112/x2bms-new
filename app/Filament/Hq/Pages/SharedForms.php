<?php
namespace App\Filament\Hq\Pages;
use App\Filament\Concerns\HqScreen;
use App\Models\DynamicForm;
use App\Models\MetricSnapshot;
use App\Models\TemplateAssignment;
use App\Support\Context\CurrentContext;
use BackedEnum;
use Filament\Pages\Page;
/** HQ-03-03 — Danh sách biểu mẫu dùng chung. */
class SharedForms extends Page {
    use HqScreen;
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-duplicate';
    protected static string|\UnitEnum|null $navigationGroup = 'Biểu mẫu & Tri thức';
    protected static ?int $navigationSort = 3;
    protected static ?string $navigationLabel = 'Biểu mẫu dùng chung';
    protected static ?string $title = 'Danh sách biểu mẫu dùng chung';
    protected static ?string $slug = 'form-templates';
    protected string $view = 'filament.hq.pages.shared-forms';
    public string $status = 'all';
    protected function getViewData(): array {
        $tid = app(CurrentContext::class)->tenantId();
        $kpi = MetricSnapshot::where('tenant_id',$tid)->where('metric_key','form_kpi')->get()->mapWithKeys(fn($m)=>[$m->dimension['metric']=>(float)$m->value]);
        $counts = TemplateAssignment::where('tenant_id',$tid)->where('assignable_type','form')->selectRaw('assignable_id,count(*) c')->groupBy('assignable_id')->pluck('c','assignable_id');
        $q = DynamicForm::where('tenant_id',$tid);
        if($this->status!=='all') $q->where('status',$this->status);
        $rows = $q->latest('id')->take(40)->get()->map(fn($f)=>['code'=>$f->code,'name'=>$f->name,'category'=>$f->category,'version'=>'v'.$f->current_version,'status'=>$f->status,'applied'=>(int)($counts[$f->id]??0)]);
        return ['kpi'=>$kpi,'rows'=>$rows];
    }
}
