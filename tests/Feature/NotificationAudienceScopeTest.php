<?php

namespace Tests\Feature;

use App\Filament\Pages\NotificationCenter;
use App\Models\Building;
use App\Models\Notification;
use App\Models\Project;
use App\Models\Tenant;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * G9 (anti-bypass) — KHÓA CỨNG phạm vi thông báo bằng TEST, không tin vào việc form
 * chỉ ẩn lựa chọn. Cô lập theo tenant/dự án phải đúng ở tầng dữ liệu (không dựa
 * `tenant_id` đơn thuần — TECH_DEBT T1):
 *  - Đọc: `Notification::scopeVisibleTo` — BQL/HQ không thấy thông báo tenant khác;
 *    platform admin thấy tất.
 *  - Quản trị: `canManageBy` — không sửa/phát hành được thông báo ngoài phạm vi.
 *
 * Xem ADR-003 (kỷ luật tenant scope) + NotificationCenter::audienceTargetOptions.
 */
class NotificationAudienceScopeTest extends TestCase
{
    use RefreshDatabase;

    public function test_bql_khong_thay_thong_bao_tenant_du_an_khac(): void
    {
        [$tA, $pA] = $this->tenantProject('A');
        [$tB, $pB] = $this->tenantProject('B');
        $bqlA = $this->bql('bqla@test.vn', $tA, $pA);

        $mine = $this->projectNotification($tA, $pA, 'Của dự án A');
        $other = $this->projectNotification($tB, $pB, 'Của dự án B (MUST_NOT_LEAK)');

        $ids = Notification::query()->visibleTo($bqlA)->pluck('id');
        $this->assertTrue($ids->contains($mine->id), 'thấy thông báo của mình');
        $this->assertFalse($ids->contains($other->id), 'BQL A KHÔNG được thấy thông báo dự án B');
    }

    public function test_platform_admin_thay_moi_tenant(): void
    {
        [$tA, $pA] = $this->tenantProject('A');
        [$tB, $pB] = $this->tenantProject('B');
        $sa = User::create([
            'name' => 'SA', 'email' => 'sa@test.vn', 'password' => bcrypt('x'),
            'account_type' => 'staff', 'is_platform_admin' => true,
        ]);
        $a = $this->projectNotification($tA, $pA, 'A');
        $b = $this->projectNotification($tB, $pB, 'B');

        $ids = Notification::query()->visibleTo($sa)->pluck('id');
        $this->assertTrue($ids->contains($a->id) && $ids->contains($b->id), 'platform admin thấy cả hai tenant');
    }

    public function test_canManageBy_chan_thong_bao_ngoai_pham_vi(): void
    {
        [$tA, $pA] = $this->tenantProject('A');
        [$tB, $pB] = $this->tenantProject('B');
        $bqlA = $this->bql('bqla2@test.vn', $tA, $pA);

        $mine = $this->projectNotification($tA, $pA, 'A');
        $other = $this->projectNotification($tB, $pB, 'B');

        $this->assertTrue($mine->canManageBy($bqlA), 'quản trị được thông báo dự án mình');
        $this->assertFalse($other->canManageBy($bqlA), 'KHÔNG quản trị được thông báo dự án B');

        $sa = User::create(['name' => 'SA', 'email' => 'sa2@test.vn', 'password' => bcrypt('x'), 'account_type' => 'staff', 'is_platform_admin' => true]);
        $this->assertTrue($other->canManageBy($sa), 'platform admin quản trị được');
    }

    public function test_bql_bi_chan_khi_soan_ra_toa_ngoai_pham_vi(): void
    {
        // Render trang Filament trong Livewire test tốn bộ nhớ — tự nâng để chạy được
        // cả dưới `artisan test` (child process mặc định 128MB ở Herd/Windows).
        ini_set('memory_limit', '1024M');

        [$tA, $pA] = $this->tenantProject('CA');
        [$tB, $pB] = $this->tenantProject('CB');
        $buildingB = Building::where('code', 'BLD-CB')->first();   // tòa của tenant B

        $bql = $this->bql('bql-compose@test.vn', $tA, $pA);
        $bql->assignRole(Role::findOrCreate('building_manager', 'web'));   // canAccessPanel

        $this->actingAs($bql);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        // BQL A cố soạn thông báo nhắm TÒA B (ngoài phạm vi) — mô phỏng POST giả mạo.
        Livewire::test(NotificationCenter::class)->callAction('compose', [
            'type' => 'announcement', 'title' => 'Vượt phạm vi', 'priority' => 'normal',
            'audience_scope' => 'building', 'audience_target' => $buildingB->id,
            'channels' => ['app'], 'publish_now' => true,
        ]);

        // Chốt chặn phía server → KHÔNG tạo thông báo/audience trỏ tòa B.
        $this->assertDatabaseMissing('notifications', ['title' => 'Vượt phạm vi']);
        $this->assertDatabaseMissing('notification_audiences', ['scope_type' => 'building', 'scope_id' => $buildingB->id]);
    }

    /** @return array{0:Tenant,1:Project} */
    private function tenantProject(string $tag): array
    {
        $t = Tenant::create(['code' => "TEN-$tag", 'name' => "Tenant $tag"]);
        $p = Project::create(['tenant_id' => $t->id, 'code' => "PRJ-$tag", 'name' => "Project $tag"]);
        Building::create(['tenant_id' => $t->id, 'project_id' => $p->id, 'code' => "BLD-$tag", 'name' => "Building $tag"]);

        return [$t, $p];
    }

    /** BQL cấp dự án: staff, không platform admin, không tenant-op; home project → accessibleProjectIds. */
    private function bql(string $email, Tenant $t, Project $p): User
    {
        return User::create([
            'name' => 'BQL', 'email' => $email, 'password' => bcrypt('x'),
            'account_type' => 'staff', 'is_platform_admin' => false,
            'tenant_id' => $t->id, 'project_id' => $p->id,
        ]);
    }

    private function projectNotification(Tenant $t, Project $p, string $title): Notification
    {
        return Notification::create([
            'owner_level' => 'project', 'tenant_id' => $t->id, 'project_id' => $p->id,
            'title' => $title, 'status' => 'published', 'published_at' => now(),
        ]);
    }
}
