<?php

namespace App\Http\Controllers\Docs;

use App\Http\Controllers\Controller;
use App\Models\DocPage;
use App\Models\DocPageRevision;
use App\Models\DocSpace;
use App\Support\Docs\DocsMarkdown;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
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

        $html = $page
            ? DocsMarkdown::toHtml($revision->body ?? $page->body)
            : '<p class="docs-empty">Không gian này chưa có nội dung.</p>';

        return view('docs.show', [
            'spaces' => $spaces,
            'space' => $space,
            'tree' => $tree,
            'page' => $page,
            'html' => $html,
            'revision' => $revision,
            'revisions' => $page ? $page->revisions()->get() : collect(),
            'breadcrumb' => $page ? $this->breadcrumb($page) : [],
        ]);
    }

    /** Tìm kiếm LIKE theo title/body trong các space được phép. */
    public function search(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $spaces = $this->visibleSpaces($request);
        $spaceIds = $spaces->pluck('id');

        $results = collect();
        if ($q !== '' && $spaceIds->isNotEmpty()) {
            $results = DocPage::query()
                ->whereIn('space_id', $spaceIds)
                ->where('status', 'published')
                ->where(function ($query) use ($q) {
                    $query->where('title', 'like', "%{$q}%")
                        ->orWhere('body', 'like', "%{$q}%");
                })
                ->with('space')
                ->limit(50)
                ->get();
        }

        return view('docs.search', [
            'spaces' => $spaces,
            'q' => $q,
            'results' => $results,
        ]);
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
