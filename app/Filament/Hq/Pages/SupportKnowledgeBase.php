<?php
namespace App\Filament\Hq\Pages;
use App\Filament\Concerns\HqScreen;
use App\Models\SupportKbArticle;
use App\Models\SupportKbCategory;
use BackedEnum;
use Filament\Pages\Page;
/** HQ-04-10 — Cơ sở tri thức hỗ trợ. */
class SupportKnowledgeBase extends Page {
    use HqScreen;
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-lifebuoy';
    protected static string|\UnitEnum|null $navigationGroup = 'Hỗ trợ & Phân quyền';
    protected static ?int $navigationSort = 10;
    protected static ?string $navigationLabel = 'Cơ sở tri thức hỗ trợ';
    protected static ?string $title = 'Cơ sở tri thức hỗ trợ';
    protected static ?string $slug = 'support-knowledge-base';
    protected string $view = 'filament.hq.pages.support-knowledge-base';
    protected function getViewData(): array {
        $cats = SupportKbCategory::withCount('articles')->orderBy('sort_order')->get();
        $articles = SupportKbArticle::where('status','published')->latest('published_at')->take(30)->get()->map(fn($a)=>['title'=>$a->title,'code'=>$a->code,'rating'=>(float)$a->rating,'views'=>$a->views,'published'=>optional($a->published_at)->format('d/m/Y')]);
        return ['cats'=>$cats,'articles'=>$articles];
    }
}
