<?php

namespace Tests\Feature;

use App\Models\Apartment;
use App\Models\Building;
use App\Models\Notification;
use App\Models\NotificationAudience;
use App\Models\NotificationChannel;
use App\Models\NotificationDeliveryLog;
use App\Models\Project;
use App\Models\Resident;
use App\Models\ResidentApartmentRelation;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Notifications\NotificationExternalChannelDispatcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * ADR-002 — khi phát hành thông báo BQL có kênh ngoài (email/…), tự gửi cho audience
 * TARGETED (căn hộ) và ghi sổ gửi. Broadcast (all/project/building) KHÔNG auto-gửi.
 */
class NotificationExternalChannelDispatcherTest extends TestCase
{
    use RefreshDatabase;

    public function test_email_gui_that_cho_audience_can_ho(): void
    {
        Mail::fake();
        [$tenant, $building, $apartment, $user] = $this->seedResident('EXT1');

        $n = Notification::create([
            'title' => 'Khử khuẩn hành lang', 'summary' => 'Đóng cửa sổ hướng hành lang.',
            'status' => 'published', 'owner_level' => 'project',
            'tenant_id' => $tenant->id, 'project_id' => $building->project_id, 'published_at' => now(),
        ]);
        NotificationAudience::create(['notification_id' => $n->id, 'scope_type' => 'apartment', 'scope_id' => $apartment->id]);
        NotificationChannel::create(['notification_id' => $n->id, 'channel' => 'email']);
        NotificationChannel::create(['notification_id' => $n->id, 'channel' => 'app']);

        $processed = app(NotificationExternalChannelDispatcher::class)->dispatch($n);

        $this->assertSame(1, $processed, '1 người × 1 kênh ngoài (email); app không tính');
        $row = NotificationDeliveryLog::where('user_id', $user->id)->where('channel', 'email')->first();
        $this->assertNotNull($row);
        $this->assertSame('sent', $row->status, 'email gửi thật (Mail::raw không throw)');
        $this->assertSame(0.0, (float) $row->cost);
    }

    public function test_broadcast_toa_khong_auto_gui_kenh_ngoai(): void
    {
        Mail::fake();
        [$tenant, $building, , ] = $this->seedResident('EXT2');

        $n = Notification::create([
            'title' => 'Thông báo toàn tòa', 'status' => 'published', 'owner_level' => 'project',
            'tenant_id' => $tenant->id, 'project_id' => $building->project_id, 'published_at' => now(),
        ]);
        NotificationAudience::create(['notification_id' => $n->id, 'scope_type' => 'building', 'scope_id' => $building->id]);
        NotificationChannel::create(['notification_id' => $n->id, 'channel' => 'email']);

        $processed = app(NotificationExternalChannelDispatcher::class)->dispatch($n);

        $this->assertSame(0, $processed, 'broadcast tòa → không auto-gửi email (tránh phí per-người)');
        Mail::assertNothingSent();
    }

    /** @return array{0:Tenant,1:Building,2:Apartment,3:User} */
    private function seedResident(string $suffix): array
    {
        $tenant = Tenant::create(['code' => "TEN-{$suffix}", 'name' => 'T']);
        $project = Project::create(['tenant_id' => $tenant->id, 'code' => "PRJ-{$suffix}", 'name' => 'P']);
        $building = Building::create(['tenant_id' => $tenant->id, 'project_id' => $project->id, 'code' => "BLD-{$suffix}", 'name' => 'B']);
        $apartment = Apartment::create(['tenant_id' => $tenant->id, 'building_id' => $building->id, 'code' => "APT-{$suffix}"]);
        $user = User::create(['name' => 'CD', 'email' => strtolower($suffix).'@test.vn', 'password' => bcrypt('x'), 'account_type' => 'resident']);
        $resident = Resident::create([
            'tenant_id' => $tenant->id, 'building_id' => $building->id, 'user_id' => $user->id,
            'code' => "RES-{$suffix}", 'full_name' => 'CD',
        ]);
        ResidentApartmentRelation::create([
            'tenant_id' => $tenant->id, 'resident_id' => $resident->id, 'apartment_id' => $apartment->id,
            'role' => 'owner', 'is_primary' => true,
        ]);

        return [$tenant, $building, $apartment, $user];
    }
}
