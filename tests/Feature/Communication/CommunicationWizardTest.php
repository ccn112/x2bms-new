<?php

namespace Tests\Feature\Communication;

use App\Enums\CommunicationWorkflowStatus as WS;
use App\Filament\Pages\CommunicationWizard;
use App\Models\Apartment;
use App\Models\Building;
use App\Models\Notification;
use App\Models\Project;
use App\Models\Resident;
use App\Models\ResidentApartmentRelation;
use App\Models\Tenant;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/** T3 — wizard BQL-NOTI-02→06: mount tạo draft, gửi duyệt chốt audience + chuyển pending_approval. */
class CommunicationWizardTest extends TestCase
{
    use RefreshDatabase;

    public function test_mount_tao_draft_va_gui_duyet_chot_nguoi_nhan(): void
    {
        ini_set('memory_limit', '1024M');

        $t = Tenant::create(['code' => 'TEN-W', 'name' => 'Tenant W']);
        $p = Project::create(['tenant_id' => $t->id, 'code' => 'PRJ-W', 'name' => 'Project W']);
        $b = Building::create(['tenant_id' => $t->id, 'project_id' => $p->id, 'code' => 'S-W', 'name' => 'Tòa W']);
        $ap = Apartment::create(['tenant_id' => $t->id, 'building_id' => $b->id, 'code' => 'W-01', 'status' => 'occupied']);
        $res = Resident::create(['tenant_id' => $t->id, 'building_id' => $b->id, 'code' => 'RW', 'full_name' => 'Cư dân W', 'status' => 'active']);
        ResidentApartmentRelation::create(['tenant_id' => $t->id, 'resident_id' => $res->id, 'apartment_id' => $ap->id, 'role' => 'owner']);

        $bql = User::create([
            'name' => 'BQL W', 'email' => 'bqlw@test.vn', 'password' => bcrypt('x'),
            'account_type' => 'staff', 'is_platform_admin' => false, 'tenant_id' => $t->id, 'project_id' => $p->id,
        ]);
        $bql->assignRole(Role::findOrCreate('building_manager', 'web'));

        $this->actingAs($bql);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $component = Livewire::test(CommunicationWizard::class)->assertOk();

        $draftId = $component->get('recordId');
        $this->assertNotNull($draftId);
        $this->assertDatabaseHas('notifications', ['id' => $draftId, 'workflow_status' => 'draft']);

        $component->fillForm([
            'content_type' => 'announcement',
            'title' => 'Bảo trì thang máy tòa W',
            'summary' => 'Thông báo bảo trì',
            'priority' => 'normal',
            'audience_scope' => 'building',
            'audience_target' => $b->id,
            'channels' => ['app', 'push'],
            'send_now' => true,
        ])->call('submitForApproval');

        $n = Notification::find($draftId);
        $this->assertSame(WS::PendingApproval, $n->workflow_status);
        $this->assertSame(1, (int) $n->recipient_count, 'đã resolve người nhận');
        $this->assertDatabaseHas('notification_approvals', ['notification_id' => $draftId, 'status' => 'requested']);
        $this->assertDatabaseHas('notification_snapshots', ['notification_id' => $draftId, 'version' => 1]);
        $this->assertDatabaseHas('notification_recipients', ['notification_id' => $draftId, 'resident_id' => $res->id]);
    }
}
