{{-- Cây trang trong 1 không gian. $nodes: collection DocPage; $space; $current --}}
<ul class="docs-nav">
    @foreach ($nodes as $node)
        @php $segs = implode('/', $node->pathSegments()); @endphp
        <li>
            <a href="{{ route('docs.show', ['space' => $space->key, 'path' => $segs], false) }}"
               class="{{ (isset($current) && $current && $current->id === $node->id) ? 'active' : '' }}">
                {{ $node->title }}
            </a>
            @if ($node->children->isNotEmpty())
                <div class="child">
                    @include('docs._tree', ['nodes' => $node->children, 'space' => $space, 'current' => $current ?? null])
                </div>
            @endif
        </li>
    @endforeach
</ul>
