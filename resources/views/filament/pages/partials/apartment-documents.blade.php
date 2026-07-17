@php $documents = $documents ?? []; @endphp

@if (count($documents))
    <ul class="space-y-2">
        @foreach ($documents as $d)
            <li class="flex items-center justify-between gap-3 rounded-lg border border-slate-100 px-3 py-2">
                <div class="flex min-w-0 items-center gap-2.5">
                    <span class="grid h-9 w-9 shrink-0 place-items-center rounded-lg text-[10px] font-bold {{ ($d['type'] ?? '') === 'PDF' ? 'bg-red-50 text-red-500' : 'bg-sky-50 text-sky-500' }}">{{ $d['type'] ?? 'FILE' }}</span>
                    <div class="min-w-0">
                        <p class="truncate text-sm font-medium text-slate-800">{{ $d['name'] ?? '—' }}</p>
                        <p class="text-xs text-slate-400">{{ $d['type'] ?? '' }} · {{ $d['size'] ?? '' }} · {{ $d['date'] ?? '' }}</p>
                    </div>
                </div>
                <button type="button" title="Tải xuống" class="shrink-0 text-slate-400 hover:text-x2-primary">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
                </button>
            </li>
        @endforeach
    </ul>
@else
    <p class="text-sm text-slate-400">Chưa có tài liệu.</p>
@endif
