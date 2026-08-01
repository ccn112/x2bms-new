<?php

namespace Tests\Feature;

use App\Models\Amenity;
use App\Models\AmenityBooking;
use App\Models\AmenitySlot;
use App\Models\Apartment;
use App\Models\Building;
use App\Models\Project;
use App\Models\Resident;
use App\Models\ResidentApartmentRelation;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Slot theo ngày (UX review 2026-08-01 ý 4): khung giờ đã đặt của một tiện ích
 * theo NGÀY chọn phải hiện số đã giữ chỗ / còn trống để báo bận khi hết slot.
 */
class AmenityAvailabilityTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<string,mixed> */
    private function scene(string $tag): array
    {
        $tenant = Tenant::create(['code' => "TEN-$tag", 'name' => "T $tag"]);
        $project = Project::create(['tenant_id' => $tenant->id, 'code' => "PRJ-$tag", 'name' => "P $tag"]);
        $building = Building::create([
            'tenant_id' => $tenant->id, 'project_id' => $project->id, 'code' => "BLD-$tag", 'name' => "B $tag",
        ]);
        $apartment = Apartment::create([
            'tenant_id' => $tenant->id, 'building_id' => $building->id, 'code' => "APT-$tag",
        ]);
        $user = User::create([
            'name' => "U $tag", 'email' => strtolower($tag).'-av@test.vn',
            'password' => bcrypt('secret'), 'account_type' => 'resident',
        ]);
        $resident = Resident::create([
            'tenant_id' => $tenant->id, 'building_id' => $building->id, 'user_id' => $user->id,
            'code' => "RES-$tag", 'full_name' => "R $tag",
        ]);
        ResidentApartmentRelation::create([
            'tenant_id' => $tenant->id, 'resident_id' => $resident->id, 'apartment_id' => $apartment->id,
            'role' => 'owner', 'is_primary' => true,
        ]);
        $amenity = Amenity::create([
            'tenant_id' => $tenant->id, 'project_id' => $project->id,
            'code' => "GYM-$tag", 'name' => 'Phòng Gym',
        ]);
        $slot = AmenitySlot::create([
            'amenity_id' => $amenity->id, 'day_of_week' => null,
            'start_time' => '09:00', 'end_time' => '10:00', 'capacity' => 2, 'status' => 'open',
        ]);

        return compact('user', 'tenant', 'amenity', 'slot');
    }

    private function booking(array $s, string $date, int $party, string $status): AmenityBooking
    {
        return AmenityBooking::create([
            'tenant_id' => $s['tenant']->id, 'amenity_id' => $s['amenity']->id,
            'amenity_slot_id' => $s['slot']->id, 'booking_date' => $date,
            'start_time' => '09:00', 'end_time' => '10:00', 'party_size' => $party, 'status' => $status,
        ]);
    }

    public function test_slot_day_full_khi_du_capacity(): void
    {
        $s = $this->scene('AV1');
        $this->booking($s, '2026-08-05', 2, 'confirmed'); // đủ capacity 2
        Sanctum::actingAs($s['user'], ['resident']);

        $res = $this->getJson("/api/v1/resident/amenities/{$s['amenity']->id}/availability?date=2026-08-05")
            ->assertOk();

        $slot = $res->json('data.slots.0');
        $this->assertSame(2, $slot['booked']);
        $this->assertSame(0, $slot['remaining']);
        $this->assertTrue($slot['is_full']);
    }

    public function test_ngay_khac_khong_tinh_booking(): void
    {
        $s = $this->scene('AV2');
        $this->booking($s, '2026-08-05', 2, 'confirmed');
        Sanctum::actingAs($s['user'], ['resident']);

        // Ngày khác → slot còn trống hoàn toàn.
        $res = $this->getJson("/api/v1/resident/amenities/{$s['amenity']->id}/availability?date=2026-08-06")
            ->assertOk();
        $slot = $res->json('data.slots.0');
        $this->assertSame(0, $slot['booked']);
        $this->assertSame(2, $slot['remaining']);
        $this->assertFalse($slot['is_full']);
    }

    public function test_booking_da_huy_khong_giu_cho(): void
    {
        $s = $this->scene('AV3');
        $this->booking($s, '2026-08-05', 2, 'cancelled'); // huỷ → không tính
        Sanctum::actingAs($s['user'], ['resident']);

        $res = $this->getJson("/api/v1/resident/amenities/{$s['amenity']->id}/availability?date=2026-08-05")
            ->assertOk();
        $this->assertSame(0, $res->json('data.slots.0.booked'));
    }

    public function test_tien_ich_du_an_khac_thi_404(): void
    {
        $me = $this->scene('AV4');
        $other = $this->scene('AV5');
        Sanctum::actingAs($me['user'], ['resident']);

        $this->getJson("/api/v1/resident/amenities/{$other['amenity']->id}/availability?date=2026-08-05")
            ->assertNotFound();
    }
}
