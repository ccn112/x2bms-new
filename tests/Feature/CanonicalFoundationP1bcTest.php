<?php

namespace Tests\Feature;

use App\Models\Apartment;
use App\Models\BillingFamily;
use App\Models\Building;
use App\Models\LiabilityPeriod;
use App\Models\Project;
use App\Models\Resident;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * P1b/P1c — nền canonical: catalog `billing_families` + `liability_periods` + backfill.
 */
class CanonicalFoundationP1bcTest extends TestCase
{
    use RefreshDatabase;

    public function test_billing_families_seed_du_5_dong_dung_priority(): void
    {
        $this->assertSame(5, BillingFamily::count());

        $water = BillingFamily::where('code', 'water')->first();
        $this->assertNotNull($water);
        $this->assertSame(200, $water->priority);
        $this->assertSame('Nước', $water->name_vi);
        $this->assertTrue($water->system_locked);
        $this->assertTrue($water->requires_subject);
        $this->assertSame('meter', $water->subject_kind);

        // Thứ tự phân bổ: management(100) < water(200) < electricity(300) < vehicle(400) < other(900)
        $order = BillingFamily::orderBy('priority')->pluck('code')->all();
        $this->assertSame(['management', 'water', 'electricity', 'vehicle', 'other'], $order);
    }

    public function test_billing_families_code_unique_khong_trung(): void
    {
        // `code` unique → upsert/seed chạy lại không thể tạo dòng thứ 2 cùng mã.
        $this->expectException(\Illuminate\Database\UniqueConstraintViolationException::class);
        BillingFamily::create([
            'code' => 'water', 'name_vi' => 'X', 'name_en' => 'X', 'priority' => 200,
            'requires_subject' => true, 'system_locked' => true, 'status' => 'active',
        ]);
    }

    public function test_liability_backfill_tao_owner_mo_va_idempotent(): void
    {
        $tenant = Tenant::create(['code' => 'TEN-LIA', 'name' => 'Tenant Lia']);
        $project = Project::create(['tenant_id' => $tenant->id, 'code' => 'PRJ-LIA', 'name' => 'P']);
        $building = Building::create(['tenant_id' => $tenant->id, 'project_id' => $project->id, 'code' => 'BLD-LIA', 'name' => 'B']);
        $apartment = Apartment::create(['tenant_id' => $tenant->id, 'building_id' => $building->id, 'code' => 'APT-LIA']);
        $resident = Resident::create(['tenant_id' => $tenant->id, 'building_id' => $building->id, 'code' => 'RES-LIA', 'full_name' => 'Nguyễn Văn A']);

        DB::table('resident_apartment_relations')->insert([
            'tenant_id' => $tenant->id, 'resident_id' => $resident->id, 'apartment_id' => $apartment->id,
            'role' => 'owner', 'is_primary' => true, 'start_date' => '2026-01-01',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        Artisan::call('billing:backfill-liability-periods');

        $liabilities = LiabilityPeriod::withoutGlobalScopes()->where('apartment_id', $apartment->id)->get();
        $this->assertCount(1, $liabilities);
        $lia = $liabilities->first();
        $this->assertSame('owner', $lia->role);
        $this->assertNull($lia->liable_to, 'liability phải MỞ');
        $this->assertSame(['all'], $lia->scope);
        $this->assertTrue($lia->coversFamily('electricity'));

        // Chạy lại: idempotent, không tạo dòng thứ 2.
        Artisan::call('billing:backfill-liability-periods');
        $this->assertSame(1, LiabilityPeriod::withoutGlobalScopes()->where('apartment_id', $apartment->id)->count());
    }

    public function test_coversFamily_theo_scope(): void
    {
        $lia = new LiabilityPeriod(['scope' => ['electricity', 'water']]);
        $this->assertTrue($lia->coversFamily('water'));
        $this->assertFalse($lia->coversFamily('management'));

        $all = new LiabilityPeriod(['scope' => ['all']]);
        $this->assertTrue($all->coversFamily('vehicle'));

        $nullScope = new LiabilityPeriod(['scope' => null]);
        $this->assertTrue($nullScope->coversFamily('other'));
    }
}
