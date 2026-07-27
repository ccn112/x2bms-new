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
        .docs-main { padding: 34px 48px 80px; max-width: 900px; }
        .docs-crumb { font-size: 13px; color: var(--muted); margin-bottom: 14px; }
        .docs-crumb a { color: var(--muted); }
        .docs-toolbar { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 8px; flex-wrap: wrap; }
        .docs-ver { font-size: 13px; }
        .docs-ver select { padding: 6px 10px; border-radius: 8px; border: 1px solid var(--line); background: #fff; }
        .docs-flag {
            background: #fff7e6; border: 1px solid #f3d488; color: #8a6d1a; padding: 8px 14px;
            border-radius: 8px; font-size: 13px; margin-bottom: 16px;
        }
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
        .docs-empty { color: var(--muted); font-style: italic; }
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
</body>
</html>
