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
    /** Trim + gộp khoảng trắng thừa. Rỗng → null. */
    public static function string(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = preg_replace('/\s+/u', ' ', trim($value)) ?? trim($value);

        return $value === '' ? null : $value;
    }

    /** Lowercase + trim. Rỗng → null. (Không validate; caller tự đặt rule email.) */
    public static function email(?string $value): ?string
    {
        $value = self::string($value);

        return $value === null ? null : Str::lower($value);
    }

    /** Giữ lại chữ số và dấu `+`. Rỗng → null. */
    public static function phone(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = preg_replace('/[^0-9+]/', '', trim($value)) ?? '';

        return $value === '' ? null : $value;
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
