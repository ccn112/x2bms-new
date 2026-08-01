<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Tenant;
use App\Services\Resident\WeatherService;
use Database\Seeders\ProjectCoordinatesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Weather Home trả `null` khi project thiếu toạ độ (phát hiện verify live
 * 2026-08-01). ProjectCoordinatesSeeder bù toạ độ cho project vận hành đã tồn
 * tại; test khoá cả việc bù đúng lẫn hệ quả: có toạ độ → WeatherService ra dữ liệu.
 */
class ProjectCoordinatesWeatherTest extends TestCase
{
    use RefreshDatabase;

    private function project(string $code, ?float $lat, ?float $lng): Project
    {
        $tenant = Tenant::create(['code' => "TEN-$code", 'name' => "Tenant $code"]);

        return Project::create([
            'tenant_id' => $tenant->id,
            'code' => $code,
            'name' => 'Đại Phúc Riverside',
            'city' => 'TP. Hồ Chí Minh',
            'latitude' => $lat,
            'longitude' => $lng,
        ]);
    }

    public function test_seeder_bu_toa_do_cho_project_thieu(): void
    {
        $p = $this->project('DAIPHUC-RS', null, null);

        (new ProjectCoordinatesSeeder())->run();

        $p->refresh();
        $this->assertNotNull($p->latitude);
        $this->assertNotNull($p->longitude);
        $this->assertEqualsWithDelta(10.7769, (float) $p->latitude, 0.001);
        $this->assertEqualsWithDelta(106.7009, (float) $p->longitude, 0.001);
    }

    public function test_seeder_khong_de_toa_do_da_co(): void
    {
        $p = $this->project('DAIPHUC-RS', 1.234567, 2.345678);

        (new ProjectCoordinatesSeeder())->run();

        $p->refresh();
        $this->assertEqualsWithDelta(1.234567, (float) $p->latitude, 0.000001,
            'không được đè toạ độ đã có');
    }

    public function test_co_toa_do_thi_weather_khong_con_null(): void
    {
        Http::fake([
            '*' => Http::response([
                'current' => [
                    'temperature_2m' => 31.4,
                    'relative_humidity_2m' => 72,
                    'weather_code' => 2, // 'Có mây'
                    'wind_speed_10m' => 8.0,
                ],
                'daily' => [
                    'temperature_2m_max' => [33.0],
                    'temperature_2m_min' => [26.0],
                ],
            ], 200),
        ]);

        $p = $this->project('DAIPHUC-RS', 10.7769, 106.7009);

        $w = app(WeatherService::class)->forProject($p->id);

        $this->assertNotNull($w, 'có toạ độ thì weather phải ra dữ liệu, không null');
        $this->assertSame(31, $w['temp_c']);
        $this->assertSame('Có mây', $w['condition']);
        $this->assertSame('TP. Hồ Chí Minh', $w['location']);
    }
}
