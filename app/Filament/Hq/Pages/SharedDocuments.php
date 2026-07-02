<?php
namespace App\Filament\Hq\Pages;
use App\Filament\Concerns\HqScreen;
use App\Models\Document;
use App\Models\DocumentLibrary;
use App\Models\MetricSnapshot;
use App\Support\Context\CurrentContext;
use BackedEnum;
use Filament\Pages\Page;
/** HQ-03-02 — Thư viện tài liệu, SOP & chính sách dùng chung. */
class SharedDocuments extends Page {
    use HqScreen;
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-folder';
    protected static string|\UnitEnum|null $navigationGroup = 'Biểu mẫu & Tri thức';
    protected static ?int $navigationSort = 2;
    protected static ?string $navigationLabel = 'Thư viện tài liệu';
    protected static ?string $title = 'Thư viện tài liệu, SOP & chính sách dùng chung';
    protected static ?string $slug = 'shared-documents';
    protected string $view = 'filament.hq.pages.shared-documents';
    public string $tab = 'all';
    protected function getViewData(): array {
        $tid = app(CurrentContext::class)->tenantId();
        $kpi = MetricSnapshot::where('tenant_id',$tid)->where('metric_key','doc_kpi')->get()->mapWithKeys(fn($m)=>[$m->dimension['metric']=>(float)$m->value]);
        $folders = DocumentLibrary::where('tenant_id',$tid)->whereNull('parent_id')->orderBy('sort')->get();
        $q = Document::where('tenant_id',$tid)->with('owner');
        if($this->tab!=='all') $q->where('type',$this->tab);
        $rows = $q->latest('id')->take(40)->get()->map(fn($d)=>['code'=>$d->code,'name'=>$d->name,'type'=>$d->type,'version'=>$d->version,'effective'=>optional($d->effective_from)->format('d/m/Y'),'owner'=>$d->owner?->name??'—','scope'=>$d->scope,'sync'=>$d->ai_sync_status,'status'=>$d->status]);
        return ['kpi'=>$kpi,'folders'=>$folders,'rows'=>$rows];
    }
}
