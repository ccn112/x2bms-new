<?php

namespace App\Support\Docs;

use Illuminate\Support\Str;
use League\CommonMark\Extension\Autolink\AutolinkExtension;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\GithubFlavoredMarkdownExtension;
use League\CommonMark\Extension\Table\TableExtension;
use League\CommonMark\MarkdownConverter;
use League\CommonMark\Environment\Environment;

/**
 * Render markdown → HTML AN TOÀN cho reader tài liệu.
 * - html_input = strip: bỏ HTML thô nhúng trong markdown (chống XSS).
 * - allow_unsafe_links = false: chặn javascript:, data: ...
 * GitHub-flavored (bảng, task list, autolink) để khớp file .md trong repo.
 *
 * Phase 3: gán id (slug) cho heading h2/h3 và trả về danh sách headings để
 * dựng mục lục "Trong trang này" (cột phải). Slug hỗ trợ tiếng Việt qua Str::slug.
 */
class DocsMarkdown
{
    /** Render HTML thuần (không cần TOC). */
    public static function toHtml(?string $markdown): string
    {
        return static::render($markdown)['html'];
    }

    /**
     * Render + trích mục lục.
     *
     * @param  bool  $stripLeadingH1  Bỏ heading cấp 1 (# ...) ĐẦU TIÊN của body —
     *   tránh trùng với tiêu đề trang do template hiển thị. Các heading khác giữ nguyên.
     * @return array{html:string, headings:array<int,array{level:int,text:string,slug:string}>}
     */
    public static function render(?string $markdown, bool $stripLeadingH1 = false): array
    {
        if (blank($markdown)) {
            return ['html' => '', 'headings' => []];
        }

        if ($stripLeadingH1) {
            $markdown = static::stripLeadingH1($markdown);
        }

        $environment = new Environment([
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
            'max_nesting_level' => 50,
        ]);
        $environment->addExtension(new CommonMarkCoreExtension());
        $environment->addExtension(new GithubFlavoredMarkdownExtension());
        $environment->addExtension(new TableExtension());
        $environment->addExtension(new AutolinkExtension());

        $converter = new MarkdownConverter($environment);
        $html = (string) $converter->convert($markdown)->getContent();

        return static::injectHeadingIds($html);
    }

    /**
     * Bỏ heading cấp 1 (`# ...`) ĐẦU TIÊN của markdown (dòng nội dung đầu tiên).
     * Không đụng `##`/`###` hay các `#` xuất hiện sau đó. Bỏ qua dòng trống ở đầu.
     */
    protected static function stripLeadingH1(string $markdown): string
    {
        $lines = preg_split('/\r?\n/', $markdown);
        foreach ($lines as $i => $line) {
            if (trim($line) === '') {
                continue; // bỏ qua dòng trống đầu file
            }
            // Dòng nội dung đầu tiên: nếu là H1 (# ...) thì loại bỏ.
            if (preg_match('/^#\s+\S/', $line)) {
                unset($lines[$i]);
            }
            break; // chỉ xét dòng nội dung đầu tiên
        }

        return ltrim(implode("\n", $lines), "\n");
    }

    /**
     * Gán id cho các thẻ <h2>/<h3> trong HTML đã render và thu thập mục lục.
     * Dùng regex trên HTML do commonmark sinh ra (không có HTML thô nhờ strip).
     *
     * @return array{html:string, headings:array<int,array{level:int,text:string,slug:string}>}
     */
    protected static function injectHeadingIds(string $html): array
    {
        $headings = [];
        $used = [];

        $html = preg_replace_callback(
            '/<h([23])>(.*?)<\/h\1>/is',
            function (array $m) use (&$headings, &$used): string {
                $level = (int) $m[1];
                $inner = $m[2];
                $text = trim(html_entity_decode(strip_tags($inner), ENT_QUOTES | ENT_HTML5, 'UTF-8'));

                $base = Str::slug($text);
                if ($base === '') {
                    $base = 'muc';
                }

                // Bảo đảm slug duy nhất trong 1 trang.
                $slug = $base;
                $i = 2;
                while (isset($used[$slug])) {
                    $slug = $base.'-'.$i;
                    $i++;
                }
                $used[$slug] = true;

                $headings[] = ['level' => $level, 'text' => $text, 'slug' => $slug];

                return '<h'.$level.' id="'.$slug.'">'.$inner.'</h'.$level.'>';
            },
            $html
        );

        return ['html' => $html, 'headings' => $headings];
    }
}
