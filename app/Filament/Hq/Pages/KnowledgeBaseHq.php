<?php
namespace App\Filament\Hq\Pages;
use App\Filament\Concerns\HqScreen;
use App\Models\KnowledgeArticle;
use App\Models\KnowledgeCategory;
use App\Support\Context\CurrentContext;
use BackedEnum;
use Filament\Pages\Page;
/** HQ-03-08 — Knowledge Base cho BQL. */
class KnowledgeBaseHq extends Page {
    use HqScreen;
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-academic-cap';
    protected static string|\UnitEnum|null $navigationGroup = 'Biểu mẫu & Tri thức';
    protected static ?int $navigationSort = 8;
    protected static ?string $navigationLabel = 'Kho tri thức BQL';
    protected static ?string $title = 'Knowledge Base cho BQL';
    protected static ?string $slug = 'knowledge-base';
    protected string $view = 'filament.hq.pages.knowledge-base-hq';
    protected function getViewData(): array {
        $tid = app(CurrentContext::class)->tenantId();
        $cats = KnowledgeCategory::where('tenant_id',$tid)->get();
        $articles = KnowledgeArticle::where('tenant_id',$tid)->with('category')->latest('published_at')->take(30)->get()->map(fn($a)=>['title'=>$a->title,'category'=>$a->category?->name??'—','views'=>$a->views,'helpful'=>$a->helpful_count,'status'=>$a->status,'published'=>optional($a->published_at)->format('d/m/Y')]);
        return ['cats'=>$cats,'articles'=>$articles];
    }
}
