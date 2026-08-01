<?php

namespace Tests\Feature;

use App\Models\Apartment;
use App\Models\Building;
use App\Models\Payment;
use App\Models\Project;
use App\Models\Resident;
use App\Models\ResidentApartmentRelation;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Bình luận phiếu: cư dân mở được bình luận của phiếu THUỘC MÌNH kể cả khi phiếu
 * gắn qua `resident_id` (apartment_id null/khác) — hay gặp với phiếu BQL tạo.
 *
 * Khoá lại bằng test vì đây là lỗi ĐÃ XẢY RA trên live (verify 2026-08-01):
 * `SlipCommentController::resolve()` trước chỉ lọc `apartment_id`, trong khi
 * `PaymentController`/`AmenityController` cho là "của tôi" theo `apartment_id`
 * HOẶC `resident_id`. Hệ quả: phiếu hiện trong danh sách nhưng mở bình luận 404.
 */
class SlipCommentOwnershipTest extends TestCase
{
    use RefreshDatabase;

    /** @return array{user:User,resident:Resident,apartment:Apartment,ctx:array} */
    private function makeResident(string $tag): array
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
            'name' => "User $tag", 'email' => strtolower($tag).'-slip@test.vn',
            'password' => bcrypt('secret'), 'account_type' => 'resident',
        ]);
        $resident = Resident::create([
            'tenant_id' => $tenant->id, 'building_id' => $building->id, 'user_id' => $user->id,
            'code' => "RES-$tag", 'full_name' => "Resident $tag",
        ]);
        ResidentApartmentRelation::create([
            'tenant_id' => $tenant->id, 'resident_id' => $resident->id, 'apartment_id' => $apartment->id,
            'role' => 'owner', 'is_primary' => true,
        ]);

        return [
            'user' => $user, 'resident' => $resident, 'apartment' => $apartment,
            'ctx' => compact('tenant', 'project', 'building'),
        ];
    }

    private function payment(array $r, ?int $apartmentId, ?int $residentId): Payment
    {
        return Payment::create([
            'tenant_id' => $r['ctx']['tenant']->id,
            'apartment_id' => $apartmentId,
            'resident_id' => $residentId,
            'code' => 'PM-'.$r['ctx']['tenant']->id.'-'.($apartmentId ?? 'x').'-'.($residentId ?? 'x'),
            'amount' => 5_000_000,
            'status' => 'confirmed',
            'paid_at' => now()->subDay(),
        ]);
    }

    public function test_phieu_gan_qua_resident_id_van_mo_duoc_binh_luan(): void
    {
        $r = $this->makeResident('S1');
        // Phiếu BQL tạo: apartment_id NULL, chỉ gắn resident_id.
        $payment = $this->payment($r, null, $r['resident']->id);

        Sanctum::actingAs($r['user'], ['resident']);

        $this->getJson("/api/v1/resident/payments/{$payment->id}/comments")
            ->assertOk();
    }

    public function test_phieu_gan_qua_apartment_id_van_mo_duoc_binh_luan(): void
    {
        $r = $this->makeResident('S2');
        $payment = $this->payment($r, $r['apartment']->id, null);

        Sanctum::actingAs($r['user'], ['resident']);

        $this->getJson("/api/v1/resident/payments/{$payment->id}/comments")
            ->assertOk();
    }

    public function test_phieu_cua_cu_dan_khac_thi_404(): void
    {
        $me = $this->makeResident('S3');
        $other = $this->makeResident('S4');
        // Phiếu thuộc cư dân khác — không apartment_id lẫn resident_id của tôi.
        $foreign = $this->payment($other, $other['apartment']->id, $other['resident']->id);

        Sanctum::actingAs($me['user'], ['resident']);

        $this->getJson("/api/v1/resident/payments/{$foreign->id}/comments")
            ->assertNotFound();
    }
}
