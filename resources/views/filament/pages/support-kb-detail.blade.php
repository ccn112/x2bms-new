<div class="space-y-4 text-sm">
    <div class="flex flex-wrap items-center gap-3">
        <span class="font-mono text-xs text-slate-500">{{ $record->code }}</span>
        <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[11px] text-slate-600">{{ $record->category?->name ?? '—' }}</span>
        <span class="text-[11px] text-slate-400">★ {{ number_format((float) $record->rating, 1) }} · {{ number_format($record->views) }} lượt xem · {{ $record->status }}</span>
    </div>
    <div class="prose prose-sm max-w-none text-slate-700">{!! $record->body !!}</div>
    <div class="text-[11px] text-slate-400">Tác giả: {{ $record->author?->name ?? '—' }} · Đăng: {{ $record->published_at?->format('d/m/Y') ?? '—' }}</div>
</div>
