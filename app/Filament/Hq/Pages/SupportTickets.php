<?php
namespace App\Filament\Hq\Pages;
use App\Filament\Concerns\HqScreen;
use App\Models\MetricSnapshot;
use App\Models\SupportTicket;
use App\Support\Context\CurrentContext;
use BackedEnum;
use Filament\Pages\Page;
/** HQ-04-07 — Ticket hỗ trợ. */
class SupportTickets extends Page {
    use HqScreen;
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-ticket';
    protected static string|\UnitEnum|null $navigationGroup = 'Hỗ trợ & Phân quyền';
    protected static ?int $navigationSort = 7;
    protected static ?string $navigationLabel = 'Ticket hỗ trợ';
    protected static ?string $title = 'Ticket hỗ trợ';
    protected static ?string $slug = 'support-tickets';
    protected string $view = 'filament.hq.pages.support-tickets';
    public string $status = 'all';
    protected function getViewData(): array {
        $tid = app(CurrentContext::class)->tenantId();
        $kpi = MetricSnapshot::where('tenant_id',$tid)->where('metric_key','hq04_kpi')->get()->mapWithKeys(fn($m)=>[$m->dimension['metric']=>(float)$m->value]);
        $q = SupportTicket::where('tenant_id',$tid);
        if($this->status!=='all') $q->where('status',$this->status);
        $rows = $q->latest('created_at')->take(40)->get()->map(fn($t)=>['id'=>$t->id,'no'=>$t->ticket_no,'subject'=>$t->subject,'module'=>$t->module,'priority'=>$t->priority,'status'=>$t->status,'sla'=>$t->sla_state,'requester'=>$t->requester_name,'at'=>optional($t->created_at)->format('d/m/Y')]);
        return ['kpi'=>$kpi,'rows'=>$rows];
    }
}
