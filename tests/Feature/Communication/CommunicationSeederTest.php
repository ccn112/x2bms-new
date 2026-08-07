<?php

namespace Tests\Feature\Communication;

use App\Models\Apartment;
use App\Models\Building;
use App\Models\Notification;
use App\Models\Poll;
use App\Models\Project;
use App\Models\Resident;
use App\Models\ResidentApartmentRelation;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\CommunicationDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** T6 — smoke test seeder demo: counts đúng, idempotent, poll aggregate khớp, recipients resolve. */
class CommunicationSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seed_dung_so_luong_idempotent_va_poll_khop(): void
    {
        config(['x2.demo_seed_enabled' => true]);
        $this->minimalDemo();

        (new CommunicationDemoSeeder())->setContainer($this->app)->run();

        $this->assertSame(12, Notification::where('content_type', 'announcement')->count());
        $this->assertSame(8, Notification::where('content_type', 'news')->count());
        $this->assertSame(6, Notification::where('content_type', 'event')->count());
        $this->assertSame(6, Notification::where('content_type', 'poll')->count());
        $this->assertDatabaseCount('notification_audience_groups', 11);

        // Poll aggregate: tổng vote_count option == poll.vote_count.
        $poll = Poll::where('question', 'like', '%phòng gym%')->first();
        $this->assertNotNull($poll);
        $this->assertSame((int) $poll->vote_count, (int) $poll->options()->sum('vote_count'));

        // Campaign đã phát hành → có người nhận resolve.
        $published = Notification::where('status', 'published')->where('recipient_count', '>', 0)->count();
        $this->assertGreaterThan(0, $published);

        // Idempotent: chạy lại không nhân đôi.
        (new CommunicationDemoSeeder())->setContainer($this->app)->run();
        $this->assertSame(12, Notification::where('content_type', 'announcement')->count());
        $this->assertSame(32, Notification::whereIn('content_type', ['announcement', 'news', 'event', 'poll'])->count());
    }

    private function minimalDemo(): void
    {
        $t = Tenant::create(['code' => 'T-X2-DEMO', 'name' => 'X2 Demo']);
        $p = Project::create(['tenant_id' => $t->id, 'code' => 'SUNSHINE-GARDEN', 'name' => 'Sunshine Garden']);
        $b1 = Building::create(['tenant_id' => $t->id, 'project_id' => $p->id, 'code' => 'SG-A', 'name' => 'Tòa A']);
        $b2 = Building::create(['tenant_id' => $t->id, 'project_id' => $p->id, 'code' => 'SG-B', 'name' => 'Tòa B']);

        foreach (range(1, 8) as $i) {
            $b = $i % 2 ? $b1 : $b2;
            $u = User::create(['name' => "CD $i", 'email' => "cd$i@demo.vn", 'password' => bcrypt('x'), 'account_type' => 'resident', 'tenant_id' => $t->id]);
            $res = Resident::create(['tenant_id' => $t->id, 'building_id' => $b->id, 'user_id' => $u->id, 'code' => "CD-000$i", 'full_name' => "Cư dân $i", 'phone' => '09000000'.$i, 'email' => "cd$i@demo.vn", 'status' => 'active']);
            $ap = Apartment::create(['tenant_id' => $t->id, 'building_id' => $b->id, 'code' => "A-010$i", 'status' => 'occupied']);
            ResidentApartmentRelation::create(['tenant_id' => $t->id, 'resident_id' => $res->id, 'apartment_id' => $ap->id, 'role' => $i % 3 ? 'owner' : 'tenant']);
        }
    }
}
