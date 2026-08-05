<?php

namespace Tests\Feature;

use App\Models\Apartment;
use App\Models\Building;
use App\Models\Project;
use App\Models\Resident;
use App\Models\ResidentApartmentRelation;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Cascade xóa mềm + khôi phục (ApartmentObserver/ResidentObserver): xóa mềm căn hộ/cư dân
 * → quan hệ cư dân↔căn hộ xóa mềm theo; khôi phục → quan hệ (xóa cùng lúc) khôi phục theo.
 */
class SoftDeleteCascadeTest extends TestCase
{
    use RefreshDatabase;

    public function test_xoa_mem_can_ho_cascade_quan_he_va_khoi_phuc(): void
    {
        [$apt, $rel] = $this->scenario('CA');

        $apt->delete();
        $this->assertSoftDeleted('apartments', ['id' => $apt->id]);
        $this->assertSoftDeleted('resident_apartment_relations', ['id' => $rel->id]);

        $apt->restore();
        $this->assertNull($apt->fresh()->deleted_at);
        $this->assertDatabaseHas('resident_apartment_relations', ['id' => $rel->id, 'deleted_at' => null]);
    }

    public function test_xoa_mem_cu_dan_cascade_quan_he(): void
    {
        [, $rel, $resident] = $this->scenario('CB');

        $resident->delete();
        $this->assertSoftDeleted('residents', ['id' => $resident->id]);
        $this->assertSoftDeleted('resident_apartment_relations', ['id' => $rel->id]);

        $resident->restore();
        $this->assertDatabaseHas('resident_apartment_relations', ['id' => $rel->id, 'deleted_at' => null]);
    }

    /** @return array{0:Apartment,1:ResidentApartmentRelation,2:Resident} */
    private function scenario(string $tag): array
    {
        $t = Tenant::create(['code' => "TEN-$tag", 'name' => "T $tag"]);
        $p = Project::create(['tenant_id' => $t->id, 'code' => "PRJ-$tag", 'name' => "P $tag"]);
        $b = Building::create(['tenant_id' => $t->id, 'project_id' => $p->id, 'code' => "BLD-$tag", 'name' => "B $tag"]);
        $apt = Apartment::create(['tenant_id' => $t->id, 'building_id' => $b->id, 'code' => "APT-$tag"]);
        $u = User::create(['name' => "CD $tag", 'email' => strtolower($tag).'@sd.vn', 'password' => bcrypt('x'), 'account_type' => 'resident']);
        $r = Resident::create(['tenant_id' => $t->id, 'building_id' => $b->id, 'user_id' => $u->id, 'code' => "RES-$tag", 'full_name' => "CD $tag"]);
        $rel = ResidentApartmentRelation::create([
            'tenant_id' => $t->id, 'resident_id' => $r->id, 'apartment_id' => $apt->id, 'role' => 'owner', 'is_primary' => true,
        ]);

        return [$apt, $rel, $r];
    }
}
