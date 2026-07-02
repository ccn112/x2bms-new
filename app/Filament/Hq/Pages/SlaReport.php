<?php
namespace App\Filament\Hq\Pages;
use App\Filament\Concerns\HqScreen;
use App\Models\MetricSnapshot;
use App\Support\Context\CurrentContext;
use BackedEnum;
use Filament\Pages\Page;
/** HQ-04-09 — Báo cáo SLA. */
class SlaReport extends Page {
    use HqScreen;
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-clock';
    protected static string|\UnitEnum|null $navigationGroup = 'Hỗ trợ & Phân quyền';
    protected static ?int $navigationSort = 9;
    protected static ?string $navigationLabel = 'Báo cáo SLA';
    protected static ?string $title = 'Báo cáo SLA';
    protected static ?string $slug = 'support-sla-report';
    protected string $view = 'filament.hq.pages.sla-report';
    protected function getViewData(): array {
        $tid = app(CurrentContext::class)->tenantId();
        $sla = MetricSnapshot::where('tenant_id',$tid)->where('metric_key','sla_kpi')->get()->mapWithKeys(fn($m)=>[$m->dimension['metric']=>(float)$m->value]);
        $byBuilding = MetricSnapshot::where('tenant_id',$tid)->where('metric_key','ticket_by_building')->get()->sortBy(fn($m)=>$m->dimension['sort']??0)->map(fn($m)=>['label'=>$m->dimension['label'],'count'=>(int)$m->value])->values();
        return ['sla'=>$sla,'byBuilding'=>$byBuilding];
    }
}
