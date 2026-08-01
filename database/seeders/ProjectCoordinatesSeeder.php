<?php

namespace Database\Seeders;

use App\Models\Project;
use Illuminate\Database\Seeder;

/**
 * Bù toạ độ cho project VẬN HÀNH đã tồn tại (dữ liệu cũ thiếu
 * latitude/longitude → card thời tiết + AQI ở Home ẩn / kẹt "Đang cập nhật",
 * vì WeatherService/AqiService proxy Open-Meteo theo `projects.latitude/longitude`).
 *
 * Idempotent: chỉ set khi đang NULL, KHÔNG đè toạ độ đã có. Toạ độ cấp thành phố
 * là đủ (Open-Meteo trả thời tiết theo vùng). Thêm code mới vào [COORDS] khi có
 * dự án khác thiếu toạ độ.
 */
class ProjectCoordinatesSeeder extends Seeder
{
    /** code dự án → [latitude, longitude] (cấp thành phố). */
    private const COORDS = [
        'DAIPHUC-RS' => [10.7769000, 106.7009000], // TP. Hồ Chí Minh
    ];

    public function run(): void
    {
        foreach (self::COORDS as $code => [$lat, $lng]) {
            Project::withoutGlobalScopes()
                ->where('code', $code)
                ->where(fn ($q) => $q->whereNull('latitude')->orWhereNull('longitude'))
                ->update(['latitude' => $lat, 'longitude' => $lng]);
        }
    }
}
