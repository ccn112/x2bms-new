<?php

namespace Tests\Feature;

use App\Models\Building;
use App\Models\Project;
use App\Models\ResidentApartmentRelation;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\CommunityTestResidentsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommunityTestResidentsSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_tao_2_cu_dan_cung_du_an(): void
    {
        $tenant = Tenant::create(['code' => 'T-DAIPHUC', 'name' => 'ĐP']);
        $project = Project::create(['tenant_id' => $tenant->id, 'code' => 'DAIPHUC-RS', 'name' => 'Đại Phúc']);
        Building::create(['tenant_id' => $tenant->id, 'project_id' => $project->id, 'code' => 'DP-A', 'name' => 'A']);

        (new CommunityTestResidentsSeeder())->run();
        (new CommunityTestResidentsSeeder())->run(); // idempotent

        $this->assertNotNull(User::where('email', 'chtchinh@gmail.com')->first());
        $this->assertNotNull(User::where('email', 'chatto.vn@gmail.com')->first());
        $this->assertSame(2, User::whereIn('email', ['chtchinh@gmail.com', 'chatto.vn@gmail.com'])->count());
        // 2 quan hệ căn hộ trong CÙNG dự án → cùng cộng đồng.
        $this->assertSame(2, ResidentApartmentRelation::count());
    }
}
