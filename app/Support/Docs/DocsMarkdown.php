<?php

namespace App\Support\Docs;

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
 */
class DocsMarkdown
{
    public static function toHtml(?string $markdown): string
    {
        if (blank($markdown)) {
            return '';
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

        return (string) $converter->convert($markdown);
    }
}
