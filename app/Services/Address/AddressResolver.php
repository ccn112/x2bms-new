<?php

namespace App\Services\Address;

use Illuminate\Support\Facades\DB;

/**
 * Suy diễn ĐỊA CHỈ MỚI 2025 (sau NQ 202/2025/QH15, bỏ cấp huyện, 63->34 tỉnh) từ
 * địa chỉ cũ (ward/district/province). Dựa trên 4 bảng tra cứu admin_*_2025.
 *
 * Ưu tiên khớp:
 *   1) Khớp xã cũ theo tên trong tỉnh mới (ward_name khớp phường/xã mới) -> high
 *   2) Khớp quận/huyện cũ -> nếu ra DUY NHẤT 1 xã mới -> high; nếu nhiều -> chốt tỉnh (medium)
 *   3) Chỉ khớp tỉnh cũ -> tỉnh mới -> medium
 *   4) Không khớp -> low (đoán rỗng)
 */
class AddressResolver
{
    /** cache tra cứu để chạy hàng loạt nhanh */
    protected array $wardsByProvince = [];

    /**
     * @return array{province_new: ?string, ward_new: ?string, matched_by: string, confidence: string}
     */
    public function resolveNew(?string $ward, ?string $district, ?string $province): array
    {
        $wardNorm = self::normalize($ward);
        $districtNorm = self::normalize($district);
        $provinceNorm = self::normalize($province);

        $newProvinceCode = null;
        $newProvinceName = null;

        // --- Bước A: chốt tỉnh mới ---
        // A1: tỉnh cũ -> tỉnh mới (map cấp tỉnh 63->34) — coi là "khung" tỉnh tin cậy,
        // dùng để loại trùng tên quận/huyện giữa các tỉnh ở bước sau.
        $scopeCode = null;
        if ($provinceNorm !== '') {
            $op = DB::table('admin_old_provinces_2025')
                ->where('old_name_norm', $provinceNorm)
                ->first();
            if ($op) {
                $scopeCode = $op->new_province_code;
                $newProvinceCode = $op->new_province_code;
                $newProvinceName = $op->new_province_name;
            }
        }

        // A2: qua quận/huyện cũ — nếu đã biết khung tỉnh thì CHỈ lấy trong tỉnh đó
        // (tránh trùng tên: "Tân Uyên" ở cả Bình Dương lẫn Lai Châu).
        $districtRows = collect();
        if ($districtNorm !== '') {
            $q = DB::table('admin_old_to_new')->where('old_district_norm', $districtNorm);
            if ($scopeCode !== null) {
                $scoped = (clone $q)->where('new_province_code', $scopeCode)->get();
                $districtRows = $scoped->isNotEmpty() ? $scoped : collect();
            } else {
                $districtRows = $q->get();
                if ($districtRows->isNotEmpty()) {
                    $newProvinceCode = $districtRows->first()->new_province_code;
                    $newProvinceName = $districtRows->first()->new_province_name;
                }
            }
        }

        // A3: dữ liệu gốc đôi khi để CẤP QUẬN/HUYỆN CŨ trong cột province
        // (vd "Q.7", "TP. Thủ Đức"). Thử coi province là quận/huyện cũ.
        if ($newProvinceCode === null && $districtRows->isEmpty() && $provinceNorm !== '') {
            $districtRows = DB::table('admin_old_to_new')
                ->where('old_district_norm', $provinceNorm)
                ->get();
            if ($districtRows->count() > 0) {
                $newProvinceCode = $districtRows->first()->new_province_code;
                $newProvinceName = $districtRows->first()->new_province_name;
                // dùng chính province (đã chuẩn hoá) làm district để khớp xã ở bước B3
                $districtNorm = $provinceNorm;
            }
        }

        if ($newProvinceCode === null) {
            return [
                'province_new' => null,
                'ward_new' => null,
                'matched_by' => 'none',
                'confidence' => 'low',
            ];
        }

        // --- Bước B: chốt xã/phường mới ---

        // B1: tên xã cũ khớp trực tiếp một xã/phường mới trong tỉnh mới
        if ($wardNorm !== '') {
            $w = $this->wardsOfProvince($newProvinceCode)[$wardNorm] ?? null;
            if ($w) {
                return $this->ok($newProvinceName, $w, 'ward_name_exact', 'high');
            }
        }

        // B2: quận/huyện cũ -> nếu chỉ ánh xạ tới đúng 1 xã mới -> chắc chắn
        if ($districtRows->count() === 1) {
            return $this->ok($newProvinceName, $districtRows->first()->new_ward_name, 'district_unique', 'high');
        }

        // B3: quận/huyện cũ ra nhiều xã mới -> thử khớp thêm bằng tên ward/district cũ
        if ($districtRows->count() > 1) {
            foreach ([$wardNorm, $districtNorm] as $needle) {
                if ($needle === '') {
                    continue;
                }
                $hit = $districtRows->first(fn ($r) => $r->new_ward_norm === $needle);
                if ($hit) {
                    return $this->ok($newProvinceName, $hit->new_ward_name, 'district_then_name', 'high');
                }
            }

            // biết chắc tỉnh mới, chưa chốt được xã (mơ hồ)
            return $this->ok($newProvinceName, null, 'district_ambiguous', 'medium');
        }

        // B4: chỉ chốt được tỉnh
        return $this->ok($newProvinceName, null, 'province_only', 'medium');
    }

    protected function ok(?string $province, ?string $ward, string $by, string $conf): array
    {
        return [
            'province_new' => $province,
            'ward_new' => $ward,
            'matched_by' => $by,
            'confidence' => $conf,
        ];
    }

    /** @return array<string,string> name_norm => full_name */
    protected function wardsOfProvince(string $code): array
    {
        if (! isset($this->wardsByProvince[$code])) {
            $this->wardsByProvince[$code] = DB::table('admin_wards_2025')
                ->where('province_code', $code)
                ->pluck('full_name', 'name_norm')
                ->all();
        }

        return $this->wardsByProvince[$code];
    }

    /**
     * Chuẩn hoá để so khớp: bỏ dấu tiếng Việt, bỏ tiền tố hành chính, lower, gộp khoảng trắng.
     */
    public static function normalize(?string $value): string
    {
        if ($value === null) {
            return '';
        }

        $s = trim($value);
        if ($s === '') {
            return '';
        }

        // 1) bỏ dấu + hạ thường + đưa mọi ký tự không phải chữ/số về khoảng trắng
        //    ("Q.7" -> "q 7", "TP. Hồ Chí Minh" -> "tp ho chi minh").
        $s = self::removeDiacritics($s);
        $s = mb_strtolower($s);
        $s = preg_replace('/[^a-z0-9]+/', ' ', $s);
        $s = trim(preg_replace('/\s+/', ' ', $s));

        // 2) bỏ tiền tố hành chính ở đầu (kể cả viết tắt tp/tx/q/h/p/tt/t).
        //    Lặp để xử lý các trường hợp lồng như "tp thu duc".
        $prefix = '/^(thanh pho|thi tran|thi xa|tinh|quan|huyen|phuong|xa|tp|tx|tt|q|h|p|t)\s+/';
        while (preg_match($prefix, $s)) {
            $s = preg_replace($prefix, '', $s, 1);
        }

        return trim($s);
    }

    public static function removeDiacritics(string $str): string
    {
        $map = [
            'à', 'á', 'ạ', 'ả', 'ã', 'â', 'ầ', 'ấ', 'ậ', 'ẩ', 'ẫ', 'ă', 'ằ', 'ắ', 'ặ', 'ẳ', 'ẵ',
            'è', 'é', 'ẹ', 'ẻ', 'ẽ', 'ê', 'ề', 'ế', 'ệ', 'ể', 'ễ',
            'ì', 'í', 'ị', 'ỉ', 'ĩ',
            'ò', 'ó', 'ọ', 'ỏ', 'õ', 'ô', 'ồ', 'ố', 'ộ', 'ổ', 'ỗ', 'ơ', 'ờ', 'ớ', 'ợ', 'ở', 'ỡ',
            'ù', 'ú', 'ụ', 'ủ', 'ũ', 'ư', 'ừ', 'ứ', 'ự', 'ử', 'ữ',
            'ỳ', 'ý', 'ỵ', 'ỷ', 'ỹ', 'đ',
        ];
        $rep = [
            'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a',
            'e', 'e', 'e', 'e', 'e', 'e', 'e', 'e', 'e', 'e', 'e',
            'i', 'i', 'i', 'i', 'i',
            'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o',
            'u', 'u', 'u', 'u', 'u', 'u', 'u', 'u', 'u', 'u', 'u',
            'y', 'y', 'y', 'y', 'y', 'd',
        ];
        $lower = mb_strtolower($str);
        $out = str_replace($map, $rep, $lower);

        return $out;
    }
}
