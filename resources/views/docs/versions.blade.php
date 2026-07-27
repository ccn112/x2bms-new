@extends('docs.layout')
@section('title', 'Lịch sử phiên bản & Backlog')

@section('sidebar')
    @include('docs._spaces')
@endsection

@section('content')
    <h1 style="font-family:'Plus Jakarta Sans',sans-serif;color:var(--navy);margin-top:0;">Lịch sử phiên bản & Backlog</h1>
    <p style="color:var(--muted);">Các đợt phát triển của tài liệu/sản phẩm và hạng mục kèm theo (mới nhất ở trên).</p>

    @php
        $catLabel = ['feature' => 'Tính năng mới', 'improvement' => 'Cải tiến', 'fix' => 'Sửa lỗi', 'change' => 'Thay đổi'];
        $catColor = ['feature' => '#16a34a', 'improvement' => '#2563eb', 'fix' => '#ef4444', 'change' => '#64748b'];
        $stLabel = ['done' => 'Hoàn thành', 'in_progress' => 'Đang làm', 'planned' => 'Dự kiến'];
        $verStLabel = ['released' => 'Đã phát hành', 'in_progress' => 'Đang làm', 'planned' => 'Dự kiến'];
    @endphp

    <div class="docs-timeline">
        @forelse ($versions as $v)
            <section class="docs-vcard">
                <div class="docs-vhead">
                    <span class="docs-verpill" style="font-size:14px;">{{ $v->label }}</span>
                    @if ($v->name)<span class="docs-vname">{{ $v->name }}</span>@endif
                    <span class="docs-vstatus docs-vstatus-{{ $v->status }}">{{ $verStLabel[$v->status] ?? $v->status }}</span>
                    @if ($v->released_at)<span class="docs-vdate">· {{ $v->released_at->format('d/m/Y') }}</span>@endif
                    @if ($v->is_current)<span class="docs-badge" style="background:rgba(213,163,49,.2);color:var(--gold);">hiện hành</span>@endif
                </div>
                @if ($v->summary)
                    <p class="docs-vsummary">{{ $v->summary }}</p>
                @endif

                @php $byCat = $v->items->groupBy('category'); @endphp
                @if ($v->items->isEmpty())
                    <p class="docs-empty">Chưa có hạng mục backlog.</p>
                @else
                    @foreach (['feature', 'improvement', 'change', 'fix'] as $cat)
                        @if ($byCat->has($cat))
                            <div class="docs-catgroup">
                                <h4 style="color:{{ $catColor[$cat] }};">{{ $catLabel[$cat] }}</h4>
                                <ul class="docs-items">
                                    @foreach ($byCat[$cat] as $it)
                                        <li>
                                            <span class="docs-itstatus docs-itstatus-{{ $it->status }}">{{ $stLabel[$it->status] ?? $it->status }}</span>
                                            <span class="docs-ittitle">{{ $it->title }}</span>
                                            @if ($it->ref_page_id && $it->refPage)
                                                <a class="docs-itlink" href="{{ route('docs.show', ['space' => $it->refPage->space->key, 'path' => implode('/', $it->refPage->pathSegments())], false) }}">↗ trang liên quan</a>
                                            @endif
                                            @if ($it->detail)
                                                <div class="docs-itdetail">{{ $it->detail }}</div>
                                            @endif
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    @endforeach
                @endif
            </section>
        @empty
            <p class="docs-empty">Chưa có phiên bản nào được công bố.</p>
        @endforelse
    </div>

    <style>
        .docs-timeline { margin-top: 20px; max-width: 820px; }
        .docs-vcard { border: 1px solid var(--line); border-left: 3px solid var(--gold); border-radius: 12px; padding: 18px 22px; margin-bottom: 18px; background: var(--card); }
        .docs-vhead { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
        .docs-vname { font-family: 'Plus Jakarta Sans', sans-serif; font-weight: 700; color: var(--navy); font-size: 16px; }
        .docs-vdate { color: var(--muted); font-size: 13px; }
        .docs-vstatus { font-size: 11px; padding: 2px 9px; border-radius: 999px; text-transform: uppercase; letter-spacing: .04em; }
        .docs-vstatus-released { background: #dcfce7; color: #15803d; }
        .docs-vstatus-in_progress { background: #fef3c7; color: #92600a; }
        .docs-vstatus-planned { background: #eef1f6; color: #64748b; }
        .docs-vsummary { color: var(--ink); margin: 10px 0 4px; }
        .docs-catgroup { margin-top: 14px; }
        .docs-catgroup h4 { font-family: 'Plus Jakarta Sans', sans-serif; font-size: 14px; margin: 0 0 6px; }
        .docs-items { list-style: none; margin: 0; padding: 0; }
        .docs-items li { padding: 6px 0; border-top: 1px dashed var(--line); }
        .docs-itstatus { font-size: 10px; padding: 1px 7px; border-radius: 999px; margin-right: 8px; }
        .docs-itstatus-done { background: #dcfce7; color: #15803d; }
        .docs-itstatus-in_progress { background: #fef3c7; color: #92600a; }
        .docs-itstatus-planned { background: #eef1f6; color: #64748b; }
        .docs-ittitle { color: var(--navy); font-weight: 500; }
        .docs-itlink { font-size: 12px; margin-left: 8px; }
        .docs-itdetail { color: var(--muted); font-size: 13px; margin-top: 3px; }
    </style>
@endsection
