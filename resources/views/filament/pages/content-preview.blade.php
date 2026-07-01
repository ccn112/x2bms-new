<div class="space-y-4 text-sm">
    @if ($record->cover_image)
        <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($record->cover_image) }}"
             alt="cover" class="max-h-56 w-full rounded-xl object-cover" />
    @endif

    <div class="flex flex-wrap gap-2">
        <span class="rounded bg-slate-100 px-2 py-0.5 text-xs text-slate-600">{{ $typeMap[$record->content_type] ?? $record->content_type }}</span>
        <span class="rounded bg-blue-50 px-2 py-0.5 text-xs text-blue-700">{{ $scopeMap[$record->publish_scope] ?? $record->publish_scope }}</span>
        <span class="rounded bg-slate-100 px-2 py-0.5 text-xs text-slate-600">{{ ($statusMap[$record->status] ?? [$record->status])[0] }}</span>
        <span class="rounded bg-slate-100 px-2 py-0.5 text-xs text-slate-600">{{ strtoupper($record->language) }}</span>
    </div>

    @if ($record->summary)
        <p class="font-medium text-slate-700">{{ $record->summary }}</p>
    @endif

    <div class="prose prose-sm max-w-none text-slate-600">
        {!! $record->body ?: '<p class="text-slate-400">Chưa có nội dung.</p>' !!}
    </div>

    <dl class="grid grid-cols-2 gap-2 border-t border-slate-100 pt-3 text-slate-500">
        <div><dt class="inline">Danh mục:</dt> <dd class="inline">{{ $record->category?->name ?? '—' }}</dd></div>
        <div><dt class="inline">Tác giả:</dt> <dd class="inline">{{ $record->creator?->name ?? '—' }}</dd></div>
        <div><dt class="inline">Ngày đăng:</dt> <dd class="inline">{{ $record->published_at?->format('d/m/Y') ?? '—' }}</dd></div>
        <div><dt class="inline">Hết hạn:</dt> <dd class="inline">{{ $record->expired_at?->format('d/m/Y') ?? '—' }}</dd></div>
    </dl>
</div>
