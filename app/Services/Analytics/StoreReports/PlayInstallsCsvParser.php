<?php

namespace App\Services\Analytics\StoreReports;

use Illuminate\Support\Carbon;

/**
 * Bóc file `installs_{package}_{yyyyMM}_overview.csv` mà Play Console đẩy vào
 * bucket Cloud Storage của nhà phát triển.
 *
 * Tách riêng khỏi phần gọi mạng để **test được mà không cần credential** — đây là
 * chỗ dễ sai nhất (encoding + tên cột), còn phần tải file chỉ là một request.
 *
 * ## Hai cái bẫy thật của file này
 *
 * 1. **File mã hoá UTF-16LE kèm BOM**, không phải UTF-8. Đọc thẳng bằng
 *    `str_getcsv` sẽ ra ký tự NUL xen giữa từng chữ và không khớp tên cột nào.
 * 2. **Tên cột đổi theo thời gian.** Play đã đổi bộ tên metric (xem
 *    support.google.com/googleplay/android-developer/answer/9419939), và CSV tải
 *    về vẫn giữ tên cũ để không phá script của nhà phát triển. Nên phải nhận
 *    NHIỀU biến thể tên cho cùng một ý nghĩa, và nếu không khớp cái nào thì báo
 *    lỗi rõ ràng chứ đừng âm thầm trả 0 — số 0 giả trông y như "hôm đó không ai
 *    tải".
 */
class PlayInstallsCsvParser
{
    /**
     * Các biến thể tên cột cho từng chỉ số. So khớp không phân biệt hoa/thường
     * và bỏ qua khoảng trắng.
     *
     * @var array<string, array<int, string>>
     */
    private const COLUMNS = [
        'installs' => ['Daily Device Installs', 'Daily User Installs'],
        'uninstalls' => ['Daily Device Uninstalls', 'Daily User Uninstalls'],
        'updates' => ['Daily Device Upgrades'],
        'active_devices' => ['Active Device Installs', 'Installs on active devices'],
    ];

    /**
     * @return array<int, array{stat_date:string, installs:?int, uninstalls:?int, updates:?int, active_devices:?int, raw:array<string,string>}>
     */
    public function parse(string $contents): array
    {
        $text = $this->toUtf8($contents);
        $lines = preg_split("/\r\n|\n|\r/", trim($text)) ?: [];
        if (count($lines) < 2) {
            return [];
        }

        $header = array_map(
            fn ($h) => strtolower(trim(str_replace("\u{FEFF}", '', (string) $h))),
            str_getcsv(array_shift($lines))
        );

        $dateIdx = $this->findColumn($header, ['Date']);
        if ($dateIdx === null) {
            throw new StoreReportFormatException(
                'CSV của Play không có cột Date. Cột đọc được: '.implode(', ', $header));
        }

        $map = [];
        foreach (self::COLUMNS as $key => $names) {
            $map[$key] = $this->findColumn($header, $names);
        }

        if ($map['installs'] === null) {
            throw new StoreReportFormatException(
                'CSV của Play không có cột lượt cài nào khớp ('
                .implode(' / ', self::COLUMNS['installs']).'). Cột đọc được: '
                .implode(', ', $header));
        }

        $rows = [];
        foreach ($lines as $line) {
            if (trim($line) === '') {
                continue;
            }
            $cells = str_getcsv($line);
            $date = trim((string) ($cells[$dateIdx] ?? ''));
            if ($date === '') {
                continue;
            }

            $rows[] = [
                'stat_date' => Carbon::parse($date)->toDateString(),
                'installs' => $this->intAt($cells, $map['installs']),
                'uninstalls' => $this->intAt($cells, $map['uninstalls']),
                'updates' => $this->intAt($cells, $map['updates']),
                'active_devices' => $this->intAt($cells, $map['active_devices']),
                'raw' => array_combine(
                    array_slice($header, 0, count($cells)),
                    array_slice($cells, 0, count($header))
                ) ?: [],
            ];
        }

        return $rows;
    }

    /** UTF-16LE (có/không BOM) → UTF-8. File đã là UTF-8 thì giữ nguyên. */
    private function toUtf8(string $raw): string
    {
        // BOM UTF-16LE
        if (str_starts_with($raw, "\xFF\xFE")) {
            return (string) mb_convert_encoding(substr($raw, 2), 'UTF-8', 'UTF-16LE');
        }
        if (str_starts_with($raw, "\xFE\xFF")) {
            return (string) mb_convert_encoding(substr($raw, 2), 'UTF-8', 'UTF-16BE');
        }
        // Không BOM nhưng vẫn là UTF-16LE: ký tự ASCII sẽ đi kèm một byte NUL.
        if (str_contains(substr($raw, 0, 200), "\x00")) {
            return (string) mb_convert_encoding($raw, 'UTF-8', 'UTF-16LE');
        }

        return preg_replace('/^\xEF\xBB\xBF/', '', $raw) ?? $raw;
    }

    /** @param array<int, string> $header */
    private function findColumn(array $header, array $candidates): ?int
    {
        foreach ($candidates as $name) {
            $needle = strtolower(trim($name));
            $idx = array_search($needle, $header, true);
            if ($idx !== false) {
                return (int) $idx;
            }
        }

        return null;
    }

    /** @param array<int, string> $cells */
    private function intAt(array $cells, ?int $idx): ?int
    {
        if ($idx === null || ! array_key_exists($idx, $cells)) {
            return null;
        }
        $v = trim((string) $cells[$idx]);

        // Ô rỗng KHÁC 0: rỗng là store không cấp số cho ngày/chiều đó.
        return $v === '' ? null : (int) round((float) str_replace(',', '', $v));
    }
}
