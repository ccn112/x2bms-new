<?php

namespace App\Services\Analytics\StoreReports;

use Illuminate\Support\Carbon;

/**
 * Bóc báo cáo SALES/SUMMARY của App Store Connect.
 *
 * Endpoint trả về **file gzip chứa TSV**, không phải JSON. Số lượt tải nằm ở cột
 * `Units`, và báo cáo gộp MỌI app của cùng vendor nên phải lọc theo SKU.
 *
 * ## Vì sao phải lọc `Product Type Identifier`
 * Cùng một app, một ngày, TSV có nhiều dòng: tải mới, cập nhật, tải lại, in-app
 * purchase… phân biệt bằng `Product Type Identifier`. Cộng hết là **đếm gấp
 * nhiều lần**. Mã bắt đầu bằng `1` là lượt tải mới của app (`1`, `1F`, `1T`,
 * `1-B`…); `7` là cập nhật; `IA*`/`F1` là in-app purchase. Chỉ lấy nhóm `1`.
 *
 * Tách riêng khỏi phần gọi mạng để test được mà không cần khoá .p8.
 */
class AppStoreSalesTsvParser
{
    /**
     * @return array<int, array{stat_date:string, installs:int, raw:array<string,string>}>
     */
    public function parse(string $contents, ?string $sku = null): array
    {
        // Endpoint trả gzip; nhận cả bản đã giải nén để test cho gọn.
        if (str_starts_with($contents, "\x1f\x8b")) {
            $decoded = @gzdecode($contents);
            if ($decoded === false) {
                throw new StoreReportFormatException('Không giải nén được file gzip của App Store.');
            }
            $contents = $decoded;
        }

        $lines = preg_split("/\r\n|\n|\r/", trim($contents)) ?: [];
        if (count($lines) < 2) {
            return [];
        }

        $header = array_map(fn ($h) => strtolower(trim((string) $h)), explode("\t", array_shift($lines)));

        $idxDate = $this->col($header, ['begin date', 'begin_date']);
        $idxUnits = $this->col($header, ['units']);
        $idxSku = $this->col($header, ['vendor identifier', 'sku']);
        $idxType = $this->col($header, ['product type identifier']);

        if ($idxDate === null || $idxUnits === null) {
            throw new StoreReportFormatException(
                'TSV của App Store thiếu cột Begin Date hoặc Units. Cột đọc được: '
                .implode(', ', $header));
        }

        // Gộp theo ngày: một ngày có nhiều dòng (theo quốc gia, theo loại thiết bị).
        $byDate = [];
        foreach ($lines as $line) {
            if (trim($line) === '') {
                continue;
            }
            $cells = explode("\t", $line);

            if ($sku !== null && $idxSku !== null
                && trim((string) ($cells[$idxSku] ?? '')) !== $sku) {
                continue;
            }

            // Chỉ lượt TẢI MỚI (nhóm mã bắt đầu bằng 1). Cộng cả cập nhật (7) và
            // in-app purchase là đếm gấp nhiều lần.
            if ($idxType !== null) {
                $type = trim((string) ($cells[$idxType] ?? ''));
                if ($type !== '' && ! str_starts_with($type, '1')) {
                    continue;
                }
            }

            $date = trim((string) ($cells[$idxDate] ?? ''));
            if ($date === '') {
                continue;
            }
            // Apple dùng MM/DD/YYYY trong báo cáo.
            $day = Carbon::createFromFormat('m/d/Y', $date) ?: Carbon::parse($date);
            $key = $day->toDateString();

            $units = (int) trim((string) ($cells[$idxUnits] ?? '0'));
            if (! isset($byDate[$key])) {
                $byDate[$key] = ['stat_date' => $key, 'installs' => 0, 'raw' => []];
            }
            $byDate[$key]['installs'] += $units;
            $byDate[$key]['raw'][] = array_combine(
                array_slice($header, 0, count($cells)),
                array_slice($cells, 0, count($header))
            ) ?: [];
        }

        ksort($byDate);

        return array_values($byDate);
    }

    /** @param array<int, string> $header */
    private function col(array $header, array $candidates): ?int
    {
        foreach ($candidates as $c) {
            $idx = array_search(strtolower($c), $header, true);
            if ($idx !== false) {
                return (int) $idx;
            }
        }

        return null;
    }
}
