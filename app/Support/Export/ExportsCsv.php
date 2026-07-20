<?php

declare(strict_types=1);

namespace App\Support\Export;

use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Xuất CSV theo dòng (streaming, tiết kiệm bộ nhớ) kèm BOM UTF-8 để Excel mở đúng
 * tiếng Việt — nền dùng chung cho mọi màn list ở cả 3 tầng (SA/HQ/BQL).
 *
 * Tách từ pattern lặp lại trong các bespoke Page (vd ResidentDirectory::export).
 * KHÔNG tự ghi audit / không tự scope: caller giữ trách nhiệm truyền query đã
 * scope theo context (tenant_id/building_id) và ghi audit trước khi gọi — để
 * trait độc lập tầng và không giấu side-effect.
 */
trait ExportsCsv
{
    /**
     * @param  iterable<mixed>  $rows       Dòng dữ liệu (nên là lazy/cursor để tiết kiệm RAM).
     * @param  list<string>  $headers       Tiêu đề cột.
     * @param  callable(mixed):array<int, scalar|null>  $mapRow  Ánh xạ 1 dòng → mảng ô.
     * @param  string  $filenameBase        Tên file (chưa gồm timestamp/đuôi), vd `residents`.
     */
    protected function streamCsv(iterable $rows, array $headers, callable $mapRow, string $filenameBase): StreamedResponse
    {
        $filename = $filenameBase.'_'.now()->format('Ymd_His').'.csv';

        return response()->streamDownload(function () use ($rows, $headers, $mapRow): void {
            $out = fopen('php://output', 'w');
            fwrite($out, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM UTF-8
            fputcsv($out, $headers);

            foreach ($rows as $row) {
                fputcsv($out, $mapRow($row));
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
