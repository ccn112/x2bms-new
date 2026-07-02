<?php
namespace App\Filament\Hq\Pages;
use App\Filament\Concerns\HqScreen;
use App\Models\SopTemplate;
use App\Models\ChecklistTemplate;
use App\Support\Context\CurrentContext;
use BackedEnum;
use Filament\Pages\Page;
/** HQ-03-05 — SOP & checklist vận hành mẫu. */
class SopChecklists extends Page {
    use HqScreen;
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-check';
    protected static string|\UnitEnum|null $navigationGroup = 'Biểu mẫu & Tri thức';
    protected static ?int $navigationSort = 5;
    protected static ?string $navigationLabel = 'SOP & Checklist';
    protected static ?string $title = 'SOP & checklist vận hành mẫu';
    protected static ?string $slug = 'sop-checklists';
    protected string $view = 'filament.hq.pages.sop-checklists';
    protected function getViewData(): array {
        $tid = app(CurrentContext::class)->tenantId();
        return [
            'sops'=>SopTemplate::where('tenant_id',$tid)->get()->map(fn($s)=>['code'=>$s->code,'name'=>$s->name,'category'=>$s->category,'version'=>$s->version,'steps'=>count($s->steps??[]),'status'=>$s->status]),
            'checklists'=>ChecklistTemplate::where('tenant_id',$tid)->get()->map(fn($c)=>['code'=>$c->code,'name'=>$c->name,'category'=>$c->category,'items'=>$c->item_count,'version'=>$c->version,'status'=>$c->status]),
        ];
    }
}
