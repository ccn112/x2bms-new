<?php

namespace Database\Seeders;

use App\Models\Notification;
use App\Models\Project;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Làm ĐẦY màn công khai (M01-PUB-02) cho đúng khuôn thiết kế.
 *
 * Khuôn cần: **≥4 dự án** hiện dạng lưới 2×2, mỗi thẻ có **chip trạng thái bán**
 * ("Đang mở bán" / "Sắp bàn giao"), và **≥4 tin tức/sự kiện** có ngày.
 *
 * Trước seeder này: mọi dự án đều `sales_status = NULL` nên chip rơi về nhãn
 * chung "Dự án"; tin công khai (`owner_level=platform`) có thể rỗng → mục
 * "Tin tức & sự kiện" biến mất khỏi màn.
 *
 * Idempotent: chỉ ghi khi trống, chạy lại không đổi dữ liệu đã có.
 *
 *   php artisan db:seed --class=PublicShowcaseSeeder
 */
class PublicShowcaseSeeder extends Seeder
{
    /** Vòng trạng thái để lưới 2×2 không bị một nhãn duy nhất. */
    private const CYCLE = ['open_for_sale', 'handover_soon', 'open_for_sale', 'handed_over'];

    public function run(): void
    {
        $this->seedSalesStatus();
        $this->seedPublicContent();
    }

    /**
     * Rải `sales_status` xen kẽ cho 12 dự án ĐẦU (đúng số dự án API public trả).
     *
     * Dữ liệu gốc có `handover_date` toàn quá khứ → nếu suy ra từ ngày thì cả
     * lưới cùng nhãn "Đã bàn giao", khuôn lại cần thấy "Đang mở bán" và "Sắp bàn
     * giao". Đây là dữ liệu DEMO nên seeder chỉnh luôn `handover_date` khớp với
     * nhãn, để chip và ngày không mâu thuẫn nhau.
     */
    private function seedSalesStatus(): void
    {
        $projects = Project::withoutGlobalScopes()
            ->orderByDesc('id')   // cùng thứ tự API public đang dùng
            ->limit(12)
            ->get();

        if ($projects->isEmpty()) {
            $this->command?->warn('  Public: chưa có dự án nào.');

            return;
        }

        foreach ($projects->values() as $i => $p) {
            $status = self::CYCLE[$i % count(self::CYCLE)];
            $handover = match ($status) {
                'handed_over' => now()->subMonths(6 + $i),
                'handover_soon' => now()->addMonths(3 + ($i % 6)),
                default => now()->addMonths(18 + $i),
            };
            $p->forceFill([
                'sales_status' => $status,
                'handover_date' => $handover->toDateString(),
            ])->saveQuietly();
        }

        $dist = $projects->countBy(fn ($p) => $p->sales_status)->toJson();
        $this->command?->info("  Public: rải sales_status cho {$projects->count()} dự án → {$dist}");
    }

    /**
     * Tin công khai = `Notification` cấp `platform` đã publish (API public đọc
     * đúng nguồn này). Chỉ tạo khi chưa đủ 4 tin.
     */
    private function seedPublicContent(): void
    {
        $existing = Notification::withoutGlobalScopes()
            ->where('owner_level', 'platform')
            ->where('status', 'published')
            ->count();

        if ($existing >= 4) {
            $this->command?->info("  Public: đã có {$existing} tin công khai — bỏ qua.");

            return;
        }

        $items = [
            ['Lễ ra mắt phân khu The Lake Premium', 'Sự kiện ra mắt phân khu cao cấp ven hồ với hơn 300 khách mời và chương trình ưu đãi đặt chỗ sớm.', 'event', 3],
            ['Tiến độ xây dựng tháng 5/2025', 'Cập nhật tiến độ thi công các toà G1–G5: hoàn thiện mặt dựng, lắp đặt thang máy và cảnh quan nội khu.', 'news', 9],
            ['Khai trương trung tâm tiện ích nội khu', 'Bể bơi bốn mùa, phòng gym và khu vui chơi trẻ em chính thức đi vào hoạt động phục vụ cư dân.', 'news', 16],
            ['Chính sách bàn giao quý IV/2025', 'Công bố lịch bàn giao, quy trình nghiệm thu căn hộ và chính sách hỗ trợ cư dân chuyển vào ở.', 'news', 24],
        ];

        $created = 0;
        foreach ($items as [$title, $body, $type, $daysAgo]) {
            Notification::withoutGlobalScopes()->updateOrCreate(
                ['code' => 'PUBLIC-'.md5($title)],
                [
                    'tenant_id' => null,
                    'owner_level' => 'platform',
                    'title' => $title,
                    'body' => $body,
                    'type' => $type,
                    'status' => 'published',
                    'published_at' => now()->subDays($daysAgo),
                ]
            );
            $created++;
        }

        $this->command?->info("  Public: {$created} tin/sự kiện công khai.");
    }
}
