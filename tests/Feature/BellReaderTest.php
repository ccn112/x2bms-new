<?php

namespace Tests\Feature;

use App\Models\ActivityNotification;
use App\Models\Apartment;
use App\Models\Building;
use App\Models\Notification;
use App\Models\NotificationAudience;
use App\Models\Project;
use App\Models\Resident;
use App\Models\ResidentApartmentRelation;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Notifications\ActivityEmitter;
use App\Services\Notifications\BellReader;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * N0 — CHUÔNG hợp nhất (ADR-001). Bốn mệnh đề trụ cột:
 *  - Broadcast KHÔNG fan-out: 1 thông báo gửi-căn = 1 dòng `notifications`, 0 dòng
 *    `activity_notifications`, mà cư dân vẫn thấy ở chuông.
 *  - Isolation: activity của A KHÔNG lọt vào chuông của B.
 *  - Coalesce: 2 tương tác cùng group_key = 1 dòng, coalesce_count=2.
 *  - Unread theo mốc seen: broadcast + activity; markSeen đưa broadcast về 0.
 */
class BellReaderTest extends TestCase
{
    use RefreshDatabase;

    public function test_broadcast_khong_fanout_nhung_van_vao_chuong(): void
    {
        [$ctx, $user] = $this->seedResident('BELL1');
        $this->broadcastToApartment($ctx, 'Thông báo toàn nhà');

        // KHÔNG có dòng activity nào được sinh cho broadcast.
        $this->assertSame(0, ActivityNotification::count(), 'broadcast không fan-out per-recipient');
        $this->assertSame(1, Notification::count(), 'chỉ 1 dòng nội dung');

        Sanctum::actingAs($user, ['resident']);
        $data = $this->getJson('/api/v1/resident/bell')->assertOk()->json('data');
        $types = collect($data['items'])->pluck('type')->all();
        $this->assertContains('announcement', $types);
        $this->assertSame('Thông báo toàn nhà', collect($data['items'])->firstWhere('type', 'announcement')['title']);
    }

    public function test_activity_cua_a_khong_lot_sang_b(): void
    {
        [$ctxA, $userA] = $this->seedResident('BELLA');
        [, $userB] = $this->seedResident('BELLB');

        app(ActivityEmitter::class)->emit([
            'recipient_user_id' => $userA->id, 'tenant_id' => $ctxA['tenant']->id,
            'kind' => 'ticket_approved', 'title' => 'Phiếu của bạn đã được duyệt',
            'entity_type' => 'ticket', 'entity_id' => 99, 'action_key' => 'view_ticket',
        ]);

        Sanctum::actingAs($userB, ['resident']);
        $b = $this->getJson('/api/v1/resident/bell')->assertOk()->json('data');
        $this->assertCount(0, collect($b['items'])->where('type', 'activity'), 'B không thấy activity của A');

        Sanctum::actingAs($userA, ['resident']);
        $a = $this->getJson('/api/v1/resident/bell')->assertOk()->json('data');
        $this->assertCount(1, collect($a['items'])->where('type', 'activity'), 'A thấy activity của mình');
    }

    public function test_coalesce_tuong_tac_cung_group_key(): void
    {
        [$ctx, $user] = $this->seedResident('BELLC');
        $emitter = app(ActivityEmitter::class);
        $base = [
            'recipient_user_id' => $user->id, 'tenant_id' => $ctx['tenant']->id,
            'kind' => 'reaction', 'entity_type' => 'community_post', 'entity_id' => 7,
            'group_key' => 'post:7:reaction',
        ];
        $emitter->emit($base + ['title' => 'A đã thả cảm xúc bài của bạn']);
        $emitter->emit($base + ['title' => 'B và những người khác đã thả cảm xúc']);

        $this->assertSame(1, ActivityNotification::count(), 'gộp một dòng');
        $row = ActivityNotification::first();
        $this->assertSame(2, $row->coalesce_count);
        $this->assertNull($row->read_at, 'nổi lại thành chưa đọc');
    }

    public function test_unread_va_mark_seen(): void
    {
        [$ctx, $user] = $this->seedResident('BELLU');
        $this->broadcastToApartment($ctx, 'BC1');
        app(ActivityEmitter::class)->emit([
            'recipient_user_id' => $user->id, 'tenant_id' => $ctx['tenant']->id,
            'kind' => 'debt_reply', 'title' => 'Thắc mắc công nợ đã được trả lời',
        ]);

        $reader = app(BellReader::class);
        $this->assertSame(2, $reader->unreadCount($user, null), '1 broadcast + 1 activity');

        // Mở chuông → broadcast về 0, còn 1 activity chưa đọc.
        $reader->markSeen($user);
        $this->assertSame(1, $reader->unreadCount($user, null), 'broadcast đã seen, activity còn');

        Sanctum::actingAs($user, ['resident']);
        $act = ActivityNotification::where('recipient_user_id', $user->id)->first();
        $this->postJson("/api/v1/resident/bell/activities/{$act->id}/read")
            ->assertOk()->assertJsonPath('data.unread', 0);
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

    private function broadcastToApartment(array $ctx, string $title): Notification
    {
        $n = Notification::create([
            'tenant_id' => $ctx['tenant']->id, 'project_id' => $ctx['project']->id, 'owner_level' => 'project',
            'source' => 'bql', 'type' => 'announcement', 'category' => 'announcement',
            'title' => $title, 'status' => 'published', 'published_at' => now(),
        ]);
        NotificationAudience::create(['notification_id' => $n->id, 'scope_type' => 'apartment', 'scope_id' => $ctx['apartment']->id]);

        return $n;
    }
}
