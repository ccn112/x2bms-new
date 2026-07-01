<div class="space-y-5 text-sm">
    <div class="flex flex-wrap gap-2">
        <span class="rounded bg-slate-100 px-2 py-0.5 text-xs text-slate-600">{{ $typeMap[$record->template_type] ?? $record->template_type }}</span>
        <span class="rounded bg-blue-50 px-2 py-0.5 text-xs text-blue-700">{{ $scopeMap[$record->owner_scope] ?? $record->owner_scope }}</span>
        <span class="rounded bg-slate-100 px-2 py-0.5 text-xs text-slate-600">v{{ $record->version }} · {{ ($statusMap[$record->status] ?? [$record->status])[0] }}</span>
        <span class="rounded px-2 py-0.5 text-xs {{ $record->ai_readable ? 'bg-green-50 text-green-700' : 'bg-slate-100 text-slate-500' }}">{{ $record->ai_readable ? 'AI đọc được' : 'AI không đọc' }}</span>
    </div>

    @if ($record->description)<p class="font-medium text-slate-700">{{ $record->description }}</p>@endif

    <div class="rounded-xl border border-slate-100 p-4">
        <p class="mb-2 font-semibold text-slate-700">Nội dung mẫu</p>
        <pre class="max-h-56 overflow-auto whitespace-pre-wrap rounded-lg bg-slate-50 p-3 text-xs text-slate-600">{{ $record->body_markdown ?: 'Chưa có nội dung.' }}</pre>
    </div>

    <div class="grid grid-cols-2 gap-4">
        <div class="rounded-xl border border-slate-100 p-4">
            <p class="mb-2 font-semibold text-slate-700">Chia sẻ ({{ $record->shares->count() }})</p>
            @forelse ($record->shares as $s)
                <div class="flex items-center justify-between border-b border-slate-50 py-1.5 last:border-0">
                    <span>{{ $scopeMap[$s->to_scope] ?? $s->to_scope }}{{ $s->to_owner_id ? ' #'.$s->to_owner_id : '' }}</span>
                    <span class="text-xs text-slate-400">{{ $shareModeMap[$s->share_mode] ?? $s->share_mode }}</span>
                </div>
            @empty
                <p class="text-slate-400">Chưa chia sẻ.</p>
            @endforelse
        </div>
        <div class="rounded-xl border border-slate-100 p-4">
            <p class="mb-2 font-semibold text-slate-700">Clone ({{ $record->clones->count() }})</p>
            @forelse ($record->clones as $cl)
                <div class="flex items-center justify-between border-b border-slate-50 py-1.5 last:border-0">
                    <span>Mẫu #{{ $cl->cloned_template_id ?? '—' }}</span>
                    <span class="text-xs text-slate-400">{{ $cl->cloned_at?->format('d/m/Y') }}</span>
                </div>
            @empty
                <p class="text-slate-400">Chưa có bản clone.</p>
            @endforelse
        </div>
    </div>
</div>
