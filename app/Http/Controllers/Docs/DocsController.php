<?php

namespace App\Http\Controllers\Docs;

use App\Http\Controllers\Controller;
use App\Models\DocPage;
use App\Models\DocPageRevision;
use App\Models\DocSpace;
use App\Support\Docs\DocsMarkdown;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Reader tài liệu kiểu GitBook.
 * - Space `is_public=true`: khách CHƯA đăng nhập vẫn xem được (chỉ trang published).
 * - Space nội bộ: yêu cầu login + quyền docs.view.{audience}.
 * super_admin bypass qua Gate::before nên thấy tất cả.
 */
class DocsController extends Controller
{
    /** Trang chủ /docs (hoặc landing site docs) — danh sách space guest/user được xem. */
    public function index(Request $request)
    {
        $spaces = $this->visibleSpaces($request);

        return view('docs.index', [
            'spaces' => $spaces,
        ]);
    }

    /** /docs/{space:key}/{path?} — hiển thị một trang trong space. */
    public function show(Request $request, DocSpace $space, ?string $path = null)
    {
        if (! $this->canView($request, $space)) {
            // Khách chưa đăng nhập gặp space nội bộ → điều hướng đăng nhập.
            if (! $request->user()) {
                return redirect()->guest(route('filament.admin.auth.login'));
            }

            // Đã đăng nhập nhưng không đủ quyền → 403.
            abort(Response::HTTP_FORBIDDEN);
        }

        $spaces = $this->visibleSpaces($request);
        $tree = $this->pageTree($space);

        // Resolve trang theo chuỗi slug (path phân cấp). Nếu không có path → trang đầu.
        $page = $path
            ? $this->resolvePageByPath($space, $path)
            : $this->firstPage($space);

        abort_if($path && ! $page, Response::HTTP_NOT_FOUND);

        // Xem version cũ nếu có ?v=
        $revision = null;
        if ($page && $request->filled('v')) {
            $revision = DocPageRevision::where('page_id', $page->id)
                ->where('version', (int) $request->query('v'))
                ->first();
        }

        // stripLeadingH1: bỏ H1 đầu body (trùng tiêu đề trang) — TOC h2/h3 giữ nguyên.
        $rendered = $page
            ? DocsMarkdown::render($revision->body ?? $page->body, stripLeadingH1: true)
            : ['html' => '<p class="docs-empty">Không gian này chưa có nội dung.</p>', 'headings' => []];

        $revisions = $page ? $page->revisions()->get() : collect();

        // Ngữ cảnh cho X2AI (chỉ dùng khi user đã đăng nhập + đủ quyền — xem layout).
        if ($page) {
            view()->share('x2aiContext', [
                'title' => 'Tài liệu · '.$space->title.' · '.$page->title,
                'surface' => 'docs',
            ]);
        }

        return view('docs.show', [
            'spaces' => $spaces,
            'space' => $space,
            'tree' => $tree,
            'page' => $page,
            'html' => $rendered['html'],
            'headings' => $rendered['headings'],
            'revision' => $revision,
            'revisions' => $revisions,
            // Phiên bản mới nhất (số + thời điểm) để hiện gần tiêu đề.
            'latestVersion' => $revisions->max('version'),
            'updatedAt' => $page?->updated_at,
            'breadcrumb' => $page ? $this->breadcrumb($page) : [],
        ]);
    }

    /**
     * Tìm kiếm full-text (MySQL FULLTEXT MATCH...AGAINST, boolean mode) trên
     * title+body; fallback LIKE nếu engine không hỗ trợ. Tôn trọng phân quyền:
     * chỉ trong space người dùng được xem + trang published.
     */
    public function search(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $spaces = $this->visibleSpaces($request);
        $spaceIds = $spaces->pluck('id');

        $results = collect();
        if ($q !== '' && $spaceIds->isNotEmpty()) {
            $base = DocPage::query()
                ->whereIn('space_id', $spaceIds)
                ->where('status', 'published')
                ->with('space');

            $driver = DocPage::query()->getConnection()->getDriverName();

            if ($driver === 'mysql') {
                // Boolean mode: mỗi từ khoá bọc +...* để yêu cầu + prefix-match.
                $boolean = collect(preg_split('/\s+/', $q))
                    ->filter()
                    ->map(fn ($t) => '+'.str_replace(['+', '-', '*', '"', '(', ')', '~', '<', '>', '@'], '', $t).'*')
                    ->implode(' ');

                $results = (clone $base)
                    ->whereRaw('MATCH(title, body) AGAINST (? IN BOOLEAN MODE)', [$boolean !== '' ? $boolean : $q])
                    ->orderByRaw('MATCH(title, body) AGAINST (?) DESC', [$q])
                    ->limit(50)
                    ->get();

                // Fallback LIKE nếu boolean mode không ra kết quả (vd từ quá ngắn / stopword).
                if ($results->isEmpty()) {
                    $results = $this->likeSearch(clone $base, $q);
                }
            } else {
                $results = $this->likeSearch($base, $q);
            }

            // Gắn snippet + highlight + anchor cho từng kết quả.
            $terms = collect(preg_split('/\s+/', $q))->filter()->values()->all();
            $results->each(function (DocPage $page) use ($terms) {
                $page->setAttribute('snippet', $this->buildSnippet($page->body, $terms));
                $page->setAttribute('match_anchor', $this->matchHeadingAnchor($page->body, $terms));
            });
        }

        return view('docs.search', [
            'spaces' => $spaces,
            'q' => $q,
            'results' => $results,
        ]);
    }

    /** Fallback LIKE (mỗi từ khoá đều phải xuất hiện ở title HOẶC body). */
    protected function likeSearch($query, string $q)
    {
        $terms = collect(preg_split('/\s+/', $q))->filter();

        return $query->where(function ($outer) use ($terms, $q) {
            if ($terms->isEmpty()) {
                $outer->where('title', 'like', "%{$q}%")->orWhere('body', 'like', "%{$q}%");

                return;
            }
            foreach ($terms as $t) {
                $outer->where(fn ($w) => $w->where('title', 'like', "%{$t}%")->orWhere('body', 'like', "%{$t}%"));
            }
        })->limit(50)->get();
    }

    /**
     * Snippet ngữ cảnh (~40 từ) quanh match đầu tiên, đã bỏ cú pháp markdown,
     * và highlight từ khoá bằng <mark>. Trả HTML AN TOÀN (escape trước, chèn mark sau).
     */
    protected function buildSnippet(?string $body, array $terms): string
    {
        $text = trim(preg_replace('/\s+/', ' ', strip_tags(DocsMarkdown::toHtml((string) $body))));
        if ($text === '') {
            return '';
        }

        // Vị trí match đầu tiên (không phân biệt hoa/thường, hỗ trợ tiếng Việt).
        $pos = null;
        foreach ($terms as $t) {
            $p = mb_stripos($text, $t);
            if ($p !== false) {
                $pos = ($pos === null) ? $p : min($pos, $p);
            }
        }
        $pos = $pos ?? 0;

        $start = max(0, $pos - 120);
        $snippet = mb_substr($text, $start, 300);
        if ($start > 0) {
            $snippet = '…'.$snippet;
        }
        if (mb_strlen($text) > $start + 300) {
            $snippet .= '…';
        }

        // Escape rồi highlight (case-insensitive) — tránh XSS.
        $safe = e($snippet);
        foreach ($terms as $t) {
            if ($t === '') {
                continue;
            }
            $safe = preg_replace_callback(
                '/'.preg_quote(e($t), '/').'/iu',
                fn ($m) => '<mark>'.$m[0].'</mark>',
                $safe
            );
        }

        return $safe;
    }

    /**
     * Nếu một từ khoá xuất hiện trong heading (## / ###) của trang → trả slug
     * heading đó để link thẳng tới anchor. Null nếu không có.
     */
    protected function matchHeadingAnchor(?string $body, array $terms): ?string
    {
        if (blank($body) || empty($terms)) {
            return null;
        }

        foreach (preg_split('/\r?\n/', $body) as $line) {
            if (preg_match('/^#{2,3}\s+(.+?)\s*$/u', $line, $m)) {
                $heading = $m[1];
                foreach ($terms as $t) {
                    if ($t !== '' && mb_stripos($heading, $t) !== false) {
                        return Str::slug($heading) ?: null;
                    }
                }
            }
        }

        return null;
    }

    // --- Helpers -----------------------------------------------------------

    /**
     * Space được xem: published + (public HOẶC user có quyền docs.view.{audience}).
     * Guest chỉ thấy space public; user thấy public + space theo quyền.
     */
    protected function visibleSpaces(Request $request): Collection
    {
        return DocSpace::query()
            ->where('is_published', true)
            ->orderBy('sort')
            ->orderBy('title')
            ->get()
            ->filter(fn (DocSpace $s) => $this->canView($request, $s))
            ->values();
    }

    protected function canView(Request $request, DocSpace $space): bool
    {
        if (! $space->is_published) {
            return false;
        }

        // Space công khai: ai cũng xem được (kể cả guest).
        if ($space->is_public) {
            return true;
        }

        // Space nội bộ: cần đăng nhập + quyền theo audience.
        $user = $request->user();

        return $user !== null && $user->can("docs.view.{$space->audience}");
    }

    /** Cây trang gốc → children (published). */
    protected function pageTree(DocSpace $space): Collection
    {
        return $space->rootPages()
            ->where('status', 'published')
            ->with(['children' => fn ($q) => $q->where('status', 'published')])
            ->get();
    }

    protected function firstPage(DocSpace $space): ?DocPage
    {
        return $space->pages()
            ->where('status', 'published')
            ->whereNull('parent_id')
            ->orderBy('sort')
            ->orderBy('title')
            ->first();
    }

    /** Đi theo chuỗi slug phân cấp để tìm đúng trang. */
    protected function resolvePageByPath(DocSpace $space, string $path): ?DocPage
    {
        $slugs = array_filter(explode('/', trim($path, '/')));
        $parentId = null;
        $page = null;

        foreach ($slugs as $slug) {
            $page = $space->pages()
                ->where('slug', $slug)
                ->where('parent_id', $parentId)
                ->first();

            if (! $page) {
                return null;
            }
            $parentId = $page->id;
        }

        return $page;
    }

    /** @return array<int, array{title:string, url:string}> */
    protected function breadcrumb(DocPage $page): array
    {
        // Xâu chuỗi từ gốc → trang hiện tại, mỗi mức là 1 URL phân cấp.
        $ancestors = [];
        $node = $page;
        while ($node) {
            array_unshift($ancestors, $node);
            $node = $node->parent;
        }

        $chain = [];
        $accumulated = [];
        foreach ($ancestors as $node) {
            $accumulated[] = $node->slug;
            $chain[] = [
                'title' => $node->title,
                // Relative URL (absolute:false) để giữ nguyên host đang duyệt
                // (host chính hoặc subdomain docs).
                'url' => route('docs.show', [
                    'space' => $page->space->key,
                    'path' => implode('/', $accumulated),
                ], false),
            ];
        }

        return $chain;
    }
}
