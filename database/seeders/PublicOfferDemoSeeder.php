<?php

namespace Database\Seeders;

use App\Models\Voucher;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Ưu đãi công khai mẫu để dựng/kiểm màn M01-PUB-14.
 *
 * ⚠️ Tên đối tác ở đây là TÊN GIẢ LẬP (Phố Biển, Sen Vàng, Sao Mai…). Cố tình
 * KHÔNG dùng thương hiệu thật như trong ảnh thiết kế: ghi vào DB một bản ghi
 * "thương hiệu X giảm 20%" khi chưa có hợp đồng nào là tạo dữ liệu thương mại
 * sai sự thật, sau này dễ bị hiểu là ưu đãi có thật. Khi có hợp đồng thật thì
 * nhập qua panel quản trị.
 *
 * Idempotent: dedupe theo `code`.
 */
class PublicOfferDemoSeeder extends Seeder
{
    public function run(): void
    {
        $offers = [
            [
                'code' => 'PUB-FOOD-20',
                'name' => 'Giảm 20% toàn bộ hóa đơn',
                'description' => 'Áp dụng cho mọi hóa đơn tại hệ thống nhà hàng, không giới hạn số lần.',
                'partner_name' => 'Nhà hàng Phố Biển',
                'category' => 'food',
                'type' => 'discount',
                'value' => 20,
            ],
            [
                'code' => 'PUB-BEAUTY-15',
                'name' => 'Giảm 15% dịch vụ chăm sóc da',
                'description' => 'Ưu đãi cho các liệu trình chăm sóc da và thư giãn.',
                'partner_name' => 'Sen Vàng Spa',
                'category' => 'beauty',
                'type' => 'discount',
                'value' => 15,
            ],
            [
                'code' => 'PUB-EDU-10',
                'name' => 'Giảm 10% học phí khóa tiếng Anh',
                'description' => 'Dành cho trẻ em và người lớn, áp dụng khi đăng ký khóa mới.',
                'partner_name' => 'Anh ngữ Sao Mai',
                'category' => 'education',
                'type' => 'discount',
                'value' => 10,
            ],
            [
                'code' => 'PUB-SHOP-5',
                'name' => 'Giảm 5% sản phẩm mẹ & bé',
                'description' => 'Áp dụng cho hóa đơn từ 300.000đ tại toàn hệ thống.',
                'partner_name' => 'Siêu thị Bé Ngoan',
                'category' => 'shopping',
                'type' => 'discount',
                'value' => 5,
            ],
            [
                'code' => 'PUB-HEALTH-FREE',
                'name' => 'Miễn phí khám tổng quát lần đầu',
                'description' => 'Gói khám cơ bản dành cho cư dân đăng ký lần đầu.',
                'partner_name' => 'Phòng khám An Tâm',
                'category' => 'health',
                'type' => 'free',
                'value' => 0,
            ],
            [
                'code' => 'PUB-SHOP-100K',
                'name' => 'Giảm 100.000đ cho đơn nội thất',
                'description' => 'Áp dụng cho đơn hàng nội thất từ 2.000.000đ.',
                'partner_name' => 'Nội thất Nhà Vui',
                'category' => 'shopping',
                'type' => 'amount',
                'value' => 100000,
            ],
        ];

        foreach ($offers as $offer) {
            Voucher::withoutGlobalScopes()->updateOrCreate(
                ['code' => $offer['code']],
                $offer + [
                    'tenant_id' => null,
                    'owner_level' => 'platform',
                    'is_public' => true,
                    'points_cost' => 0,
                    //  NOT NULL ở schema — 0 nghĩa là không giới hạn số lượt.
                    'quantity' => 0,
                    'status' => 'active',
                    'valid_from' => now()->subDay(),
                    'valid_to' => now()->addMonths(6),
                    // Ảnh minh họa theo chủ đề (Unsplash) — thay bằng ảnh đối
                    // tác thật khi nhập qua panel.
                    'image_url' => $this->image($offer['category']),
                ]
            );
        }

        $this->command?->info('Đã seed '.count($offers).' ưu đãi công khai (dữ liệu mẫu).');
        $this->command?->line('Tổng public offers: '.DB::table('vouchers')->where('is_public', true)->count());
    }

    private function image(string $category): string
    {
        $ids = [
            'food' => 'photo-1555396273-367ea4eb4db5',
            'beauty' => 'photo-1540555700478-4be289fbecef',
            'education' => 'photo-1503676260728-1c00da094a0b',
            'shopping' => 'photo-1600880292203-757bb62b4baf',
            'health' => 'photo-1576091160399-112ba8d25d1d',
        ];

        return 'https://images.unsplash.com/'.($ids[$category] ?? $ids['shopping'])
            .'?auto=format&fit=crop&w=600&h=600&q=70';
    }
}
