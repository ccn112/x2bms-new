@extends('docs.layout')
@section('title', 'Tìm kiếm')

@section('sidebar')
    @include('docs._spaces')
@endsection

@section('content')
    <h1 style="font-family:'Plus Jakarta Sans',sans-serif;color:var(--navy);margin-top:0;">Kết quả tìm kiếm</h1>
    <p style="color:var(--muted);">Từ khóa: <strong>{{ $q ?: '(trống)' }}</strong> — {{ $results->count() }} kết quả</p>

    <ul class="docs-results">
        @forelse ($results as $r)
            @php
                $path = implode('/', $r->pathSegments());
                $anchor = $r->match_anchor ? '#'.$r->match_anchor : '';
                $href = route('docs.show', ['space' => $r->space->key, 'path' => $path], false).$anchor;
            @endphp
            <li>
                <a href="{{ $href }}" class="docs-result">
                    <div class="docs-result-head">
                        <strong>{{ $r->title }}</strong>
                        <span class="docs-badge">{{ $r->space->audience }}</span>
                        @if ($r->match_anchor)
                            <span class="docs-badge" style="background:rgba(37,99,235,.12);color:var(--blue);">khớp tiêu đề mục</span>
                        @endif
                    </div>
                    @if ($r->snippet)
                        <div class="docs-snippet">{!! $r->snippet !!}</div>
                    @endif
                    <div class="docs-result-path">{{ $r->space->title }} › /docs/{{ $r->space->key }}/{{ $path }}</div>
                </a>
            </li>
        @empty
            <li class="docs-empty">Không tìm thấy trang nào khớp.</li>
        @endforelse
    </ul>
@endsection
