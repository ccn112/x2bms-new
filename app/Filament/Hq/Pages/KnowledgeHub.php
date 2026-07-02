<?php
namespace App\Filament\Hq\Pages;
use App\Filament\Concerns\HqScreen;
use App\Models\MetricSnapshot;
use App\Support\Context\CurrentContext;
use BackedEnum;
use Filament\Pages\Page;
/** HQ-03-01 — Trung tâm biểu mẫu, tài liệu dùng chung & tri thức AI. */
class KnowledgeHub extends Page {
    use HqScreen;
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-book-open';
    protected static string|\UnitEnum|null $navigationGroup = 'Biểu mẫu & Tri thức';
    protected static ?string $navigationLabel = 'Trung tâm tri thức';
    protected static ?int $navigationSort = 1;
    protected static ?string $title = 'Trung tâm biểu mẫu, tài liệu dùng chung & tri thức AI';
    protected static ?string $slug = 'knowledge-hub';
    protected string $view = 'filament.hq.pages.knowledge-hub';
    protected function getViewData(): array {
        $tid = app(CurrentContext::class)->tenantId();
        $kpi = MetricSnapshot::where('tenant_id',$tid)->where('metric_key','hub_kpi')->get()->mapWithKeys(fn($m)=>[$m->dimension['metric']=>(float)$m->value]);
        $apply = MetricSnapshot::where('tenant_id',$tid)->where('metric_key','apply_rate')->get()->sortBy(fn($m)=>$m->dimension['sort']??0)->map(fn($m)=>['project'=>$m->dimension['project'],'rate'=>(float)$m->value])->values();
        $status = MetricSnapshot::where('tenant_id',$tid)->where('metric_key','ai_kb_status')->get()->sortBy(fn($m)=>$m->dimension['sort']??0)->map(fn($m)=>['label'=>$m->dimension['label'],'count'=>(int)$m->value,'pct'=>$m->dimension['pct']])->values();
        return ['kpi'=>$kpi,'apply'=>$apply,'status'=>$status];
    }
}
