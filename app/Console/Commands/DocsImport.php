<?php

namespace App\Console\Commands;

use App\Models\DocPage;
use App\Models\DocSpace;
use App\Models\DocVersion;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Symfony\Component\Finder\Finder;

/**
 * Seed các không gian/trang tài liệu từ file .md trong repo (idempotent).
 * Đọc docs/dev/**\/*.md và docs/guide/**\/*.md. Map audience theo thư mục:
 *   dev/*        → dev
 *   guide/bql/*  → bql
 *   guide/hq/*   → hq
 *   guide/sa/*   → sa
 *   guide (gốc)  → ops
 * Một space mỗi (audience). Trang import phẳng theo thư mục con; slug từ path.
 */
class DocsImport extends Command
{
    protected $signature = 'docs:import {--fresh : Xóa trang cũ của các space import trước khi nạp lại}';

    protected $description = 'Nạp tài liệu từ docs/dev và docs/guide vào module Tài liệu (idempotent)';

    /**
     * `public` = mặc định is_public khi TẠO MỚI space (không ghi đè nếu admin đã
     * chỉnh tay trên Filament). ops = hướng dẫn vận hành → công khai; dev + guide
     * theo vai trò (bql/hq/sa) = nội bộ, yêu cầu đăng nhập.
     *
     * @var array<string, array{title:string, key:string, desc:string, sort:int, public:bool}>
     */
    private array $spaceDefs = [
        'dev' => ['title' => 'Tài liệu phát triển (Dev)', 'key' => 'dev', 'desc' => 'UI/UX, tính năng, kiến trúc & DB — nội bộ dev.', 'sort' => 10, 'public' => false],
        'ops' => ['title' => 'Vận hành & Tích hợp', 'key' => 'ops', 'desc' => 'Chạy backend, mobile API, triển khai & mở rộng.', 'sort' => 20, 'public' => true],
        'bql' => ['title' => 'Hướng dẫn Ban Quản Lý (BQL)', 'key' => 'bql', 'desc' => 'Hướng dẫn nghiệp vụ cho Ban Quản lý dự án.', 'sort' => 30, 'public' => false],
        'hq' => ['title' => 'Hướng dẫn Cổng Công ty (HQ)', 'key' => 'hq', 'desc' => 'Hướng dẫn cho cổng công ty vận hành.', 'sort' => 40, 'public' => false],
        'sa' => ['title' => 'Hướng dẫn SuperAdmin', 'key' => 'sa', 'desc' => 'Hướng dẫn cho nhà cung cấp nền tảng.', 'sort' => 50, 'public' => false],
    ];

    /** Phiên bản sản phẩm mặc định gán cho trang import (v1.0). */
    private ?DocVersion $defaultVersion = null;

    public function handle(): int
    {
        $base = base_path('docs');
        if (! is_dir($base)) {
            $this->error("Không tìm thấy thư mục docs tại {$base}");

            return self::FAILURE;
        }

        // Phiên bản sản phẩm mặc định v1.0 (idempotent). Đặt hiện hành nếu chưa có version nào.
        $this->defaultVersion = DocVersion::firstOrNew(['label' => 'v1.0']);
        if (! $this->defaultVersion->exists) {
            $this->defaultVersion->fill([
                'name' => 'Ra mắt',
                'status' => 'released',
                'released_at' => now()->toDateString(),
                'is_current' => DocVersion::where('is_current', true)->doesntExist(),
                'sort' => 10,
                'summary' => 'Phiên bản đầu tiên của bộ tài liệu X2-BMS.',
            ])->save();
        }

        $spaces = [];
        foreach ($this->spaceDefs as $aud => $def) {
            $space = DocSpace::firstOrNew(['key' => $def['key']]);
            $isNew = ! $space->exists;

            $space->fill([
                'title' => $def['title'],
                'description' => $def['desc'],
                'audience' => $aud,
                'sort' => $def['sort'],
                'is_published' => true,
            ]);
            // is_public: đặt mặc định khi tạo mới HOẶC khi chạy --fresh (reset) →
            // không ghi đè chỉnh tay của admin ở lần import thường.
            if ($isNew || $this->option('fresh')) {
                $space->is_public = $def['public'];
            }
            $space->save();

            if ($this->option('fresh')) {
                DocPage::where('space_id', $space->id)->forceDelete();
            }
            $spaces[$aud] = $space;
        }

        $imported = 0;

        // 1) docs/dev/**/*.md → space dev.
        $imported += $this->importDir($base.'/dev', $spaces['dev'], $base.'/dev');

        // 2) docs/guide/**/*.md → audience theo thư mục con.
        $guide = $base.'/guide';
        if (is_dir($guide)) {
            $finder = (new Finder())->files()->in($guide)->name('*.md')->sortByName();
            foreach ($finder as $file) {
                $rel = str_replace('\\', '/', $file->getRelativePathname());
                if (Str::endsWith(strtoupper($rel), 'SUMMARY.MD')) {
                    continue; // mục lục — không import thành trang.
                }
                $top = Str::before($rel, '/');
                $aud = in_array($top, ['bql', 'hq', 'sa'], true) ? $top : 'ops';
                $imported += $this->importFile($file->getRealPath(), $spaces[$aud], $rel);
            }
        }

        $this->info("docs:import xong. Đã nạp/cập nhật {$imported} trang trên ".count($spaces)." không gian.");

        return self::SUCCESS;
    }

    /** Import mọi *.md trong 1 thư mục (đệ quy) vào 1 space. */
    private function importDir(string $dir, DocSpace $space, string $root): int
    {
        if (! is_dir($dir)) {
            return 0;
        }
        $count = 0;
        $finder = (new Finder())->files()->in($dir)->name('*.md')->sortByName();
        foreach ($finder as $file) {
            $rel = str_replace('\\', '/', ltrim(str_replace(str_replace('\\', '/', $root), '', str_replace('\\', '/', $file->getRealPath())), '/'));
            $count += $this->importFile($file->getRealPath(), $space, $rel);
        }

        return $count;
    }

    /** Tạo/cập nhật 1 trang từ file .md. Slug ổn định theo relative path (phẳng). */
    private function importFile(string $absPath, DocSpace $space, string $relPath): int
    {
        $body = (string) file_get_contents($absPath);
        $title = $this->extractTitle($body) ?: Str::of(pathinfo($relPath, PATHINFO_FILENAME))->replace(['-', '_'], ' ')->title();

        // slug phẳng, duy nhất trong space: bỏ .md, thay / bằng -.
        $slug = Str::slug(str_replace('/', '-', preg_replace('/\.md$/i', '', $relPath)));
        if ($slug === '') {
            $slug = Str::slug($title) ?: 'trang';
        }

        DocPage::updateOrCreate(
            ['space_id' => $space->id, 'parent_id' => null, 'slug' => $slug],
            ['title' => (string) $title, 'body' => $body, 'status' => 'published', 'sort' => 0,
             'version_id' => $this->defaultVersion?->id],
        );

        $this->line("  + [{$space->key}] {$slug}  ({$title})");

        return 1;
    }

    private function extractTitle(string $md): ?string
    {
        foreach (preg_split('/\r?\n/', $md) as $line) {
            if (preg_match('/^#\s+(.+?)\s*$/', $line, $m)) {
                return trim($m[1]);
            }
        }

        return null;
    }
}
