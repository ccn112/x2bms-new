{{-- Danh sách không gian tài liệu (đã lọc quyền) --}}
<h4>Không gian</h4>
<ul class="docs-nav">
    @forelse ($spaces as $s)
        <li>
            <a href="{{ route('docs.show', ['space' => $s->key]) }}"
               class="docs-space-link {{ (isset($space) && $space->id === $s->id) ? 'active' : '' }}">
                <span>{{ $s->title }}</span>
                <span class="docs-badge">{{ $s->audience }}</span>
            </a>
        </li>
    @empty
        <li><a>Chưa có không gian nào bạn được xem.</a></li>
    @endforelse
</ul>
