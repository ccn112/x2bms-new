<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\PublicProject;
use App\Support\Api\ApiResponse;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Danh mục dự án CÔNG KHAI cho app cư dân (M01-PUB-03/04).
 *
 * Nguồn là bảng `public_projects` (thư viện dùng chung toàn nền tảng, ~1.8k dự
 * án nhập từ batdongsan) — KHÁC bảng `projects` vốn là dự án đang vận hành của
 * từng tenant mà `public/bootstrap` đang trả. Trước đây app chỉ thấy vài chục
 * dự án tenant, chọn khu vực nào ngoài TP.HCM cũng trống.
 *
 * Không auth, không lộ dữ liệu tenant: chỉ đọc bản ghi `is_public = true`.
 */
class PublicProjectController extends ApiController
{
    /** GET /api/v1/public/projects?city=&q=&page=&per_page= */
    public function index(Request $request): JsonResponse
    {
        $perPage = (int) min(max((int) $request->query('per_page', 20), 1), 50);
        $page = (int) max((int) $request->query('page', 1), 1);

        $query = PublicProject::query()
            ->where('is_public', true)
            // Ảnh bìa đọc từ thư viện ProjectMedia (ưu tiên ảnh chính thống hơn
            // ảnh batdongsan có watermark) — nạp sẵn để khỏi N+1.
            ->with(['media' => fn ($m) => $m->where('is_active', true)->orderBy('sort_order')])
            // Dự án có ảnh + có số căn lên trước: danh sách đầu tiên người lạ
            // nhìn thấy không nên toàn thẻ thiếu ảnh.
            ->orderByRaw("CASE WHEN json_extract(metadata_json, '$.cover_image') IS NULL THEN 1 ELSE 0 END")
            ->orderByRaw('CASE WHEN apartments IS NULL OR apartments = 0 THEN 1 ELSE 0 END')
            ->orderBy('name');

        $this->applyCity($query, $request->query('city'));
        $this->applySearch($query, $request->query('q'));

        $total = (clone $query)->count();
        $items = $query->forPage($page, $perPage)->get()
            ->map(fn (PublicProject $p) => $this->card($p))
            ->all();

        return ApiResponse::success($items, [
            'page' => $page,
            'per_page' => $perPage,
            'total' => $total,
            'has_more' => $page * $perPage < $total,
        ]);
    }

    /** GET /api/v1/public/projects/{slug} — slug = `code`, fallback id. */
    public function show(string $slug): JsonResponse
    {
        $project = PublicProject::query()
            ->where('is_public', true)
            ->with(['media' => fn ($m) => $m->where('is_active', true)->orderBy('sort_order')])
            ->where(fn (Builder $q) => $q->where('code', $slug)->orWhere('id', $slug))
            ->first();

        if (! $project) {
            return ApiResponse::error('PROJECT_NOT_FOUND', 'Không tìm thấy dự án.', 404);
        }

        return ApiResponse::success($this->detail($project));
    }

    // ---------------------------------------------------------------- filters

    /**
     * Lọc theo thành phố. Dữ liệu nhập từ nhiều nguồn nên cùng một nơi có thể
     * là "Hồ Chí Minh", "TP.HCM", "TP. Thủ Đức" → so khớp theo BÍ DANH đã bỏ
     * dấu, và soi cả `province` lẫn `address` (Phú Quốc/Cần Thơ chỉ nằm trong
     * địa chỉ chứ chưa được tách cột).
     */
    private function applyCity(Builder $query, ?string $city): void
    {
        $needles = $this->cityNeedles($city);
        if (empty($needles)) {
            return;
        }

        $query->where(function (Builder $q) use ($needles) {
            foreach ($needles as $needle) {
                $q->orWhere('province', 'like', "%{$needle}%")
                    ->orWhere('address', 'like', "%{$needle}%")
                    ->orWhere('district', 'like', "%{$needle}%");
            }
        });
    }

    /** @return array<int,string> chuỗi con cần dò (giữ dấu vì DB lưu có dấu) */
    private function cityNeedles(?string $city): array
    {
        $key = Str::of((string) $city)->lower()->replace(['tp.', 'tp', 'thành phố', 'tỉnh'], '')->squish()->value();

        return match (true) {
            $key === '' => [],
            str_contains($key, 'hồ chí minh') || str_contains($key, 'hcm') || str_contains($key, 'sài gòn')
                => ['Hồ Chí Minh', 'HCM', 'Thủ Đức'],
            str_contains($key, 'hà nội') => ['Hà Nội'],
            str_contains($key, 'đà nẵng') => ['Đà Nẵng'],
            str_contains($key, 'hưng yên') => ['Hưng Yên'],
            str_contains($key, 'phú quốc') => ['Phú Quốc', 'Kiên Giang'],
            str_contains($key, 'cần thơ') => ['Cần Thơ'],
            default => [$city],
        };
    }

    /**
     * Tìm theo TÊN · CHỦ ĐẦU TƯ · ĐỊA CHỈ.
     *
     * Địa chỉ dò cả `address` lẫn 3 cột đã tách (`ward`/`district`/`province`):
     * dữ liệu nhập từ nhiều nguồn, có bản ghi chỉ có chuỗi địa chỉ đầy đủ, có
     * bản ghi ngược lại chỉ có cột tách. Thêm `code` để tra nhanh mã dự án.
     */
    private function applySearch(Builder $query, ?string $q): void
    {
        $q = trim((string) $q);
        if ($q === '') {
            return;
        }

        $columns = ['name', 'developer_name', 'address', 'ward', 'district', 'province', 'code'];

        $query->where(function (Builder $sub) use ($columns, $q) {
            foreach ($columns as $column) {
                $sub->orWhere($column, 'like', "%{$q}%");
            }
        });
    }

    // ---------------------------------------------------------------- mapping

    /** Thẻ trong danh sách (M01-PUB-03). */
    private function card(PublicProject $p): array
    {
        $detail = (array) data_get($p->metadata_json, 'detail', []);

        return [
            'id' => (string) $p->id,
            'slug' => $p->code ?: (string) $p->id,
            'name' => $p->name,
            'location' => $this->location($p),
            'status' => $this->salesStatus($p),
            'image' => $this->cover($p),
            'summary' => $p->description,
            'units' => $this->units($p, $detail),
            'area_range' => $this->areaRange($detail),
            'towers' => $this->towers($p, $detail),
            'handover_year' => $this->handoverYear($p),
            // Trả ngay ở thẻ danh sách: tìm được theo chủ đầu tư thì phải NHÌN
            // thấy chủ đầu tư, không thì người dùng không hiểu vì sao ra kết quả.
            'developer_name' => $p->developer_name,
        ];
    }

    /** Chi tiết (M01-PUB-04) = thẻ + điểm nổi bật/tiện ích/thư viện ảnh. */
    private function detail(PublicProject $p): array
    {
        // Thư viện ảnh: ưu tiên ProjectMedia (đã dedup, có cờ watermark và sắp
        // xếp ảnh chính thống lên trước); dự án chưa sync media thì lấy tạm
        // danh sách thô trong metadata.
        $images = $p->media->isNotEmpty()
            ? $p->media->pluck('file_url')->filter()->values()->all()
            : array_values(array_filter((array) data_get($p->metadata_json, 'images', [])));

        return $this->card($p) + [
            'highlights' => $this->highlights($p),
            'amenities' => $this->amenities($p),
            'gallery_count' => count($images),
            'gallery' => array_slice($images, 0, 12),
        ];
    }

    private function location(PublicProject $p): string
    {
        $parts = array_filter([$p->district, $p->province]);

        return $parts ? implode(', ', $parts) : (string) ($p->address ?? '');
    }

    /**
     * Trạng thái BÁN HÀNG cho chip trên thẻ. `status` của bảng là trạng thái
     * vòng đời dự án (planning/selling/handover/operating) — ánh xạ sang đúng
     * bốn giá trị app hiểu, giá trị lạ để app tự rơi về "unknown".
     */
    private function salesStatus(PublicProject $p): string
    {
        return match ($p->status) {
            'selling' => 'open_for_sale',
            'handover', 'operating' => 'handed_over',
            'planning' => 'planning',
            default => 'unknown',
        };
    }

    /// Ảnh bìa: `coverUrl()` đã ưu tiên ProjectMedia is_cover (official/manual
    /// trước batdongsan) rồi mới rơi về metadata.
    private function cover(PublicProject $p): ?string
    {
        return $p->coverUrl() ?? data_get($p->metadata_json, 'images.0');
    }

    /** "1.200" — giữ nguyên cách viết của nguồn, chỉ bỏ chữ "căn". */
    private function units(PublicProject $p, array $detail): ?string
    {
        // `apartments = 1` gặp ở toà văn phòng/dự án nhập thiếu — hiện "1 căn hộ"
        // trông như lỗi, thà bỏ dòng đó.
        if ((int) $p->apartments > 1) {
            return number_format((int) $p->apartments, 0, ',', '.');
        }
        $raw = $detail['Số căn'] ?? null;

        return $raw ? trim(str_ireplace('căn', '', $raw)) : null;
    }

    private function areaRange(array $detail): ?string
    {
        return $detail['Diện tích'] ?? null;
    }

    private function towers(PublicProject $p, array $detail): ?string
    {
        if ($p->blocks) {
            return (string) $p->blocks;
        }
        $raw = $detail['Số tòa'] ?? null;

        return $raw ? trim(str_ireplace(['tòa', 'toà'], '', $raw)) : null;
    }

    /** Năm bàn giao chỉ có ở vài nguồn — không suy đoán nếu thiếu. */
    private function handoverYear(PublicProject $p): ?string
    {
        $raw = (string) (data_get($p->metadata_json, 'detail.Thời điểm bàn giao')
            ?? data_get($p->metadata_json, 'detail.Bàn giao') ?? '');

        return preg_match('/(20\d{2})/', $raw, $m) ? $m[1] : null;
    }

    /**
     * Bốn ô "Tổng quan dự án". Lấy từ dữ liệu CÓ THẬT (loại hình, pháp lý, quy
     * mô, chủ đầu tư) chứ không phải câu quảng cáo dựng sẵn.
     *
     * @return array<int,string>
     */
    private function highlights(PublicProject $p): array
    {
        $detail = (array) data_get($p->metadata_json, 'detail', []);

        return array_values(array_filter([
            $p->project_type ?: ($detail['Loại hình'] ?? null),
            $detail['Pháp lý'] ?? (data_get($p->metadata_json, 'legal') ?: null),
            $detail['Diện tích'] ?? null,
            $p->developer_name,
        ]));
    }

    /** @return array<int,string> */
    private function amenities(PublicProject $p): array
    {
        $labels = [
            'gym' => 'Phòng gym',
            'pool' => 'Hồ bơi',
            'bbq' => 'Khu BBQ',
            'kids' => 'Khu vui chơi trẻ em',
            'mall' => 'Trung tâm thương mại',
            'park' => 'Công viên nội khu',
            'school' => 'Trường học',
            'sky_bar' => 'Sky bar',
            'lake' => 'Hồ cảnh quan',
            'golf' => 'Sân golf',
        ];

        return array_values(array_map(
            fn ($a) => $labels[$a] ?? (string) $a,
            (array) ($p->amenities_json ?? []),
        ));
    }
}
