<?php

namespace Tests\Feature;

use App\Models\Amenity;
use App\Models\AmenitySlot;
use App\Models\Project;
use App\Models\Tenant;
use Database\Seeders\AmenitySlotsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AmenitySlotsSeederTest extends TestCase
{
    use RefreshDatabase;

    private function amenity(string $tag): Amenity
    {
        $tenant = Tenant::create(['code' => "TEN-$tag", 'name' => "T $tag"]);
        $project = Project::create(['tenant_id' => $tenant->id, 'code' => "PRJ-$tag", 'name' => "P $tag"]);

        return Amenity::create([
            'tenant_id' => $tenant->id, 'project_id' => $project->id,
            'code' => "GYM-$tag", 'name' => 'Phòng Gym',
        ]);
    }

    public function test_them_slot_cho_tien_ich_chua_co(): void
    {
        $a = $this->amenity('S1');

        (new AmenitySlotsSeeder())->run();

        $this->assertSame(6, AmenitySlot::where('amenity_id', $a->id)->count());
    }

    public function test_idempotent_khong_them_trung(): void
    {
        $a = $this->amenity('S2');

        (new AmenitySlotsSeeder())->run();
        (new AmenitySlotsSeeder())->run();

        $this->assertSame(6, AmenitySlot::where('amenity_id', $a->id)->count());
    }
}
