<?php
namespace App\Filament\Hq\Pages;
use App\Filament\Concerns\HqScreen;
use App\Models\AuditLog;
use App\Support\Context\CurrentContext;
use BackedEnum;
use Filament\Pages\Page;
/** HQ-04-06 — Nhật ký hoạt động. */
class HqActivityLog extends Page {
    use HqScreen;
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static string|\UnitEnum|null $navigationGroup = 'Hỗ trợ & Phân quyền';
    protected static ?int $navigationSort = 6;
    protected static ?string $navigationLabel = 'Nhật ký hoạt động';
    protected static ?string $title = 'Nhật ký hoạt động';
    protected static ?string $slug = 'audit-logs';
    protected string $view = 'filament.hq.pages.hq-activity-log';
    protected function getViewData(): array {
        $tid = app(CurrentContext::class)->tenantId();
        $rows = AuditLog::where('tenant_id',$tid)->latest('id')->take(50)->get()->map(fn($a)=>['actor'=>$a->actor_name,'action'=>$a->action,'desc'=>$a->description,'at'=>optional($a->created_at)->format('d/m/Y H:i')]);
        return ['rows'=>$rows];
    }
}
