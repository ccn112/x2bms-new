<?php

namespace App\Console\Commands;

use App\Models\DocPage;
use App\Models\DocSpace;
use App\Models\DocVersion;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Symfony\Component\Finder\Finder;

/**
 * Nạp tài liệu vào Docs CMS — NƠI CHÍNH THỨC xuất bản tài liệu dev + hướng dẫn
 * của CẢ 2 dự án (x2bms + x2mobile). Idempotent.
 *
 * Space + nguồn import khai báo ở config/docs.php ('spaces' + 'import_paths').
 * Mỗi nguồn:
 *   - 'space'                    : gom mọi .md của path vào 1 space.
 *   - 'mode' => 'guide_audience' : map thư mục con (bql/hq/sa → space cùng tên,
 *                                  còn lại → 'ops'). Dùng cho docs/guide của x2bms.
 * AN TOÀN: path không tồn tại (vd server không có x2mobile) → skip êm.
 */
class DocsImport extends Command
{
    protected $signature = 'docs:import {--fresh : Xóa trang cũ của các space import trước khi nạp lại}';

    protected $description = 'Nạp tài liệu (x2bms + x2mobile) vào Docs CMS (idempotent, đa nguồn)';

    /** Phiên bản sản phẩm mặc định gán cho trang import (v1.0). */
    private ?DocVersion $defaultVersion = null;

    /** @var array<string, DocSpace> space theo key */
    private array $spaces = [];

    public function handle(): int
    {
        $this->ensureDefaultVersion();
        $this->ensureSpaces();

        $imported = 0;
        $touchedSpaceIds = [];

        foreach ((array) config('docs.import_paths', []) as $entry) {
            $abs = $this->resolvePath($entry['path'] ?? '');
            if (! $abs || ! is_dir($abs)) {
                $this->warn("  skip (không tồn tại): {$entry['path']}");

                continue;
            }

            if (($entry['mode'] ?? null) === 'guide_audience') {
                $imported += $this->importGuideByAudience($abs, $touchedSpaceIds);
            } else {
                $spaceKey = $entry['space'] ?? null;
                if (! $spaceKey || ! isset($this->spaces[$spaceKey])) {
                    $this->warn("  skip (space '{$spaceKey}' chưa khai báo): {$entry['path']}");

                    continue;
                }
                $space = $this->spaces[$spaceKey];
                $touchedSpaceIds[$space->id] = true;
                $imported += $this->importDir($abs, $space, $abs);
            }
        }

        $this->info("docs:import xong. Đã nạp/cập nhật {$imported} trang; ".count($touchedSpaceIds)." space có nội dung / ".count($this->spaces)." space khai báo.");

        return self::SUCCESS;
    }

    /** Tạo phiên bản sản phẩm mặc định v1.0 (idempotent). */
    private function ensureDefaultVersion(): void
    {
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
    }

    /** Tạo/đồng bộ mọi space khai báo trong config (idempotent). --fresh: xóa trang cũ + reset is_public. */
    private function ensureSpaces(): void
    {
        foreach ((array) config('docs.spaces', []) as $key => $def) {
            $space = DocSpace::firstOrNew(['key' => $key]);
            $isNew = ! $space->exists;

            $space->fill([
                'title' => $def['title'] ?? $key,
                'description' => $def['desc'] ?? null,
                'audience' => $def['audience'] ?? 'dev',
                'sort' => $def['sort'] ?? 0,
                'is_published' => true,
            ]);
            // is_public: đặt mặc định khi tạo mới HOẶC khi --fresh → không ghi đè chỉnh tay.
            if ($isNew || $this->option('fresh')) {
                $space->is_public = (bool) ($def['is_public'] ?? false);
            }
            $space->save();

            if ($this->option('fresh')) {
                DocPage::where('space_id', $space->id)->forceDelete();
            }
            $this->spaces[$key] = $space;
        }
    }

    /** Giải path tương đối base_path() → tuyệt đối chuẩn hóa; null nếu không hợp lệ. */
    private function resolvePath(string $path): ?string
    {
        if ($path === '') {
            return null;
        }
        $abs = base_path($path);
        $real = realpath($abs);

        return $real !== false ? $real : $abs;
    }

    /** docs/guide của x2bms — map thư mục con (bql/hq/sa) → space cùng tên, còn lại → ops. */
    private function importGuideByAudience(string $guideDir, array &$touchedSpaceIds): int
    {
        $count = 0;
        $finder = (new Finder())->files()->in($guideDir)->name('*.md')->sortByName();
        foreach ($finder as $file) {
            $rel = str_replace('\\', '/', $file->getRelativePathname());
            if (Str::endsWith(strtoupper($rel), 'SUMMARY.MD')) {
                continue; // mục lục — không import thành trang.
            }
            $top = Str::before($rel, '/');
            $key = in_array($top, ['bql', 'hq', 'sa'], true) ? $top : 'ops';
            if (! isset($this->spaces[$key])) {
                continue;
            }
            $space = $this->spaces[$key];
            $touchedSpaceIds[$space->id] = true;
            $count += $this->importFile($file->getRealPath(), $space, $rel);
        }

        return $count;
    }

    /** Import mọi *.md trong 1 thư mục (đệ quy) vào 1 space. */
    private function importDir(string $dir, DocSpace $space, string $root): int
    {
        $count = 0;
        $finder = (new Finder())->files()->in($dir)->name('*.md')->sortByName();
        foreach ($finder as $file) {
            $rootNorm = str_replace('\\', '/', $root);
            $rel = ltrim(str_replace($rootNorm, '', str_replace('\\', '/', $file->getRealPath())), '/');
            if (Str::endsWith(strtoupper($rel), 'SUMMARY.MD')) {
                continue;
            }
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
