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

    /**
     * Số thập phân đơn giản (chỉ số công tơ, số lượng, %) — KHÔNG dùng cho tiền, xem
     * `money()`. Nhận cả dấu `,` lẫn `.` làm dấu thập phân (không phân biệt hàng nghìn ở
     * đây vì các cột này không cần nhóm số). Không parse được → null.
     */
    public static function decimal(?string $value): ?float
    {
        $v = self::string($value);
        if ($v === null) {
            return null;
        }

        $v = preg_replace('/[^0-9.,\-]/u', '', $v) ?? '';
        $v = str_replace(',', '.', $v);

        return is_numeric($v) ? (float) $v : null;
    }

    /**
     * Tiền VND — số nguyên đồng (D7, `docs/BILLING_OWNER_DECISIONS_20260731.md`).
     *
     * Trả về `int` khi rút gọn SẠCH được về số nguyên đồng. Trả về CHUỖI đã làm sạch
     * (không ép được về nguyên) khi giá trị có phần lẻ khác 0 hoặc định dạng không nhận
     * diện được — normalizer không có kênh báo lỗi riêng (xem `ImportColumnSpec::extract()`),
     * nên tầng validate của profile đọc lại giá trị này: `is_int()` thật thì hợp lệ, chuỗi
     * thì lấy nguyên văn để echo vào thông báo lỗi "Tiền đồng không có số lẻ".
     *
     * Nhận: `518000` · `518.000` · `518,000` · `"518 000"` · `518000 đ` → `518000` (int).
     * Từ chối (giữ dạng chuỗi): `518000.5` · `518.000,50` — phần lẻ khác 0.
     * Chấp nhận `.00`/`,00` (Excel hay xuất số nguyên kèm đuôi này) → cắt về nguyên.
     *
     * Quy ước phân biệt "dấu ngăn cách hàng nghìn" với "dấu thập phân": nhìn vào DẤU
     * TÁCH CUỐI CÙNG trong chuỗi — theo sau đúng 3 chữ số thì coi là hàng nghìn (gộp mọi
     * dấu cùng loại lại, xử lý được cả `1.234.567`); theo sau 1-2 chữ số thì coi là thập
     * phân (chấp nhận nếu toàn số 0, từ chối nếu khác 0).
     */
    public static function money(?string $value): int|string|null
    {
        $raw = self::string($value);
        if ($raw === null) {
            return null;
        }

        $clean = preg_replace('/[^0-9.,\-]/u', '', $raw) ?? '';
        if ($clean === '' || $clean === '-') {
            return null;
        }

        $negative = str_starts_with($clean, '-');
        $clean = ltrim($clean, '-');

        $lastSep = null;
        foreach ([strrpos($clean, '.'), strrpos($clean, ',')] as $pos) {
            if ($pos !== false && ($lastSep === null || $pos > $lastSep)) {
                $lastSep = $pos;
            }
        }

        if ($lastSep === null) {
            $digits = $clean;
        } else {
            $fraction = substr($clean, $lastSep + 1);
            $integerPart = substr($clean, 0, $lastSep);

            if ($fraction !== '' && ctype_digit($fraction) && strlen($fraction) === 3) {
                $digits = preg_replace('/[.,]/', '', $clean) ?? $clean;
            } elseif ($fraction !== '' && ctype_digit($fraction) && (int) $fraction === 0) {
                $digits = preg_replace('/[.,]/', '', $integerPart) ?? $integerPart;
            } else {
                return $clean;
            }
        }

        if ($digits === '' || ! ctype_digit($digits)) {
            return $clean;
        }

        $intVal = (int) $digits;

        return $negative ? -$intVal : $intVal;
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
