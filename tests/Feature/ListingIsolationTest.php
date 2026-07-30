<?php

namespace Tests\Feature;

use App\Models\Apartment;
use App\Models\Building;
use App\Models\Project;
use App\Models\RealEstateListing;
use App\Models\Resident;
use App\Models\ResidentApartmentRelation;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Tin rao BĐS — cách ly theo dự án (CHỐT BẮT BUỘC, xem yêu cầu 2026-07-30 #trọn
 * vẹn listings): tin của dự án A không được lọt sang người xem chỉ có căn ở dự
 * án B, kể cả khi tin đã duyệt. Cũng khoá lại quyền rao (chủ căn vs người
 * không liên quan) và tính nguyên tử của interest_count.
 */
class ListingIsolationTest extends TestCase
{
    use RefreshDatabase;

    /** Dựng một (tenant, project, building, apartment, resident chủ căn, user). */
    private function makeOwner(string $tag): array
    {
        $tenant = Tenant::create(['code' => "TEN-$tag", 'name' => "Tenant $tag"]);
        $project = Project::create(['tenant_id' => $tenant->id, 'code' => "PRJ-$tag", 'name' => "Project $tag"]);
        $building = Building::create([
            'tenant_id' => $tenant->id, 'project_id' => $project->id,
            'code' => "BLD-$tag", 'name' => "Building $tag",
        ]);
        $apartment = Apartment::create([
            'tenant_id' => $tenant->id, 'building_id' => $building->id, 'code' => "APT-$tag",
        ]);
        $user = User::create([
            'name' => "User $tag", 'email' => strtolower($tag).'@test.vn', 'password' => bcrypt('secret'),
            'account_type' => 'resident',
        ]);
        $resident = Resident::create([
            'tenant_id' => $tenant->id, 'building_id' => $building->id, 'user_id' => $user->id,
            'code' => "RES-$tag", 'full_name' => "Resident $tag",
        ]);
        ResidentApartmentRelation::create([
            'tenant_id' => $tenant->id, 'resident_id' => $resident->id, 'apartment_id' => $apartment->id,
            'role' => 'owner', 'is_primary' => true,
        ]);

        return compact('tenant', 'project', 'building', 'apartment', 'user', 'resident');
    }

    private function approvedListing(array $owner, string $code): RealEstateListing
    {
        return RealEstateListing::create([
            'tenant_id' => $owner['tenant']->id,
            'project_id' => $owner['project']->id,
            'apartment_id' => $owner['apartment']->id,
            'owner_resident_id' => $owner['resident']->id,
            'created_by_user_id' => $owner['user']->id,
            'code' => $code, 'type' => 'sale', 'title' => 'Tin test', 'price' => 1_000_000_000,
            'status' => 'active', 'approval_status' => 'approved', 'approved_at' => now(), 'published_at' => now(),
        ]);
    }

    public function test_cross_project_interest_is_forbidden(): void
    {
        $a = $this->makeOwner('A');
        $b = $this->makeOwner('B');
        $listingB = $this->approvedListing($b, 'RE-B-001');

        Sanctum::actingAs($a['user'], ['resident']);

        // Người chỉ có căn ở dự án A không được tương tác tin của dự án B —
        // kể cả khi tin đã duyệt và đang active. Đây là chốt bắt buộc.
        $this->postJson("/api/v1/resident/listings/{$listingB->id}/interest")
            ->assertStatus(403)
            ->assertJsonPath('error.code', 'forbidden');
    }

    public function test_pending_listing_is_hidden_from_other_viewers(): void
    {
        $a = $this->makeOwner('A');
        $seller = $this->makeOwner('C');
        $pending = RealEstateListing::create([
            'tenant_id' => $a['tenant']->id, 'project_id' => $a['project']->id,
            'apartment_id' => $seller['apartment']->id, 'owner_resident_id' => $seller['resident']->id,
            'created_by_user_id' => $seller['user']->id,
            'code' => 'RE-A-PENDING', 'type' => 'sale', 'title' => 'Chờ duyệt', 'price' => 500_000_000,
            'status' => 'active', 'approval_status' => 'pending',
        ]);
        // Same project as $a so the isolation check passes; only approval hides it.
        $pending->project_id = $a['project']->id;
        $pending->save();

        Sanctum::actingAs($a['user'], ['resident']);

        // Cùng dự án nhưng CHƯA duyệt → 404 (không lộ tin tồn tại), không phải 403.
        $this->postJson("/api/v1/resident/listings/{$pending->id}/interest")
            ->assertStatus(404);
    }

    public function test_tenant_without_grant_cannot_post_listing(): void
    {
        $owner = $this->makeOwner('D');
        // Một cư dân KHÁC (role=member) ở CÙNG căn, chưa được BQL cấp quyền rao.
        $tenantUser = User::create([
            'name' => 'Tenant D2', 'email' => 'd2@test.vn', 'password' => bcrypt('secret'), 'account_type' => 'resident',
        ]);
        $tenantResident = Resident::create([
            'tenant_id' => $owner['tenant']->id, 'building_id' => $owner['building']->id, 'user_id' => $tenantUser->id,
            'code' => 'RES-D2', 'full_name' => 'Tenant D2',
        ]);
        ResidentApartmentRelation::create([
            'tenant_id' => $owner['tenant']->id, 'resident_id' => $tenantResident->id,
            'apartment_id' => $owner['apartment']->id, 'role' => 'tenant', 'is_primary' => true,
        ]);

        Sanctum::actingAs($tenantUser, ['resident']);

        $this->postJson('/api/v1/resident/listings', [
            'apartment_id' => $owner['apartment']->id, 'type' => 'sale', 'title' => 'Rao chui', 'price' => 100,
        ])->assertStatus(403)->assertJsonPath('error.code', 'not_authorized_to_post');
    }

    public function test_interest_toggle_is_idempotent_and_floors_at_zero(): void
    {
        $seller = $this->makeOwner('E');
        $buyer = $this->makeOwner('F');
        $listing = $this->approvedListing($seller, 'RE-E-001');
        // Cùng dự án để cách ly không chặn — gán buyer vào dự án của seller.
        Apartment::where('id', $buyer['apartment']->id)->update(['building_id' => $seller['building']->id]);

        Sanctum::actingAs($buyer['user'], ['resident']);

        $this->postJson("/api/v1/resident/listings/{$listing->id}/interest")->assertOk();
        $this->postJson("/api/v1/resident/listings/{$listing->id}/interest")->assertOk();
        $this->assertSame(1, $listing->fresh()->interest_count, 'Bấm hai lần không được cộng hai lần.');

        $this->deleteJson("/api/v1/resident/listings/{$listing->id}/interest")->assertOk();
        $this->deleteJson("/api/v1/resident/listings/{$listing->id}/interest")->assertOk();
        $this->assertSame(0, $listing->fresh()->interest_count, 'Bỏ quan tâm hai lần không được âm.');
    }
}
