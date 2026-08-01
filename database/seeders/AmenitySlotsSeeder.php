<?php

namespace Database\Seeders;

use App\Models\Amenity;
use App\Models\AmenitySlot;
use Illuminate\Database\Seeder;

/**
 * Seed khung giờ MẪU cho tiện ích demo. Khung giờ do BQL thiết lập; dữ liệu demo
 * chưa có slot nào nên màn đặt tiện ích không hiện khung giờ → không soi được
 * luồng "còn/hết chỗ theo ngày" (UX review 2026-08-01 ý 4).
 *
 * Idempotent: chỉ thêm cho tiện ích CHƯA có slot. `day_of_week = null` = áp dụng
 * mọi ngày; sức chứa 2/slot để dễ thấy trạng thái đầy khi có 2 booking.
 */
class AmenitySlotsSeeder extends Seeder
{
    private const SLOTS = [
        ['06:00', '08:00'], ['08:00', '10:00'], ['10:00', '12:00'],
        ['14:00', '16:00'], ['16:00', '18:00'], ['18:00', '20:00'],
    ];

    public function run(): void
    {
        Amenity::withoutGlobalScopes()->doesntHave('slots')->get()
            ->each(function (Amenity $amenity): void {
                foreach (self::SLOTS as [$start, $end]) {
                    AmenitySlot::create([
                        'amenity_id' => $amenity->id,
                        'day_of_week' => null,
                        'start_time' => $start,
                        'end_time' => $end,
                        'capacity' => 2,
                        'status' => 'open',
                    ]);
                }
            });
    }
}
