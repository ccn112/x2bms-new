<?php
namespace App\Filament\Hq\Pages;
use App\Filament\Concerns\HqScreen;
use App\Models\PermissionGroup;
use App\Support\Context\CurrentContext;
use BackedEnum;
use Filament\Pages\Page;
/** HQ-04-05 — Ma trận phân quyền. */
class PermissionMatrix extends Page {
    use HqScreen;
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-table-cells';
    protected static string|\UnitEnum|null $navigationGroup = 'Hỗ trợ & Phân quyền';
    protected static ?int $navigationSort = 5;
    protected static ?string $navigationLabel = 'Ma trận phân quyền';
    protected static ?string $title = 'Ma trận phân quyền';
    protected static ?string $slug = 'permission-matrix';
    protected string $view = 'filament.hq.pages.permission-matrix';
    protected function getViewData(): array {
        $tid = app(CurrentContext::class)->tenantId();
        $modules = PermissionGroup::where('tenant_id',$tid)->orderBy('id')->get()->map(fn($g)=>$g->module ?: $g->name)->unique()->values();
        $roles = [['Quản trị HQ','all'],['Quản lý tòa','ops'],['Kế toán','fin'],['Kỹ thuật','tech'],['Lễ tân','recep'],['Bảo vệ','sec']];
        return ['modules'=>$modules,'roles'=>$roles,'actions'=>['Xem','Thêm','Sửa','Xóa','Duyệt']];
    }
}
