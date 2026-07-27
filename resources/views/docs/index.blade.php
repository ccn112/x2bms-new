@extends('docs.layout')
@section('title', 'Tài liệu')

@section('sidebar')
    @include('docs._spaces')
@endsection

@section('content')
    <h1 style="font-family:'Plus Jakarta Sans',sans-serif;color:var(--navy);margin-top:0;">Trung tâm tài liệu</h1>
    <p style="color:var(--muted);">Chọn một không gian tài liệu để bắt đầu. Bạn chỉ thấy các không gian phù hợp với quyền của mình.</p>

    <div class="docs-cards" style="margin-top:24px;">
        @forelse ($spaces as $s)
            <a class="docs-card" href="{{ route('docs.show', ['space' => $s->key]) }}">
                <h3>{{ $s->title }} <span class="docs-badge">{{ $s->audience }}</span></h3>
                <p>{{ $s->description ?: 'Không gian tài liệu '.$s->key }}</p>
            </a>
        @empty
            <p class="docs-empty">Chưa có không gian tài liệu nào bạn được phép xem.</p>
        @endforelse
    </div>
@endsection
