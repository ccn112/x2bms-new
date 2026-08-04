<?php

namespace Tests\Feature;

use App\Models\Apartment;
use App\Models\Building;
use App\Models\Poll;
use App\Models\PollOption;
use App\Models\Project;
use App\Models\Resident;
use App\Models\ResidentApartmentRelation;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Audit BOLA/IDOR — bình chọn khảo sát (`POST resident/community/polls/{poll}/vote`).
 * Cư dân CHỈ vote được poll thuộc DỰ ÁN của mình; poll dự án khác (có thể tenant khác)
 * → 404, không được ghi vote (chống bóp méo khảo sát nội bộ của bên khác).
 */
class CommunityPollScopeTest extends TestCase
{
    use RefreshDatabase;

    public function test_khong_vote_duoc_poll_du_an_khac(): void
    {
        $author = $this->resident('PA');
        [$pollB, $optB] = $this->poll('PB');   // poll của dự án/tenant khác

        Sanctum::actingAs($author, ['resident']);

        $this->postJson("/api/v1/resident/community/polls/{$pollB->id}/vote", ['option_id' => $optB->id])
            ->assertNotFound();

        $this->assertDatabaseCount('poll_votes', 0);
        $this->assertSame(0, (int) $pollB->fresh()->vote_count, 'vote_count poll bên kia KHÔNG đổi');
    }

    public function test_vote_duoc_poll_du_an_minh(): void
    {
        // Cư dân + poll cùng dự án.
        $t = Tenant::create(['code' => 'TEN-OK', 'name' => 'T']);
        $p = Project::create(['tenant_id' => $t->id, 'code' => 'PRJ-OK', 'name' => 'P']);
        $b = Building::create(['tenant_id' => $t->id, 'project_id' => $p->id, 'code' => 'BLD-OK', 'name' => 'B']);
        $author = $this->residentIn($t, $b, 'ok@test.vn');
        $poll = Poll::create(['tenant_id' => $t->id, 'project_id' => $p->id, 'question' => 'Q', 'type' => 'single', 'status' => 'open']);
        $opt = PollOption::create(['poll_id' => $poll->id, 'label' => 'A', 'sort' => 1]);

        Sanctum::actingAs($author, ['resident']);

        $this->postJson("/api/v1/resident/community/polls/{$poll->id}/vote", ['option_id' => $opt->id])
            ->assertOk();
        $this->assertDatabaseCount('poll_votes', 1);
    }

    private function resident(string $tag): User
    {
        $t = Tenant::create(['code' => "TEN-$tag", 'name' => "T $tag"]);
        $b = Building::create(['tenant_id' => $t->id, 'project_id' => Project::create(['tenant_id' => $t->id, 'code' => "PRJ-$tag", 'name' => "P $tag"])->id, 'code' => "BLD-$tag", 'name' => "B $tag"]);

        return $this->residentIn($t, $b, strtolower($tag).'@test.vn');
    }

    private function residentIn(Tenant $t, Building $b, string $email): User
    {
        $u = User::create(['name' => 'CD', 'email' => $email, 'password' => bcrypt('x'), 'account_type' => 'resident']);
        $apt = Apartment::create(['tenant_id' => $t->id, 'building_id' => $b->id, 'code' => 'APT-'.substr(md5($email), 0, 5)]);
        $r = Resident::create(['tenant_id' => $t->id, 'building_id' => $b->id, 'user_id' => $u->id, 'code' => 'RES-'.substr(md5($email), 0, 5), 'full_name' => 'CD']);
        ResidentApartmentRelation::create(['tenant_id' => $t->id, 'resident_id' => $r->id, 'apartment_id' => $apt->id, 'role' => 'owner', 'is_primary' => true]);

        return $u;
    }

    /** @return array{0:Poll,1:PollOption} */
    private function poll(string $tag): array
    {
        $t = Tenant::create(['code' => "TEN-$tag", 'name' => "T $tag"]);
        $p = Project::create(['tenant_id' => $t->id, 'code' => "PRJ-$tag", 'name' => "P $tag"]);
        $poll = Poll::create(['tenant_id' => $t->id, 'project_id' => $p->id, 'question' => 'Q', 'type' => 'single', 'status' => 'open']);
        $opt = PollOption::create(['poll_id' => $poll->id, 'label' => 'A', 'sort' => 1]);

        return [$poll, $opt];
    }
}
