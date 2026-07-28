<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Voucher;
use App\Support\Api\ApiResponse;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Ưu đãi CÔNG KHAI cho app cư dân (M01-PUB-14) — không auth.
 *
 * Chỉ trả voucher `owner_level = platform` + `is_public` + còn hiệu lực. KHÔNG
 * bao giờ trả voucher của tenant: đó là ưu đãi riêng của từng dự án, lộ ra ngoài
 * là lộ chính sách kinh doanh của khách hàng.
 */
class PublicOfferController extends ApiController
{
    /** Danh mục cố định — khớp hàng chip của khuôn. */
    public const CATEGORIES = [
        'food' => 'Ăn uống',
        'beauty' => 'Làm đẹp',
        'education' => 'Giáo dục',
        'shopping' => 'Mua sắm',
        'health' => 'Sức khỏe',
        'other' => 'Khác',
    ];

    /** GET /api/v1/public/offers?category=&per_page= */
    public function index(Request $request): JsonResponse
    {
        $perPage = (int) min(max((int) $request->query('per_page', 20), 1), 50);
        $category = $request->query('category');

        $query = Voucher::query()
            ->where('owner_level', 'platform')
            ->where('is_public', true)
            ->where('status', 'active')
            // Hết hạn thì bỏ, nhưng voucher KHÔNG có ngày hết hạn vẫn hợp lệ.
            ->where(fn (Builder $q) => $q->whereNull('valid_to')->orWhere('valid_to', '>=', now()))
            ->orderByDesc('valid_from')
            ->orderByDesc('id');

        if (is_string($category) && $category !== '' && $category !== 'all') {
            $query->where('category', $category);
        }

        $items = $query->limit($perPage)->get()->map(fn (Voucher $v) => [
            'id' => (string) $v->id,
            'code' => $v->code,
            'title' => $v->name,
            'description' => $v->description,
            'partner_name' => $v->partner_name,
            'category' => $v->category ?: 'other',
            'category_label' => self::CATEGORIES[$v->category ?? 'other'] ?? 'Khác',
            'image' => $v->image_url,
            // Nhãn hiển thị trên badge: tính từ type+value chứ không để app tự
            // đoán (app không biết `type` nghĩa là gì).
            'badge' => $this->badge($v),
            'valid_to' => optional($v->valid_to)->toIso8601String(),
        ])->all();

        return ApiResponse::success($items, [
            'categories' => collect(self::CATEGORIES)
                ->map(fn ($label, $id) => ['id' => $id, 'label' => $label])
                ->values()
                ->all(),
        ]);
    }

    private function badge(Voucher $v): string
    {
        $value = (float) $v->value;

        return match ($v->type) {
            'discount' => $value > 0 ? 'Giảm '.rtrim(rtrim(number_format($value, 2, ',', '.'), '0'), ',').'%' : 'Ưu đãi',
            'amount' => $value > 0 ? 'Giảm '.number_format($value, 0, ',', '.').'đ' : 'Ưu đãi',
            'free' => 'Miễn phí',
            default => 'Ưu đãi',
        };
    }
}
