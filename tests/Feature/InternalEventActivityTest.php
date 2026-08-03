<?php

namespace Tests\Feature;

use App\Models\ActivityNotification;
use App\Models\Apartment;
use App\Models\Building;
use App\Models\Payment;
use App\Models\Project;
use App\Models\Resident;
use App\Models\ResidentApartmentRelation;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Billing\ResidentPaymentClaimReviewer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * N1b — event NỘI BỘ sinh activity vào chuông.
 *  - BQL duyệt chứng từ thanh toán → cư dân có activity 'payment_confirmed'.
 *  - Duyệt lại (idempotent) KHÔNG đẻ activity thứ hai.
 */
class InternalEventActivityTest extends TestCase
{
    use RefreshDatabase;

    public function test_duyet_thanh_toan_sinh_activity_cho_cu_dan(): void
    {
        $tenant = Tenant::create(['code' => 'TEN-EV', 'name' => 'T']);
        $project = Project::create(['tenant_id' => $tenant->id, 'code' => 'PRJ-EV', 'name' => 'P']);
        $building = Building::create(['tenant_id' => $tenant->id, 'project_id' => $project->id, 'code' => 'BLD-EV', 'name' => 'B']);
        $apartment = Apartment::create(['tenant_id' => $tenant->id, 'building_id' => $building->id, 'code' => 'APT-EV']);
        $user = User::create(['name' => 'CD', 'email' => 'ev-res@test.vn', 'password' => bcrypt('x'), 'account_type' => 'resident']);
        $resident = Resident::create(['tenant_id' => $tenant->id, 'building_id' => $building->id, 'user_id' => $user->id, 'code' => 'RES-EV', 'full_name' => 'CD']);
        ResidentApartmentRelation::create(['tenant_id' => $tenant->id, 'resident_id' => $resident->id, 'apartment_id' => $apartment->id, 'role' => 'owner', 'is_primary' => true]);
        $staff = User::create(['name' => 'BQL', 'email' => 'ev-staff@test.vn', 'password' => bcrypt('x'), 'account_type' => 'staff', 'tenant_id' => $tenant->id, 'project_id' => $project->id, 'building_id' => $building->id]);

        $payment = Payment::create([
            'tenant_id' => $tenant->id, 'building_id' => $building->id, 'apartment_id' => $apartment->id,
            'resident_id' => $resident->id, 'code' => 'TT-EV-1', 'amount' => 500_000,
            'paid_at' => now()->subHour(), 'status' => Payment::STATUS_PENDING,
            'source' => Payment::SOURCE_RESIDENT_APP, 'submitted_by_id' => $user->id, 'submitted_at' => now(),
        ]);

        app(ResidentPaymentClaimReviewer::class)->approve($payment, $staff);

        $act = ActivityNotification::where('recipient_user_id', $user->id)->where('kind', 'payment_confirmed')->get();
        $this->assertCount(1, $act, 'cư dân có 1 activity xác nhận thanh toán');
        $this->assertSame('payment', $act->first()->entity_type, 'không claim statement → trỏ payment');

        // Duyệt lại → idempotent (status đã confirmed) → KHÔNG đẻ activity thứ hai.
        app(ResidentPaymentClaimReviewer::class)->approve($payment->fresh(), $staff);
        $this->assertSame(1, ActivityNotification::where('recipient_user_id', $user->id)->where('kind', 'payment_confirmed')->count());
    }
}
