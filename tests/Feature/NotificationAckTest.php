<?php

namespace Tests\Feature;

use App\Models\Apartment;
use App\Models\Building;
use App\Models\Notification;
use App\Models\NotificationAudience;
use App\Models\NotificationRead;
use App\Models\Project;
use App\Models\Resident;
use App\Models\ResidentApartmentRelation;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * A3 — cư dân xác nhận đã tiếp nhận thông báo khẩn (`requires_ack`).
 *
 * Bốn mệnh đề:
 *  - Thông báo requires_ack → POST .../ack đánh dấu acknowledged (và đã đọc).
 *  - Bấm lại KHÔNG dời mốc ack (idempotent).
 *  - Thông báo KHÔNG cần ack → 422 ack_not_required (app không lạm dụng).
 *  - Thông báo của căn KHÁC → 404 (isolation, không tiết lộ tồn tại).
 */
class NotificationAckTest extends TestCase
{
    use RefreshDatabase;

    public function test_ack_thong_bao_khan_va_idempotent(): void
    {
        [$ctx, $user] = $this->seedResident('ACK1');
        $n = $this->publish($ctx, requiresAck: true);

        Sanctum::actingAs($user, ['resident']);

        $this->postJson("/api/v1/resident/notifications/{$n->id}/ack")
            ->assertOk()->assertJsonPath('data.acknowledged', true)
            ->assertJsonPath('data.is_read', true);

        $read = NotificationRead::where('notification_id', $n->id)->where('user_id', $user->id)->first();
        $this->assertNotNull($read->acknowledged_at);
        $firstAck = $read->acknowledged_at;

        // Bấm lại → vẫn ok, mốc ack GIỮ NGUYÊN.
        $this->postJson("/api/v1/resident/notifications/{$n->id}/ack")->assertOk();
        $this->assertEquals($firstAck, $read->fresh()->acknowledged_at);

        // Chi tiết phản ánh acknowledged=true.
        $this->getJson("/api/v1/resident/notifications/{$n->id}")
            ->assertOk()->assertJsonPath('data.acknowledged', true);
    }

    public function test_thong_bao_khong_can_ack_tra_422(): void
    {
        [$ctx, $user] = $this->seedResident('ACK2');
        $n = $this->publish($ctx, requiresAck: false);

        Sanctum::actingAs($user, ['resident']);

        $this->postJson("/api/v1/resident/notifications/{$n->id}/ack")
            ->assertStatus(422)->assertJsonPath('error.code', 'ack_not_required');
        $this->assertSame(0, NotificationRead::where('notification_id', $n->id)->count());
    }

    public function test_khong_ack_duoc_thong_bao_cua_can_khac(): void
    {
        [, $user] = $this->seedResident('ACK3A');
        [$otherCtx] = $this->seedResident('ACK3B');
        $other = $this->publish($otherCtx, requiresAck: true); // của căn khác

        Sanctum::actingAs($user, ['resident']);

        $this->postJson("/api/v1/resident/notifications/{$other->id}/ack")
            ->assertStatus(404);
        $this->assertSame(0, NotificationRead::where('notification_id', $other->id)->count());
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

    private function publish(array $ctx, bool $requiresAck): Notification
    {
        $n = Notification::create([
            'tenant_id' => $ctx['tenant']->id, 'project_id' => $ctx['project']->id, 'owner_level' => 'project',
            'type' => 'emergency', 'category' => 'emergency', 'title' => 'Khẩn cấp', 'summary' => 'PCCC',
            'status' => 'published', 'published_at' => now(), 'requires_ack' => $requiresAck,
        ]);
        NotificationAudience::create(['notification_id' => $n->id, 'scope_type' => 'apartment', 'scope_id' => $ctx['apartment']->id]);

        return $n;
    }
}
