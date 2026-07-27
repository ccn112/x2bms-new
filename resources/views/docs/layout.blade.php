<!DOCTYPE html>
<html lang="vi" class="docs-root">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Tài liệu') · X2-BMS</title>
    <link rel="stylesheet" href="/fonts/x2-fonts.css">
    <style>
        :root {
            --navy: #0b1b3f; --navy-2: #12264f; --gold: #d5a331; --blue: #2563eb;
            --ink: #1f2937; --muted: #6b7280; --line: #e5e7eb; --bg: #f7f8fa; --card: #fff;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0; background: var(--bg); color: var(--ink);
            font-family: 'Inter', system-ui, sans-serif; font-size: 15px; line-height: 1.65;
        }
        a { color: var(--blue); text-decoration: none; }
        a:hover { text-decoration: underline; }
        .docs-shell { display: grid; grid-template-columns: 300px 1fr; min-height: 100vh; }
        /* Sidebar */
        .docs-side {
            background: var(--navy); color: #cdd6e6; padding: 0; position: sticky; top: 0;
            height: 100vh; overflow-y: auto;
        }
        .docs-brand {
            display: flex; align-items: center; gap: 10px; padding: 18px 20px;
            font-family: 'Plus Jakarta Sans', sans-serif; font-weight: 700; color: #fff;
            font-size: 18px; border-bottom: 1px solid rgba(255,255,255,.08); position: sticky; top: 0;
            background: var(--navy);
        }
        .docs-brand .dot { width: 26px; height: 26px; border-radius: 7px; background: var(--gold); }
        .docs-search { padding: 14px 16px; }
        .docs-search input {
            width: 100%; padding: 9px 12px; border-radius: 8px; border: 1px solid rgba(255,255,255,.12);
            background: rgba(255,255,255,.06); color: #fff; font-size: 14px;
        }
        .docs-search input::placeholder { color: #8ea0c0; }
        /* Bộ chọn phiên bản sản phẩm (sidebar) */
        .docs-verpicker { padding: 0 16px 12px; font-size: 13px; }
        .docs-verpicker label { display: block; color: #8ea0c0; font-size: 11px; text-transform: uppercase; letter-spacing: .06em; margin-bottom: 4px; }
        .docs-verpicker select {
            width: 100%; padding: 8px 10px; border-radius: 8px; border: 1px solid rgba(255,255,255,.14);
            background: rgba(255,255,255,.06); color: #fff; font-size: 13px;
        }
        .docs-verpicker select option { color: #0b1b3f; }
        .docs-verlink { display: inline-block; margin-top: 8px; color: var(--gold); font-size: 12px; }
        .docs-side h4 {
            font-size: 11px; text-transform: uppercase; letter-spacing: .08em; color: #7f92b5;
            margin: 18px 20px 6px; font-weight: 700;
        }
        .docs-nav { list-style: none; margin: 0; padding: 0 8px 40px; }
        .docs-nav a {
            display: block; padding: 7px 14px; border-radius: 8px; color: #cdd6e6; font-size: 14px;
        }
        .docs-nav a:hover { background: rgba(255,255,255,.06); text-decoration: none; color: #fff; }
        .docs-nav a.active { background: var(--gold); color: var(--navy); font-weight: 600; }
        .docs-nav .child { padding-left: 16px; }
        .docs-nav .child a { font-size: 13.5px; color: #aab8d4; }
        .docs-space-link { display:flex; align-items:center; gap:8px; }
        .docs-badge {
            font-size: 10px; padding: 1px 7px; border-radius: 999px; background: rgba(213,163,49,.2);
            color: var(--gold); text-transform: uppercase; letter-spacing: .04em;
        }
        /* Content */
        .docs-main { padding: 34px 48px 80px; width: 100%; }
        .docs-crumb { font-size: 13px; color: var(--muted); margin-bottom: 14px; }
        .docs-crumb a { color: var(--muted); }
        .docs-toolbar { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 8px; flex-wrap: wrap; }
        .docs-ver { font-size: 13px; }
        .docs-ver select { padding: 6px 10px; border-radius: 8px; border: 1px solid var(--line); background: #fff; }
        .docs-flag {
            background: #fff7e6; border: 1px solid #f3d488; color: #8a6d1a; padding: 8px 14px;
            border-radius: 8px; font-size: 13px; margin-bottom: 16px;
        }
        /* 3-column show layout: content + right "On this page" rail */
        .docs-layout { display: grid; grid-template-columns: minmax(0, 1fr) 240px; gap: 44px; align-items: start; }
        /* Nội dung chạy full chiều rộng còn lại giữa sidebar trái và cột TOC phải */
        .docs-article { min-width: 0; width: 100%; }
        /* Page head — title + version line near the top */
        .docs-pagehead { margin-bottom: 6px; }
        .docs-verline {
            display: flex; align-items: center; gap: 10px; flex-wrap: wrap;
            font-size: 13px; color: var(--muted); margin: 6px 0 22px;
            padding-bottom: 14px; border-bottom: 1px solid var(--line);
        }
        .docs-verpill {
            background: rgba(37,99,235,.1); color: var(--blue); font-weight: 600;
            padding: 2px 10px; border-radius: 999px; font-size: 12px;
        }
        .docs-verline select { padding: 5px 9px; border-radius: 8px; border: 1px solid var(--line); background: #fff; font-size: 12.5px; }
        .docs-verline select:disabled { color: var(--muted); background: #f4f6fb; cursor: default; }
        .docs-verselect { display: inline-flex; align-items: center; gap: 6px; }
        .docs-verlabel { font-weight: 600; color: var(--navy); }
        /* Right TOC rail */
        .docs-toc {
            position: sticky; top: 24px; align-self: start; font-size: 13px;
            max-height: calc(100vh - 48px); overflow: auto; padding-left: 4px;
            border-left: 1px solid var(--line);
        }
        .docs-toc h5 {
            margin: 0 0 10px; padding-left: 12px; font-size: 11px; font-weight: 700;
            text-transform: uppercase; letter-spacing: .07em; color: var(--muted);
        }
        .docs-toc ul { list-style: none; margin: 0; padding: 0; }
        .docs-toc a {
            display: block; padding: 4px 12px; color: var(--muted); border-left: 2px solid transparent;
            margin-left: -1px; line-height: 1.4;
        }
        .docs-toc a:hover { color: var(--navy); text-decoration: none; }
        .docs-toc a.lvl3 { padding-left: 26px; font-size: 12.5px; }
        .docs-toc a.active { color: var(--blue); border-left-color: var(--blue); font-weight: 600; }
        .docs-content h2, .docs-content h3 { scroll-margin-top: 24px; }
        /* Compact inline TOC (small screens) */
        .docs-toc-inline { display: none; }
        /* Rendered markdown */
        .docs-content h1, .docs-content h2, .docs-content h3 {
            font-family: 'Plus Jakarta Sans', sans-serif; color: var(--navy); line-height: 1.3;
        }
        .docs-content h1 { font-size: 30px; margin: 0 0 18px; }
        .docs-content h2 { font-size: 23px; margin: 32px 0 12px; padding-bottom: 6px; border-bottom: 1px solid var(--line); }
        .docs-content h3 { font-size: 18px; margin: 24px 0 10px; }
        .docs-content p, .docs-content li { color: var(--ink); }
        .docs-content code {
            background: #eef1f6; padding: 2px 6px; border-radius: 5px; font-size: 13.5px;
            font-family: ui-monospace, 'SFMono-Regular', Menlo, monospace;
        }
        .docs-content pre {
            background: var(--navy); color: #e6edf9; padding: 16px 18px; border-radius: 10px;
            overflow-x: auto;
        }
        .docs-content pre code { background: none; color: inherit; padding: 0; }
        .docs-content table { border-collapse: collapse; width: 100%; margin: 16px 0; font-size: 14px; }
        .docs-content th, .docs-content td { border: 1px solid var(--line); padding: 8px 12px; text-align: left; }
        .docs-content th { background: #f0f3f8; }
        .docs-content blockquote {
            border-left: 4px solid var(--gold); margin: 16px 0; padding: 4px 16px; color: var(--muted); background: #fffdf6;
        }
        .docs-content img { max-width: 100%; border-radius: 8px; }
        /* Danh sách — khai báo tường minh (bền vững cả khi trang nạp CSS reset của app cho X2AI) */
        .docs-content ul { list-style: disc; margin: 12px 0; padding-left: 24px; }
        .docs-content ol { list-style: decimal; margin: 12px 0; padding-left: 24px; }
        .docs-content li { margin: 4px 0; }
        .docs-empty { color: var(--muted); font-style: italic; }
        /* Copy code button (Phase 4) */
        .docs-content pre { position: relative; }
        .docs-copy-btn {
            position: absolute; top: 8px; right: 8px; z-index: 2;
            background: rgba(255,255,255,.12); color: #e6edf9; border: 1px solid rgba(255,255,255,.2);
            border-radius: 6px; padding: 3px 10px; font-size: 12px; cursor: pointer;
            font-family: 'Inter', sans-serif; opacity: 0; transition: opacity .15s;
        }
        .docs-content pre:hover .docs-copy-btn { opacity: 1; }
        .docs-copy-btn:hover { background: rgba(255,255,255,.22); }
        .docs-copy-btn.copied { background: var(--gold); color: var(--navy); border-color: var(--gold); opacity: 1; }
        /* Search results (Phase 4) */
        .docs-results { list-style: none; margin: 8px 0 0; padding: 0; }
        .docs-results li { margin-bottom: 10px; }
        .docs-result {
            display: block; background: var(--card); border: 1px solid var(--line); border-radius: 12px;
            padding: 14px 16px; color: var(--ink); transition: box-shadow .15s, border-color .15s;
        }
        .docs-result:hover { text-decoration: none; box-shadow: 0 6px 18px rgba(11,27,63,.08); border-color: #c7d2e6; }
        .docs-result-head { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; margin-bottom: 4px; }
        .docs-result-head strong { color: var(--navy); font-family: 'Plus Jakarta Sans', sans-serif; }
        .docs-snippet { color: var(--muted); font-size: 13.5px; line-height: 1.55; }
        .docs-snippet mark { background: #fff2ac; color: inherit; padding: 0 2px; border-radius: 3px; }
        .docs-result-path { color: #9aa6bd; font-size: 12px; margin-top: 6px; }
        /* Edit-from-reader button (Phase 4) */
        .docs-edit-btn {
            display: inline-flex; align-items: center; gap: 6px; font-size: 13px;
            background: var(--gold); color: var(--navy); font-weight: 600; padding: 6px 14px;
            border-radius: 8px; text-decoration: none;
        }
        .docs-edit-btn:hover { text-decoration: none; filter: brightness(.96); }
        [x-cloak] { display: none !important; }
        /* Cards (index) */
        .docs-cards { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 16px; }
        .docs-card {
            background: var(--card); border: 1px solid var(--line); border-radius: 12px; padding: 18px 20px;
            transition: box-shadow .15s, transform .15s;
        }
        .docs-card:hover { box-shadow: 0 8px 24px rgba(11,27,63,.1); transform: translateY(-2px); }
        .docs-card h3 { margin: 0 0 6px; font-family: 'Plus Jakarta Sans', sans-serif; color: var(--navy); }
        .docs-card p { margin: 0; color: var(--muted); font-size: 14px; }
        .docs-menu-btn { display: none; }
        /* Responsive */
        @media (max-width: 1100px) {
            /* Ẩn cột phải, đưa mục lục thành khối gọn ở đầu nội dung */
            .docs-layout { grid-template-columns: 1fr; }
            .docs-toc { display: none; }
            .docs-toc-inline {
                display: block; background: #f0f3f8; border: 1px solid var(--line);
                border-radius: 10px; padding: 12px 16px; margin: 0 0 22px; font-size: 13px;
            }
            .docs-toc-inline summary { cursor: pointer; font-weight: 600; color: var(--navy); }
            .docs-toc-inline ul { list-style: none; margin: 10px 0 0; padding: 0; }
            .docs-toc-inline a { display: block; padding: 3px 0; color: var(--muted); }
            .docs-toc-inline a.lvl3 { padding-left: 16px; }
        }
        @media (max-width: 860px) {
            .docs-shell { grid-template-columns: 1fr; }
            .docs-side {
                position: fixed; z-index: 40; width: 280px; left: 0; top: 0; transform: translateX(-100%);
                transition: transform .2s;
            }
            .docs-root.nav-open .docs-side { transform: translateX(0); }
            .docs-main { padding: 20px; }
            .docs-menu-btn {
                display: inline-flex; position: fixed; z-index: 50; top: 12px; left: 12px;
                background: var(--navy); color: #fff; border: none; border-radius: 8px; padding: 8px 12px; cursor: pointer;
            }
            .docs-main { padding-top: 56px; }
        }
    </style>
</head>
<body>
    <button class="docs-menu-btn" onclick="document.documentElement.classList.toggle('nav-open')">☰ Mục lục</button>
    <div class="docs-shell">
        <aside class="docs-side">
            <a class="docs-brand" href="{{ route('docs.index', [], false) }}" style="color:#fff;text-decoration:none;"><span class="dot"></span> Tài liệu X2-BMS</a>
            <form class="docs-search" action="{{ route('docs.search', [], false) }}" method="get">
                <input type="search" name="q" placeholder="Tìm kiếm tài liệu…" value="{{ $q ?? '' }}">
            </form>

            {{-- Bộ chọn PHIÊN BẢN SẢN PHẨM (v1.0/v2.0) — lọc cây + nội dung theo version --}}
            @if (($docVersions ?? collect())->isNotEmpty())
                <div class="docs-verpicker">
                    <label>Phiên bản
                        <select onchange="docsSetVersion(this.value)">
                            @foreach ($docVersions as $dv)
                                <option value="{{ $dv->label }}" {{ (isset($activeVersion) && $activeVersion && $activeVersion->id === $dv->id) ? 'selected' : '' }}>
                                    {{ $dv->label }}{{ $dv->name ? ' — '.$dv->name : '' }}
                                </option>
                            @endforeach
                        </select>
                    </label>
                    <a href="{{ route('docs.versions', [], false) }}" class="docs-verlink">Lịch sử phiên bản & backlog →</a>
                </div>
            @endif

            @yield('sidebar')
            <div style="padding:14px 20px 28px;border-top:1px solid rgba(255,255,255,.08);margin-top:12px;font-size:13px;">
                @auth
                    <span style="color:#8ea0c0;">Xin chào, {{ auth()->user()->name }}</span>
                @else
                    <a href="{{ route('filament.admin.auth.login') }}" style="color:var(--gold);">Đăng nhập để xem tài liệu nội bộ →</a>
                @endauth
            </div>
        </aside>
        <main class="docs-main">
            @yield('content')
        </main>
    </div>

    {{-- Đổi phiên bản sản phẩm: set ?ver=<label> giữ nguyên đường dẫn hiện tại. --}}
    <script>
        function docsSetVersion(label) {
            var url = new URL(window.location.href);
            if (label) { url.searchParams.set('ver', label); } else { url.searchParams.delete('ver'); }
            url.searchParams.delete('v'); // bỏ tham số revision trang khi đổi version sản phẩm
            window.location.href = url.toString();
        }
        // Đổi bản sửa trang (revision) nhưng GIỮ nguyên phiên bản sản phẩm (?ver).
        function docsSetRevision(v) {
            var url = new URL(window.location.href);
            if (v) { url.searchParams.set('v', v); } else { url.searchParams.delete('v'); }
            window.location.href = url.toString();
        }
    </script>

    {{-- Copy code (Phase 4): gắn nút "Copy" cho mọi khối <pre> trong nội dung. JS thuần. --}}
    <script>
        (function () {
            var pres = document.querySelectorAll('.docs-content pre');
            pres.forEach(function (pre) {
                var btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'docs-copy-btn';
                btn.textContent = 'Copy';
                btn.addEventListener('click', function () {
                    var code = pre.querySelector('code');
                    var text = (code ? code.innerText : pre.innerText) || '';
                    var done = function () {
                        btn.textContent = 'Đã sao chép';
                        btn.classList.add('copied');
                        setTimeout(function () { btn.textContent = 'Copy'; btn.classList.remove('copied'); }, 1600);
                    };
                    if (navigator.clipboard && navigator.clipboard.writeText) {
                        navigator.clipboard.writeText(text).then(done, function () { fallback(text, done); });
                    } else {
                        fallback(text, done);
                    }
                });
                pre.appendChild(btn);
            });
            function fallback(text, done) {
                var ta = document.createElement('textarea');
                ta.value = text; ta.style.position = 'fixed'; ta.style.opacity = '0';
                document.body.appendChild(ta); ta.select();
                try { document.execCommand('copy'); done(); } catch (e) {}
                document.body.removeChild(ta);
            }
        })();
    </script>

    {{--
        X2AI chat trong reader (Phase 4) — CHỈ cho user đã đăng nhập + có quyền dùng
        (X2aiPolicyGate::canUse). GUEST/public KHÔNG bao giờ thấy chat và KHÔNG nạp
        endpoint/asset AI (tránh chi phí token + abuse). Tái dùng nguyên hạ tầng
        <x-x2.ai-fab> + Livewire x2ai-chat; ngữ cảnh trang được share qua $x2aiContext,
        nội dung trang tự bắt qua window.x2aiCaptureScreen (querySelector('main')).
    --}}
    @auth
        @if (app(\App\Support\X2AI\X2aiPolicyGate::class)->canUse(auth()->user()))
            @livewireStyles
            @vite('resources/css/app.css')
            <x-x2.ai-fab greeting="Xin chào! Hỏi tôi bất cứ điều gì về trang tài liệu bạn đang đọc." />
            @livewireScripts
        @endif
    @endauth
</body>
</html>
