<?php
namespace App\Filament\Hq\Pages;
use App\Filament\Concerns\HqScreen;
use App\Models\TemplateAssignment;
use App\Support\Context\CurrentContext;
use BackedEnum;
use Filament\Pages\Page;
/** HQ-03-06 — Áp biểu mẫu/SOP xuống dự án. */
class TemplateAssignments extends Page {
    use HqScreen;
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-arrow-down-on-square-stack';
    protected static string|\UnitEnum|null $navigationGroup = 'Biểu mẫu & Tri thức';
    protected static ?int $navigationSort = 6;
    protected static ?string $navigationLabel = 'Áp xuống dự án';
    protected static ?string $title = 'Áp biểu mẫu / SOP xuống dự án';
    protected static ?string $slug = 'template-assignments';
    protected string $view = 'filament.hq.pages.template-assignments';
    protected function getViewData(): array {
        $tid = app(CurrentContext::class)->tenantId();
        $rows = TemplateAssignment::where('tenant_id',$tid)->with('project')->latest('assigned_at')->get()->map(fn($a)=>['type'=>$a->assignable_type,'name'=>$a->resource_name,'project'=>$a->project?->name??'Tất cả','mode'=>$a->mode,'status'=>$a->status,'at'=>optional($a->assigned_at)->format('d/m/Y')]);
        return ['rows'=>$rows];
    }
}
