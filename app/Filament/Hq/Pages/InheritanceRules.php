<?php
namespace App\Filament\Hq\Pages;
use App\Filament\Concerns\HqScreen;
use App\Models\ConfigInheritanceRule;
use App\Support\Context\CurrentContext;
use BackedEnum;
use Filament\Pages\Page;
/** HQ-03-07 — Quy tắc kế thừa & override. */
class InheritanceRules extends Page {
    use HqScreen;
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-share';
    protected static string|\UnitEnum|null $navigationGroup = 'Biểu mẫu & Tri thức';
    protected static ?int $navigationSort = 7;
    protected static ?string $navigationLabel = 'Quy tắc kế thừa';
    protected static ?string $title = 'Quy tắc kế thừa & override';
    protected static ?string $slug = 'config-inheritance';
    protected string $view = 'filament.hq.pages.inheritance-rules';
    protected function getViewData(): array {
        $tid = app(CurrentContext::class)->tenantId();
        return ['rows'=>ConfigInheritanceRule::where('tenant_id',$tid)->orderBy('priority')->get()->map(fn($r)=>['type'=>$r->resource_type,'from'=>$r->scope_from,'to'=>$r->scope_to,'mode'=>$r->mode,'priority'=>$r->priority,'status'=>$r->status,'note'=>$r->note])];
    }
}
