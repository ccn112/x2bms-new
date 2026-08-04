<?php

namespace Tests\Feature;

use App\Models\Building;
use App\Models\Project;
use App\Models\Tenant;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * ① POC hard-lock tenant tầng DB (ADR-001) — composite FK
 * `notifications(tenant_id, building_id) → buildings(tenant_id, id)` phải TỪ CHỐI
 * insert lai-tenant ở tầng DB, kể cả khi ghi thẳng (bỏ qua mọi app scope).
 *
 * MySQL-only: ràng buộc chỉ áp trên prod MySQL (migration guard theo driver). Suite
 * chạy sqlite → skip (tự tài liệu). Bằng chứng thủ công đã chạy trên MySQL dev.
 */
class TenantCompositeFkTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        if (DB::getDriverName() !== 'mysql') {
            $this->markTestSkipped('Composite FK chống lai-tenant chỉ áp MySQL (prod).');
        }
    }

    public function test_db_tu_choi_notification_tro_toa_khac_tenant(): void
    {
        [, $bA] = $this->tenantBuilding('FKA');
        [$tB] = $this->tenantBuilding('FKB');

        // tenant B + tòa của tenant A → LAI TENANT → DB phải chặn.
        $this->expectException(QueryException::class);
        DB::table('notifications')->insert([
            'tenant_id' => $tB->id, 'building_id' => $bA->id,
            'owner_level' => 'project', 'title' => 'lai-tenant', 'status' => 'draft',
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function test_dung_tenant_va_khong_nham_toa_van_ghi_duoc(): void
    {
        [$tA, $bA] = $this->tenantBuilding('FKC');

        $ok = DB::table('notifications')->insertGetId([
            'tenant_id' => $tA->id, 'building_id' => $bA->id,
            'owner_level' => 'project', 'title' => 'dung-tenant', 'status' => 'draft',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->assertIsInt($ok);

        // building_id null (không nhắm tòa) → không bị FK chặn.
        $ok2 = DB::table('notifications')->insertGetId([
            'tenant_id' => $tA->id, 'building_id' => null,
            'owner_level' => 'project', 'title' => 'khong-toa', 'status' => 'draft',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->assertIsInt($ok2);
    }

    /** @return array{0:Tenant,1:Building} */
    private function tenantBuilding(string $tag): array
    {
        $t = Tenant::create(['code' => "TEN-$tag", 'name' => "T $tag"]);
        $p = Project::create(['tenant_id' => $t->id, 'code' => "PRJ-$tag", 'name' => "P $tag"]);
        $b = Building::create(['tenant_id' => $t->id, 'project_id' => $p->id, 'code' => "BLD-$tag", 'name' => "B $tag"]);

        return [$t, $b];
    }
}
