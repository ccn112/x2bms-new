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
 * A4 — tách nguồn feed: màn "Thông báo BQL" (?feed=bql) chỉ lấy thông báo CHÍNH
 * THỐNG (source=bql); Hộp thư hợp nhất (chuông, không feed) lấy cả item tương tác
 * (source=interaction). summary.unread_bql đếm đúng phạm vi BQL.
 */
class NotificationFeedSeparationTest extends TestCase
{
    use RefreshDatabase;

    public function test_feed_bql_loai_item_tuong_tac(): void
    {
        [$ctx, $user] = $this->seedResident('FEED');
        $bql = $this->publish($ctx, 'billing', source: 'bql', title: 'Thông báo phí T7');
        $interaction = $this->publish($ctx, 'community', source: 'interaction', title: 'Có người bình luận bài của bạn');

        Sanctum::actingAs($user, ['resident']);

        // Chuông (không feed) → thấy CẢ hai.
        $inbox = collect($this->getJson('/api/v1/resident/notifications')->assertOk()->json('data'))
            ->pluck('id')->all();
        $this->assertContains((string) $bql->id, $inbox);
        $this->assertContains((string) $interaction->id, $inbox);

        // Màn BQL (?feed=bql) → CHỈ thông báo chính thống, KHÔNG item tương tác.
        $bqlFeed = collect($this->getJson('/api/v1/resident/notifications?feed=bql')->assertOk()->json('data'))
            ->pluck('id')->all();
        $this->assertContains((string) $bql->id, $bqlFeed);
        $this->assertNotContains((string) $interaction->id, $bqlFeed);
    }

    public function test_summary_unread_bql_dem_theo_source(): void
    {
        [$ctx, $user] = $this->seedResident('FEED2');
        $this->publish($ctx, 'billing', source: 'bql');
        $this->publish($ctx, 'maintenance', source: 'bql');
        $this->publish($ctx, 'community', source: 'interaction');

        Sanctum::actingAs($user, ['resident']);

        $summary = $this->getJson('/api/v1/resident/notifications/summary')->assertOk()->json('data');
        $this->assertSame(3, $summary['unread_total'], 'chuông đếm tất cả');
        $this->assertSame(2, $summary['unread_bql'], 'BQL đếm 2 thông báo chính thống (Phí + Bảo trì), bỏ item tương tác');
    }

    /** @return array{0:array,1:User} */
    private function seedResident(string $suffix): array
    {
        $tenant = Tenant::create(['code' => "TEN-{$suffix}", 'name' => 'T']);
        $project = Project::create(['tenant_id' => $tenant->id, 'code' => "PRJ-{$suffix}", 'name' => 'P']);
        $building = Building::create(['tenant_id' => $tenant->id, 'project_id' => $project->id, 'code' => "BLD-{$suffix}", 'name' => 'B']);
        $apartment = Apartment::create(['tenant_id' => $tenant->id, 'building_id' => $building->id, 'code' => "APT-{$suffix}"]);
        $user = User::create(['name' => 'CD', 'email' => strtolower($suffix).'@test.vn', 'password' => bcrypt('x'), 'account_type' => 'resident']);
        $resident = Resident::create(['tenant_id' => $tenant->id, 'building_id' => $building->id, 'user_id' => $user->id, 'code' => "RES-{$suffix}", 'full_name' => 'CD']);
        ResidentApartmentRelation::create(['tenant_id' => $tenant->id, 'resident_id' => $resident->id, 'apartment_id' => $apartment->id, 'role' => 'owner', 'is_primary' => true]);

        return [compact('tenant', 'project', 'building', 'apartment'), $user];
    }

    private function publish(array $ctx, string $type, string $source, string $title = 'TB'): Notification
    {
        $n = Notification::create([
            'tenant_id' => $ctx['tenant']->id, 'project_id' => $ctx['project']->id, 'owner_level' => 'project',
            'source' => $source, 'type' => $type, 'category' => $type, 'title' => $title,
            'status' => 'published', 'published_at' => now(),
        ]);
        NotificationAudience::create(['notification_id' => $n->id, 'scope_type' => 'apartment', 'scope_id' => $ctx['apartment']->id]);

        return $n;
    }
}
