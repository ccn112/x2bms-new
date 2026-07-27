@extends('docs.layout')
@section('title', $page?->title ?? $space->title)

@section('sidebar')
    @include('docs._spaces')
    @if ($tree->isNotEmpty())
        <h4>{{ $space->title }}</h4>
        @include('docs._tree', ['nodes' => $tree, 'space' => $space, 'current' => $page])
    @endif
@endsection

@section('content')
    <nav class="docs-crumb">
        <a href="{{ route('docs.index', [], false) }}">Tài liệu</a>
        <span>/</span>
        <a href="{{ route('docs.show', ['space' => $space->key], false) }}">{{ $space->title }}</a>
        @foreach ($breadcrumb as $b)
            <span>/</span>
            @if ($loop->last)
                <span>{{ $b['title'] }}</span>
            @else
                <a href="{{ $b['url'] }}">{{ $b['title'] }}</a>
            @endif
        @endforeach
    </nav>

    <div class="docs-layout">
        <div class="docs-article">
            @if ($page)
                <div class="docs-pagehead">
                    @php
                        // Escape trước (an toàn), rồi biến `...` trong tiêu đề thành <code> để
                        // đoạn code hiển thị đúng kiểu code mà vẫn to tương ứng với H1.
                        $titleHtml = preg_replace('/`([^`]+)`/', '<code>$1</code>', e($page->title));
                    @endphp
                    <h1 class="docs-pagetitle">{!! $titleHtml !!}</h1>
                </div>

                {{-- Dòng thông tin: "Lịch sử sửa trang" (revision) + cập nhật + nút Sửa (nếu có quyền).
                     Đây là REVISION TỪNG TRANG — khác với "Phiên bản sản phẩm" ở sidebar. --}}
                <div class="docs-verline">
                    @if ($page->version)
                        <span class="docs-verpill">{{ $page->version->label }}</span>
                    @endif
                    {{-- Dropdown chọn bản sửa của trang — LUÔN hiển thị (disabled nếu chỉ 1 bản) --}}
                    <label class="docs-verselect">
                        <span class="docs-verlabel">Lịch sử sửa trang:</span>
                        <select
                            @if ($revisions->count() <= 1) disabled @endif
                            onchange="docsSetRevision(this.value)">
                            <option value="">Bản mới nhất (sửa lần {{ $latestVersion ?? 1 }})</option>
                            @foreach ($revisions as $rev)
                                <option value="{{ $rev->version }}" {{ ($revision && $revision->version === $rev->version) ? 'selected' : '' }}>
                                    Sửa lần {{ $rev->version }} — {{ $rev->created_at?->format('d/m/Y H:i') }}
                                </option>
                            @endforeach
                        </select>
                    </label>
                    @if ($updatedAt)
                        <span>· cập nhật {{ $updatedAt->format('d/m/Y') }}</span>
                    @endif

                    {{-- Feature 4: Sửa nhanh từ reader — deep link tới edit Filament /sa (ẩn với guest/không quyền) --}}
                    @auth
                        @can('docs.manage')
                            <a class="docs-edit-btn" style="margin-left:auto;"
                               href="{{ url('/sa/doc-pages/'.$page->id.'/edit') }}" target="_blank" rel="noopener">
                                ✎ Sửa trang
                            </a>
                        @endcan
                    @endauth
                </div>

                {{-- Gợi ý chuyển phiên bản sản phẩm khi mở trực tiếp trang thuộc version khác --}}
                @if (!empty($versionMismatch))
                    <div class="docs-flag" style="background:#eaf1ff;border-color:#b9cdf5;color:#1e3a8a;">
                        Trang này thuộc phiên bản <strong>{{ $versionMismatch->label }}</strong>
                        (bạn đang xem {{ $activeVersion?->label ?? '—' }}).
                        <a href="?ver={{ $versionMismatch->label }}">Chuyển sang {{ $versionMismatch->label }} →</a>
                    </div>
                @endif

                @if ($revision && $revision->version !== $latestVersion)
                    <div class="docs-flag">
                        Đang xem <strong>bản sửa cũ (lần {{ $revision->version }})</strong>
                        ({{ $revision->created_at?->format('d/m/Y H:i') }}).
                        <a href="{{ route('docs.show', ['space' => $space->key, 'path' => implode('/', $page->pathSegments())], false) }}">Về bản mới nhất →</a>
                    </div>
                @endif

                {{-- Mục lục gọn cho màn nhỏ (< lg) --}}
                @if (count($headings) > 1)
                    <details class="docs-toc-inline">
                        <summary>Trong trang này</summary>
                        <ul>
                            @foreach ($headings as $h)
                                <li><a class="{{ $h['level'] === 3 ? 'lvl3' : '' }}" href="#{{ $h['slug'] }}">{{ $h['text'] }}</a></li>
                            @endforeach
                        </ul>
                    </details>
                @endif
            @endif

            <article class="docs-content" id="docs-content">
                {!! $html !!}
            </article>
        </div>

        {{-- Cột phải: "Trong trang này" (On this page) — chỉ hiện khi có ≥2 heading --}}
        @if (count($headings) > 1)
            <aside class="docs-toc" id="docs-toc">
                <h5>Trong trang này</h5>
                <ul>
                    @foreach ($headings as $h)
                        <li><a class="{{ $h['level'] === 3 ? 'lvl3' : '' }}" href="#{{ $h['slug'] }}" data-toc="{{ $h['slug'] }}">{{ $h['text'] }}</a></li>
                    @endforeach
                </ul>
            </aside>
        @endif
    </div>

    @if (count($headings) > 1)
        <script>
            (function () {
                var links = Array.prototype.slice.call(document.querySelectorAll('#docs-toc a[data-toc]'));
                if (!links.length) return;
                var targets = links
                    .map(function (a) { return document.getElementById(a.getAttribute('data-toc')); })
                    .filter(Boolean);

                function setActive(id) {
                    links.forEach(function (a) {
                        a.classList.toggle('active', a.getAttribute('data-toc') === id);
                    });
                }

                // Scrollspy: heading gần đỉnh viewport nhất đang được xem.
                var observer = new IntersectionObserver(function (entries) {
                    var visible = entries
                        .filter(function (e) { return e.isIntersecting; })
                        .sort(function (a, b) { return a.boundingClientRect.top - b.boundingClientRect.top; });
                    if (visible.length) {
                        setActive(visible[0].target.id);
                    }
                }, { rootMargin: '0px 0px -70% 0px', threshold: 0 });

                targets.forEach(function (t) { observer.observe(t); });

                // Kích hoạt mục đầu tiên khi tải trang.
                setActive(targets[0].id);

                // Cuộn mượt khi bấm link mục lục.
                links.forEach(function (a) {
                    a.addEventListener('click', function (e) {
                        var el = document.getElementById(a.getAttribute('data-toc'));
                        if (el) {
                            e.preventDefault();
                            el.scrollIntoView({ behavior: 'smooth', block: 'start' });
                            history.replaceState(null, '', '#' + a.getAttribute('data-toc'));
                            setActive(a.getAttribute('data-toc'));
                        }
                    });
                });
            })();
        </script>
    @endif
@endsection
