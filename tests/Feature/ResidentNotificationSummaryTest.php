<?php

namespace Tests\Feature;

use App\Models\Apartment;
use App\Models\Building;
use App\Models\Notification;
use App\Models\NotificationAudience;
use App\Models\Project;
use App\Models\Resident;
use App\Models\ResidentApartmentRelation;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * N-inbox: hộp thư hợp nhất — GET notifications/summary (unread + breakdown nhóm),
 * POST notifications/read-all, lọc index theo ?category / ?unread.
 */
class ResidentNotificationSummaryTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Project $project;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tenant = Tenant::create(['code' => 'TEN-N', 'name' => 'T N']);
        $this->project = Project::create(['tenant_id' => $this->tenant->id, 'code' => 'PRJ-N', 'name' => 'P N']);
        $building = Building::create(['tenant_id' => $this->tenant->id, 'project_id' => $this->project->id, 'code' => 'BLD-N', 'name' => 'B N']);
        $apartment = Apartment::create(['tenant_id' => $this->tenant->id, 'building_id' => $building->id, 'code' => 'APT-N']);
        $this->user = User::create([
            'name' => 'CD N', 'email' => 'cd-n@test.vn',
            'password' => bcrypt('secret'), 'account_type' => 'resident',
        ]);
        $resident = Resident::create([
            'tenant_id' => $this->tenant->id, 'building_id' => $building->id, 'user_id' => $this->user->id,
            'code' => 'RES-N', 'full_name' => 'CD N',
        ]);
        ResidentApartmentRelation::create([
            'tenant_id' => $this->tenant->id, 'resident_id' => $resident->id, 'apartment_id' => $apartment->id,
            'role' => 'owner', 'is_primary' => true,
        ]);
    }

    private function publish(string $category, string $title): Notification
    {
        $n = Notification::create([
            'tenant_id' => $this->tenant->id, 'owner_level' => 'project', 'project_id' => $this->project->id,
            'type' => $category, 'category' => $category, 'title' => $title,
            'status' => 'published', 'published_at' => now(),
        ]);
        NotificationAudience::create(['notification_id' => $n->id, 'scope_type' => 'all']);

        return $n;
    }

    public function test_summary_tra_unread_tong_va_breakdown_theo_nhom(): void
    {
        $this->publish('billing', 'Phí tháng 5');
        $this->publish('billing', 'Phí tháng 6');
        $this->publish('maintenance', 'Cắt nước');
        Sanctum::actingAs($this->user, ['resident']);

        $res = $this->getJson('/api/v1/resident/notifications/summary')->assertOk();
        $res->assertJsonPath('data.unread_total', 3);
        $res->assertJsonPath('data.unread_by_category.billing', 2);
        $res->assertJsonPath('data.unread_by_category.maintenance', 1);
    }

    public function test_read_all_danh_dau_het_va_unread_ve_0(): void
    {
        $this->publish('billing', 'A');
        $this->publish('community', 'B');
        Sanctum::actingAs($this->user, ['resident']);

        $this->postJson('/api/v1/resident/notifications/read-all')
            ->assertOk()
            ->assertJsonPath('data.marked', 2)
            ->assertJsonPath('data.unread_notification_count', 0);

        $this->getJson('/api/v1/resident/notifications/summary')
            ->assertJsonPath('data.unread_total', 0);
    }

    public function test_index_loc_theo_category(): void
    {
        $this->publish('billing', 'Hoá đơn');
        $this->publish('maintenance', 'Bảo trì');
        Sanctum::actingAs($this->user, ['resident']);

        $res = $this->getJson('/api/v1/resident/notifications?category=billing')->assertOk();
        $data = $res->json('data');
        $this->assertCount(1, $data);
        $this->assertSame('billing', $data[0]['category']);
    }

    public function test_read_all_theo_category_chi_danh_dau_nhom_do(): void
    {
        $this->publish('billing', 'A');
        $this->publish('maintenance', 'B');
        Sanctum::actingAs($this->user, ['resident']);

        $this->postJson('/api/v1/resident/notifications/read-all?category=billing')
            ->assertOk()
            ->assertJsonPath('data.marked', 1);

        $this->getJson('/api/v1/resident/notifications/summary')
            ->assertJsonPath('data.unread_total', 1)
            ->assertJsonPath('data.unread_by_category.maintenance', 1);
    }
}
