<?php
namespace App\Filament\Hq\Pages;
use App\Filament\Concerns\HqScreen;
use App\Models\AiTestRun;
use App\Support\Context\CurrentContext;
use BackedEnum;
use Filament\Pages\Page;
/** HQ-03-10 — Kiểm tra AI trả lời có dẫn nguồn. */
class AiKnowledgeTest extends Page {
    use HqScreen;
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-beaker';
    protected static string|\UnitEnum|null $navigationGroup = 'Biểu mẫu & Tri thức';
    protected static ?int $navigationSort = 10;
    protected static ?string $navigationLabel = 'Kiểm tra AI dẫn nguồn';
    protected static ?string $title = 'Kiểm tra AI trả lời có dẫn nguồn';
    protected static ?string $slug = 'ai-knowledge-test';
    protected string $view = 'filament.hq.pages.ai-knowledge-test';
    protected function getViewData(): array {
        $tid = app(CurrentContext::class)->tenantId();
        $runs = AiTestRun::where('tenant_id',$tid)->with('question')->latest('ran_at')->get()->map(fn($r)=>['question'=>$r->question?->question??'—','answer'=>$r->answer,'sources'=>$r->cited_sources??[],'cited'=>$r->has_citation,'score'=>(float)$r->score,'at'=>optional($r->ran_at)->format('d/m H:i')]);
        $total=$runs->count(); $cited=$runs->where('cited',true)->count();
        return ['runs'=>$runs,'total'=>$total,'cited'=>$cited,'rate'=>$total?round($cited/$total*100,1):0,'avgScore'=>round($runs->avg('score'),1)];
    }
}
