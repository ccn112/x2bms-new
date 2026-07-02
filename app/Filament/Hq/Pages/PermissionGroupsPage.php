<?php
namespace App\Filament\Hq\Pages;
use App\Filament\Concerns\HqScreen;
use App\Models\PermissionGroup;
use App\Support\Context\CurrentContext;
use BackedEnum;
use Filament\Pages\Page;
/** HQ-04-04 — Nhóm quyền. */
class PermissionGroupsPage extends Page {
    use HqScreen;
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-group';
    protected static string|\UnitEnum|null $navigationGroup = 'Hỗ trợ & Phân quyền';
    protected static ?int $navigationSort = 4;
    protected static ?string $navigationLabel = 'Nhóm quyền';
    protected static ?string $title = 'Nhóm quyền';
    protected static ?string $slug = 'permission-groups';
    protected string $view = 'filament.hq.pages.permission-groups';
    protected function getViewData(): array {
        $tid = app(CurrentContext::class)->tenantId();
        return ['rows'=>PermissionGroup::where('tenant_id',$tid)->get()->map(fn($g)=>['code'=>$g->code,'name'=>$g->name,'desc'=>$g->description,'module'=>$g->module,'perms'=>$g->permission_count,'roles'=>$g->role_count,'status'=>$g->status])];
    }
}
