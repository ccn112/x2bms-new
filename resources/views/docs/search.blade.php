@extends('docs.layout')
@section('title', 'Tìm kiếm')

@section('sidebar')
    @include('docs._spaces')
@endsection

@section('content')
    <h1 style="font-family:'Plus Jakarta Sans',sans-serif;color:var(--navy);margin-top:0;">Kết quả tìm kiếm</h1>
    <p style="color:var(--muted);">Từ khóa: <strong>{{ $q ?: '(trống)' }}</strong> — {{ $results->count() }} kết quả</p>

    <ul class="docs-nav" style="padding-left:0;">
        @forelse ($results as $r)
            <li style="margin-bottom:6px;">
                <a href="{{ route('docs.show', ['space' => $r->space->key, 'path' => implode('/', $r->pathSegments())]) }}"
                   style="background:var(--card);border:1px solid var(--line);color:var(--ink);">
                    <strong>{{ $r->title }}</strong>
                    <span class="docs-badge">{{ $r->space->audience }}</span>
                    <div style="color:var(--muted);font-size:13px;">{{ \Illuminate\Support\Str::limit(strip_tags($r->body), 140) }}</div>
                </a>
            </li>
        @empty
            <li class="docs-empty">Không tìm thấy trang nào khớp.</li>
        @endforelse
    </ul>
@endsection
