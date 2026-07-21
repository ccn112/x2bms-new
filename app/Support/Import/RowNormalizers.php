<?php

declare(strict_types=1);

namespace App\Support\Import;

use DateTime;
use Illuminate\Support\Str;

/**
 * Chuẩn hóa giá trị & tên cột khi import Excel/CSV — nền dùng chung cho MỌI tầng
 * (SuperAdmin `/sa`, HQ `/hq`, BQL `/admin`) và mọi cơ chế đọc file.
 *
 * Port từ pattern production ở x1web (BoV2CompanyImporter::normalize* +
 * AbstractExcelSheetImportCommand::normalizeHeaderText/rowValue) nhưng KHÔNG phụ
 * thuộc gói Excel nào (x2bms dùng spatie/simple-excel). Chỉ nhận/đưa scalar.
 */
final class RowNormalizers
{
    /** Quy đổi khoảng trắng "ẩn" (nbsp, zero-width, BOM, tab/newline) → space thường. */
    private static function stripInvisible(string $value): string
    {
        return preg_replace('/[\x{00A0}\x{200B}\x{200C}\x{200D}\x{FEFF}\t\r\n]+/u', ' ', $value) ?? $value;
    }

    /** Trim + gộp mọi khoảng trắng (kể cả nbsp/zero-width) về 1 space. Rỗng → null. */
    public static function string(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = self::stripInvisible($value);
        $value = preg_replace('/\s+/u', ' ', trim($value)) ?? trim($value);

        return $value === '' ? null : $value;
    }

    /** Họ tên: chuẩn hóa khoảng trắng + Title Case (unicode) → "nguyễn  văn AN" → "Nguyễn Văn An". */
    public static function name(?string $value): ?string
    {
        $value = self::string($value);

        return $value === null ? null : mb_convert_case($value, MB_CASE_TITLE, 'UTF-8');
    }

    /** Email: bỏ MỌI khoảng trắng (kể cả ở giữa) + lowercase. Rỗng → null. */
    public static function email(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = preg_replace('/\s+/u', '', self::stripInvisible($value)) ?? '';

        return $value === '' ? null : Str::lower($value);
    }

    /**
     * SĐT: bỏ mọi ký tự không phải số/`+`; chuẩn hóa VN:
     *  - `+84`/`84` đầu → `0`; bỏ `+` thừa.
     *  - Mất số 0 đầu do Excel đọc dạng số (9 chữ số bắt đầu 3/5/7/8/9) → thêm `0`.
     * Rỗng → null. (Không ép kiểu nếu không nhận diện được — giữ nguyên số đã làm sạch.)
     */
    public static function phone(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $digits = preg_replace('/[^0-9+]/', '', self::stripInvisible($value)) ?? '';
        if ($digits === '') {
            return null;
        }

        $digits = preg_replace('/^(?:\+?84|0084)/', '0', $digits) ?? $digits;
        $digits = ltrim($digits, '+');

        if (preg_match('/^[35789]\d{8}$/', $digits)) {
            $digits = '0'.$digits;
        }

        return $digits;
    }

    /**
     * CCCD/CMND: chỉ giữ chữ số (bỏ dấu cách "079 090 001 234", chấm...).
     * Mất số 0 đầu do Excel đọc dạng số (còn 11 số) → pad về 12. Rỗng → null.
     */
    public static function idNo(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', self::stripInvisible($value)) ?? '';
        if ($digits === '') {
            return null;
        }

        if (strlen($digits) === 11) {
            $digits = '0'.$digits;
        }

        return $digits;
    }

    /**
     * Parse nhiều định dạng ngày phổ biến (ưu tiên kiểu VN d/m/Y) → chuỗi `Y-m-d`.
     * Không parse được → null.
     */
    public static function date(?string $value): ?string
    {
        $value = self::string($value);

        if ($value === null) {
            return null;
        }

        foreach (['d/m/Y', 'd-m-Y', 'd.m.Y', 'Y-m-d', 'Y/m/d'] as $format) {
            $date = DateTime::createFromFormat($format, $value);

            if ($date !== false) {
                return $date->format('Y-m-d');
            }
        }

        return null;
    }

    /** Chuẩn hóa tên header để so khớp không phân biệt khoảng trắng thừa/newline. */
    public static function header(string $header): string
    {
        $trimmed = trim($header);

        return preg_replace('/\s+/u', ' ', $trimmed) ?? $trimmed;
    }

    /**
     * Lấy giá trị 1 cột từ 1 dòng (map header→value), so khớp theo tên kỳ vọng
     * đã normalize + danh sách alias (giống `guess()` của Filament). Không thấy → null.
     *
     * @param  array<string, mixed>  $row
     * @param  list<string>  $aliases
     */
    public static function value(array $row, string $expected, array $aliases = []): mixed
    {
        $candidates = [$expected, ...$aliases];

        // Lập chỉ mục dòng theo header đã normalize (1 lần) để so khớp O(n).
        $indexed = [];
        foreach ($row as $key => $val) {
            if (is_string($key)) {
                $indexed[self::header($key)] = $val;
            }
        }

        foreach ($candidates as $candidate) {
            $normalized = self::header((string) $candidate);
            if (array_key_exists($normalized, $indexed)) {
                return $indexed[$normalized];
            }
        }

        return null;
    }
}
