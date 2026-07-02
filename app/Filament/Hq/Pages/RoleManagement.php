<?php
namespace App\Filament\Hq\Pages;
use App\Filament\Concerns\HqScreen;
use App\Models\MetricSnapshot;
use App\Support\Context\CurrentContext;
use BackedEnum;
use Filament\Pages\Page;
use Spatie\Permission\Models\Role;
/** HQ-04-03 — Vai trò. */
class RoleManagement extends Page {
    use HqScreen;
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-identification';
    protected static string|\UnitEnum|null $navigationGroup = 'Hỗ trợ & Phân quyền';
    protected static ?int $navigationSort = 3;
    protected static ?string $navigationLabel = 'Vai trò';
    protected static ?string $title = 'Quản lý vai trò';
    protected static ?string $slug = 'roles';
    protected string $view = 'filament.hq.pages.role-management';
    protected function getViewData(): array {
        $tid = app(CurrentContext::class)->tenantId();
        $usage = MetricSnapshot::where('tenant_id',$tid)->where('metric_key','role_usage')->get()->mapWithKeys(fn($m)=>[$m->dimension['label']=>(int)$m->value]);
        $labels = ['super_admin'=>'Quản trị nền tảng','platform_support'=>'Hỗ trợ nền tảng','billing_admin'=>'Quản trị billing','company_admin'=>'Quản trị HQ','hq_finance'=>'Tài chính HQ','operations_director'=>'Giám đốc vận hành','building_manager'=>'Quản lý tòa nhà','accountant'=>'Kế toán','cashier'=>'Thủ quỹ','customer_service'=>'CSKH','technician'=>'Kỹ thuật viên','security'=>'Bảo vệ','shift_leader'=>'Trưởng ca','communication_officer'=>'Truyền thông'];
        $rows = Role::withCount('permissions')->orderBy('id')->get()->map(fn($r)=>['name'=>$labels[$r->name]??$r->name,'key'=>$r->name,'perms'=>$r->permissions_count,'users'=>(int)($usage[$labels[$r->name]??'']??0)]);
        return ['rows'=>$rows,'total'=>$rows->count()];
    }
}
