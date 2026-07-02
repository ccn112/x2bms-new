<?php
namespace App\Filament\Hq\Pages;
use App\Filament\Concerns\HqScreen;
use App\Models\MetricSnapshot;
use App\Support\Context\CurrentContext;
use BackedEnum;
use Filament\Pages\Page;
/** HQ-04-01 — Tổng quan phân quyền & hỗ trợ. */
class AccessSupportOverview extends Page {
    use HqScreen;
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-shield-check';
    protected static string|\UnitEnum|null $navigationGroup = 'Hỗ trợ & Phân quyền';
    protected static ?int $navigationSort = 1;
    protected static ?string $navigationLabel = 'Tổng quan phân quyền & hỗ trợ';
    protected static ?string $title = 'Tổng quan phân quyền & hỗ trợ';
    protected static ?string $slug = 'access-support/overview';
    protected string $view = 'filament.hq.pages.access-support-overview';
    protected function getViewData(): array {
        $tid = app(CurrentContext::class)->tenantId();
        $get = fn(string $k) => MetricSnapshot::where('tenant_id',$tid)->where('metric_key',$k)->get();
        return [
            'kpi'=>$get('hq04_kpi')->mapWithKeys(fn($m)=>[$m->dimension['metric']=>(float)$m->value]),
            'userStatus'=>$get('user_status')->sortBy(fn($m)=>$m->dimension['sort']??0)->map(fn($m)=>['label'=>$m->dimension['label'],'count'=>(int)$m->value,'pct'=>$m->dimension['pct'],'color'=>$m->dimension['color']])->values(),
            'roleUsage'=>$get('role_usage')->sortBy(fn($m)=>$m->dimension['sort']??0)->map(fn($m)=>['label'=>$m->dimension['label'],'count'=>(int)$m->value,'pct'=>$m->dimension['pct']])->values(),
            'ticketStatus'=>$get('ticket_status')->sortBy(fn($m)=>$m->dimension['sort']??0)->map(fn($m)=>['label'=>$m->dimension['label'],'count'=>(int)$m->value,'pct'=>$m->dimension['pct'],'color'=>$m->dimension['color']])->values(),
            'ticketSource'=>$get('ticket_source')->sortBy(fn($m)=>$m->dimension['sort']??0)->map(fn($m)=>['label'=>$m->dimension['label'],'count'=>(int)$m->value,'pct'=>$m->dimension['pct']])->values(),
            'byBuilding'=>$get('ticket_by_building')->sortBy(fn($m)=>$m->dimension['sort']??0)->map(fn($m)=>['label'=>$m->dimension['label'],'count'=>(int)$m->value])->values(),
            'csat'=>$get('csat_trend')->map(fn($m)=>['day'=>$m->dimension['day'],'value'=>(float)$m->value]),
        ];
    }
}
