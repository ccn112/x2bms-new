@extends('docs.layout')
@section('title', 'Tài liệu')

@section('sidebar')
    @include('docs._spaces')
@endsection

@section('content')
    <h1 style="font-family:'Plus Jakarta Sans',sans-serif;color:var(--navy);margin-top:0;">Trung tâm tài liệu X2-BMS</h1>
    <p style="color:var(--muted);">
        @auth
            Chọn một không gian tài liệu để bắt đầu. Bạn thấy các không gian công khai và những không gian nội bộ phù hợp với quyền của mình.
        @else
            Chọn một không gian để bắt đầu. Một số tài liệu nội bộ yêu cầu <a href="{{ route('filament.admin.auth.login') }}">đăng nhập</a>.
        @endauth
    </p>

    <div class="docs-cards" style="margin-top:24px;">
        @forelse ($spaces as $s)
            <a class="docs-card" href="{{ route('docs.show', ['space' => $s->key], false) }}">
                <h3>
                    {{ $s->title }}
                    <span class="docs-badge">{{ $s->audience }}</span>
                    @if ($s->is_public)
                        <span class="docs-badge" style="background:rgba(37,99,235,.15);color:var(--blue);">công khai</span>
                    @endif
                </h3>
                <p>{{ $s->description ?: 'Không gian tài liệu '.$s->key }}</p>
            </a>
        @empty
            <p class="docs-empty">Chưa có không gian tài liệu công khai nào.</p>
        @endforelse
    </div>
@endsection
