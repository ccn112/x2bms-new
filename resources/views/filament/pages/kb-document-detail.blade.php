<div class="space-y-5 text-sm">
    <div class="flex flex-wrap gap-2">
        <span class="rounded bg-slate-100 px-2 py-0.5 text-xs text-slate-600">{{ $typeMap[$record->document_type] ?? $record->document_type }}</span>
        <span class="rounded bg-blue-50 px-2 py-0.5 text-xs text-blue-700">{{ $scopeMap[$record->owner_scope] ?? $record->owner_scope }}</span>
        <span class="rounded bg-slate-100 px-2 py-0.5 text-xs text-slate-600">{{ ($sensitivityMap[$record->sensitivity] ?? [$record->sensitivity])[0] }}</span>
        <span class="rounded bg-slate-100 px-2 py-0.5 text-xs text-slate-600">{{ ($indexMap[$record->ai_index_status] ?? [$record->ai_index_status])[0] }}</span>
        <span class="rounded bg-slate-100 px-2 py-0.5 text-xs text-slate-600">v{{ $record->version }}</span>
    </div>

    @if ($record->description)<p class="font-medium text-slate-700">{{ $record->description }}</p>@endif

    <div class="rounded-xl border border-slate-100 p-4">
        <p class="mb-2 font-semibold text-slate-700">Nội dung</p>
        <pre class="max-h-56 overflow-auto whitespace-pre-wrap rounded-lg bg-slate-50 p-3 text-xs text-slate-600">{{ $record->content_markdown ?: 'Chưa có nội dung.' }}</pre>
    </div>

    <div class="rounded-xl border border-slate-100 p-4">
        <p class="mb-2 font-semibold text-slate-700">Phạm vi & quyền ({{ $record->scopes->count() }})</p>
        @forelse ($record->scopes as $sc)
            <div class="flex items-center justify-between border-b border-slate-50 py-1.5 last:border-0">
                <span>{{ $scopeMap[$sc->scope_type] ?? $sc->scope_type }}{{ $sc->scope_id ? ' #'.$sc->scope_id : '' }}</span>
                <span class="text-xs {{ $sc->permission === 'ai_read' ? 'text-green-600' : 'text-slate-400' }}">{{ $sc->permission }}</span>
            </div>
        @empty
            <p class="text-slate-400">Chưa cấu hình phạm vi (chỉ owner truy cập).</p>
        @endforelse
    </div>

    <dl class="grid grid-cols-2 gap-2 text-slate-500">
        <div><dt class="inline">Index lúc:</dt> <dd class="inline">{{ $record->ai_indexed_at?->format('d/m/Y H:i') ?? '—' }}</dd></div>
        <div><dt class="inline">Hiệu lực:</dt> <dd class="inline">{{ $record->effective_from?->format('d/m/Y') ?? '—' }} → {{ $record->effective_to?->format('d/m/Y') ?? '—' }}</dd></div>
    </dl>
</div>
