<?php

namespace App\Support\Docs;

use Highlight\Highlighter;
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

        $html = static::styleCodeBlocks($html);

        return static::injectHeadingIds($html);
    }

    /**
     * Biến `<pre><code class="language-xxx">…</code></pre>` (do commonmark sinh)
     * thành CARD đẹp: header (tên ngôn ngữ + nút Copy) + gutter số dòng + code tô
     * màu cú pháp (server-side, scrivo/highlight.php — PHP port của highlight.js).
     * Block không có ngôn ngữ → card + số dòng, không tô màu.
     * Copy lấy đúng source qua data-code (không kèm số dòng).
     */
    protected static function styleCodeBlocks(string $html): string
    {
        return (string) preg_replace_callback(
            '#<pre><code(?:\s+class="language-([^"]*)")?>(.*?)</code></pre>#s',
            function (array $m): string {
                $langHint = trim($m[1] ?? '');
                // Code do commonmark escape (& < > ") → giải mã lấy source thật.
                $raw = html_entity_decode($m[2], ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $raw = rtrim($raw, "\n");

                [$codeHtml, $langLabel] = static::highlightCode($raw, $langHint);

                $lineCount = max(1, substr_count($raw, "\n") + 1);
                $gutter = '';
                for ($i = 1; $i <= $lineCount; $i++) {
                    $gutter .= '<span>'.$i.'</span>';
                }

                $label = $langLabel !== '' ? strtoupper($langLabel) : 'CODE';
                $dataCode = htmlspecialchars($raw, ENT_QUOTES, 'UTF-8');

                return '<div class="docs-code" data-code="'.$dataCode.'">'
                    .'<div class="docs-code-head">'
                        .'<span class="docs-code-lang">'.$label.'</span>'
                        .'<button type="button" class="docs-copy-btn">Copy</button>'
                    .'</div>'
                    .'<div class="docs-code-body">'
                        .'<span class="docs-code-gutter" aria-hidden="true">'.$gutter.'</span>'
                        .'<pre class="docs-code-scroll"><code class="hljs">'.$codeHtml.'</code></pre>'
                    .'</div>'
                .'</div>';
            },
            $html
        );
    }

    /**
     * Tô màu cú pháp 1 khối code. Trả [html_đã_tô_màu, nhãn_ngôn_ngữ].
     * Ngôn ngữ không xác định / không hỗ trợ → escape thường, nhãn rỗng.
     *
     * @return array{0:string,1:string}
     */
    protected static function highlightCode(string $raw, string $langHint): array
    {
        // Chuẩn hoá alias → tên highlight.js.
        $aliases = [
            'sh' => 'bash', 'shell' => 'bash', 'zsh' => 'bash', 'console' => 'bash',
            'js' => 'javascript', 'ts' => 'typescript', 'yml' => 'yaml',
            'md' => 'markdown', 'html' => 'xml', 'jsonc' => 'json',
        ];
        $lang = strtolower($langHint);
        $lang = $aliases[$lang] ?? $lang;

        if ($lang === '') {
            return [htmlspecialchars($raw, ENT_QUOTES, 'UTF-8'), ''];
        }

        try {
            $hl = new Highlighter();
            $result = $hl->highlight($lang, $raw);

            return [$result->value, $result->language ?: $langHint];
        } catch (\Throwable) {
            // Ngôn ngữ lạ hoặc lỗi → không tô màu, vẫn hiển thị card.
            return [htmlspecialchars($raw, ENT_QUOTES, 'UTF-8'), $langHint];
        }
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
