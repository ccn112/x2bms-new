<?php

namespace App\Support\Knowledge;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Trích text từ nội dung bài KB + tệp đính kèm (PDF/DOCX) để X2AI đọc được.
 * PDF: smalot/pdfparser. DOCX: ZipArchive (word/document.xml). HTML body: strip tags.
 * DOC nhị phân cũ / định dạng khác: bỏ qua (ghi chú tên tệp).
 */
class DocumentTextExtractor
{
    /** Trần ký tự cho mỗi tệp và cho tổng content_text (chống phình). */
    private const PER_FILE_LIMIT = 20000;

    private const TOTAL_LIMIT = 60000;

    /**
     * Gộp text đọc được từ body (HTML) + các tệp đính kèm (đường dẫn trên disk `public`).
     *
     * @param  array<int, string>  $attachmentPaths
     */
    public function build(?string $bodyHtml, array $attachmentPaths = []): string
    {
        $parts = [];

        $body = trim($this->htmlToText($bodyHtml ?? ''));
        if ($body !== '') {
            $parts[] = $body;
        }

        foreach ($attachmentPaths as $path) {
            $name = basename($path);
            $text = trim($this->fromStoredFile($path));
            if ($text !== '') {
                $parts[] = "[Tệp: {$name}]\n".Str::limit($text, self::PER_FILE_LIMIT, '…');
            } else {
                $parts[] = "[Tệp đính kèm: {$name} — chưa trích được nội dung tự động]";
            }
        }

        return $this->clean(Str::limit(trim(implode("\n\n", $parts)), self::TOTAL_LIMIT, '…'));
    }

    public function htmlToText(string $html): string
    {
        $text = preg_replace('/<\s*(br|\/p|\/div|\/li|\/h[1-6])\s*>/i', "\n", $html);

        return $this->clean(trim(html_entity_decode(strip_tags($text ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8')));
    }

    /**
     * Chuẩn hóa về UTF-8 hợp lệ trước khi lưu MySQL (utf8mb4). Bộ trích PDF hay
     * sinh CESU-8 / lone surrogate (\xED\xA0..\xED\xBF..) — KHÔNG hợp lệ UTF-8 nên
     * MySQL báo lỗi 1366. `iconv //IGNORE` bỏ chuỗi byte lỗi, GIỮ emoji 4-byte hợp lệ.
     */
    public function clean(string $s): string
    {
        if ($s === '') {
            return '';
        }
        $out = @iconv('UTF-8', 'UTF-8//IGNORE', $s);
        if ($out === false) {
            $out = mb_convert_encoding($s, 'UTF-8', 'UTF-8'); // fallback: thay ký tự lỗi
        }

        // Bỏ ký tự điều khiển (trừ tab \t và xuống dòng \n).
        return preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $out) ?? $out;
    }

    /** $relativePath = đường dẫn trên disk `public` (vd kb-attachments/abc.pdf). */
    public function fromStoredFile(string $relativePath): string
    {
        try {
            if (! Storage::disk('public')->exists($relativePath)) {
                return '';
            }
            $abs = Storage::disk('public')->path($relativePath);
            $ext = strtolower(pathinfo($relativePath, PATHINFO_EXTENSION));

            return match ($ext) {
                'pdf' => $this->fromPdf($abs),
                'docx' => $this->fromDocx($abs),
                default => '', // doc (binary) & khác: bỏ qua
            };
        } catch (\Throwable $e) {
            Log::warning('KB extract failed', ['path' => $relativePath, 'error' => $e->getMessage()]);

            return '';
        }
    }

    private function fromPdf(string $abs): string
    {
        if (! class_exists(\Smalot\PdfParser\Parser::class)) {
            return '';
        }
        $parser = new \Smalot\PdfParser\Parser();
        $text = $this->clean($parser->parseFile($abs)->getText());

        return trim(preg_replace('/[ \t]+/', ' ', $text) ?? '');
    }

    private function fromDocx(string $abs): string
    {
        if (! class_exists(\ZipArchive::class)) {
            return '';
        }
        $zip = new \ZipArchive();
        if ($zip->open($abs) !== true) {
            return '';
        }
        $xml = $zip->getFromName('word/document.xml') ?: '';
        $zip->close();

        // Ngắt đoạn/hàng thành newline trước khi bỏ tag.
        $xml = preg_replace('/<\/w:p>/', "\n", $xml);
        $xml = preg_replace('/<w:tab\/?>/', "\t", $xml);

        return $this->htmlToText($xml);
    }
}
