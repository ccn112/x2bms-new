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
        <a href="{{ route('docs.index') }}">Tài liệu</a>
        <span>/</span>
        <a href="{{ route('docs.show', ['space' => $space->key]) }}">{{ $space->title }}</a>
        @foreach ($breadcrumb as $b)
            <span>/</span>
            @if ($loop->last)
                <span>{{ $b['title'] }}</span>
            @else
                <a href="{{ $b['url'] }}">{{ $b['title'] }}</a>
            @endif
        @endforeach
    </nav>

    @if ($page)
        <div class="docs-toolbar">
            <div></div>
            @if ($revisions->count() > 1)
                <div class="docs-ver">
                    <label>Phiên bản:
                        <select onchange="if(this.value){location.search='?v='+this.value}else{location.search=''}">
                            <option value="">Mới nhất</option>
                            @foreach ($revisions as $rev)
                                <option value="{{ $rev->version }}" {{ ($revision && $revision->version === $rev->version) ? 'selected' : '' }}>
                                    v{{ $rev->version }} — {{ $rev->created_at?->format('d/m/Y H:i') }}
                                </option>
                            @endforeach
                        </select>
                    </label>
                </div>
            @endif
        </div>

        @if ($revision)
            <div class="docs-flag">
                Đang xem <strong>phiên bản cũ v{{ $revision->version }}</strong>
                ({{ $revision->created_at?->format('d/m/Y H:i') }}).
                <a href="{{ route('docs.show', ['space' => $space->key, 'path' => implode('/', $page->pathSegments())]) }}">Về bản mới nhất</a>
            </div>
        @endif
    @endif

    <article class="docs-content">
        {!! $html !!}
    </article>
@endsection
