<?php
namespace App\Filament\Hq\Pages;
use App\Filament\Concerns\HqScreen;
use App\Models\AiKnowledgeSource;
use App\Models\AiKnowledgeSyncLog;
use App\Support\Context\CurrentContext;
use BackedEnum;
use Filament\Pages\Page;
/** HQ-03-09 — Cấu hình tri thức cho X2AI. */
class AiKnowledgeSources extends Page {
    use HqScreen;
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-cpu-chip';
    protected static string|\UnitEnum|null $navigationGroup = 'Biểu mẫu & Tri thức';
    protected static ?int $navigationSort = 9;
    protected static ?string $navigationLabel = 'Cấu hình tri thức AI';
    protected static ?string $title = 'Cấu hình tri thức cho X2AI';
    protected static ?string $slug = 'ai-knowledge-sources';
    protected string $view = 'filament.hq.pages.ai-knowledge-sources';
    protected function getViewData(): array {
        $tid = app(CurrentContext::class)->tenantId();
        $sources = AiKnowledgeSource::where('tenant_id',$tid)->get();
        $logs = AiKnowledgeSyncLog::where('tenant_id',$tid)->latest('ran_at')->take(10)->get()->map(fn($l)=>['event'=>$l->event,'new'=>$l->items_new,'updated'=>$l->items_updated,'errors'=>$l->errors,'status'=>$l->status,'at'=>optional($l->ran_at)->format('d/m H:i')]);
        return [
            'sources'=>$sources->map(fn($s)=>['name'=>$s->name,'provider'=>$s->provider,'status'=>$s->status,'gb'=>(float)$s->size_gb,'items'=>$s->indexed_items,'synced'=>optional($s->last_synced_at)->diffForHumans()]),
            'logs'=>$logs,
            'totalGb'=>(float)$sources->sum('size_gb'),
            'totalItems'=>(int)$sources->sum('indexed_items'),
        ];
    }
}
