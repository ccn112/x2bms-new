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
                    <h1 style="font-family:'Plus Jakarta Sans',sans-serif;color:var(--navy);margin:0;">{{ $page->title }}</h1>
                </div>

                {{-- Phiên bản + bộ chọn version, ngay dưới tiêu đề --}}
                <div class="docs-verline">
                    @if ($latestVersion)
                        <span class="docs-verpill">Phiên bản {{ $revision->version ?? $latestVersion }}</span>
                    @endif
                    @if ($updatedAt)
                        <span>· cập nhật {{ $updatedAt->format('d/m/Y') }}</span>
                    @endif

                    @if ($revisions->count() > 1)
                        <span style="margin-left:auto;">
                            <label>Xem phiên bản:
                                <select onchange="if(this.value){location.search='?v='+this.value}else{location.search=''}">
                                    <option value="">Mới nhất (v{{ $latestVersion }})</option>
                                    @foreach ($revisions as $rev)
                                        <option value="{{ $rev->version }}" {{ ($revision && $revision->version === $rev->version) ? 'selected' : '' }}>
                                            v{{ $rev->version }} — {{ $rev->created_at?->format('d/m/Y H:i') }}
                                        </option>
                                    @endforeach
                                </select>
                            </label>
                        </span>
                    @endif
                </div>

                @if ($revision && $revision->version !== $latestVersion)
                    <div class="docs-flag">
                        Đang xem <strong>phiên bản cũ v{{ $revision->version }}</strong>
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
