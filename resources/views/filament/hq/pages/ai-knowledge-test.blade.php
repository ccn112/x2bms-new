<x-filament-panels::page>
<div class="space-y-6">
    <div><h1 class="font-title text-2xl font-bold text-slate-900">Kiểm tra AI trả lời có dẫn nguồn</h1>
        <p class="mt-1 text-sm text-slate-500">Chạy câu hỏi kiểm thử để xác minh AI trả lời chính xác và trích dẫn đúng nguồn tri thức.</p></div>
    <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm"><div class="text-xs text-slate-500">Số câu kiểm thử</div><div class="mt-1 text-2xl font-bold text-slate-900">{{ $total }}</div></div>
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm"><div class="text-xs text-slate-500">Có dẫn nguồn</div><div class="mt-1 text-2xl font-bold text-emerald-600">{{ $cited }}</div></div>
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm"><div class="text-xs text-slate-500">Tỷ lệ dẫn nguồn</div><div class="mt-1 text-2xl font-bold text-blue-600">{{ $rate }}%</div></div>
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm"><div class="text-xs text-slate-500">Điểm chính xác TB</div><div class="mt-1 text-2xl font-bold text-violet-600">{{ $avgScore }}</div></div>
    </div>
    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="flex gap-2">
            <input class="flex-1 rounded-lg border-slate-200 text-sm" placeholder="Nhập câu hỏi kiểm thử cho X2AI...">
            <button class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">Chạy kiểm thử</button>
        </div>
    </div>
    <div class="space-y-3">
        @foreach ($runs as $r)
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-start justify-between gap-4">
                    <div class="flex-1">
                        <div class="flex items-center gap-2">
                            <span class="grid h-6 w-6 place-items-center rounded-full bg-slate-100 text-xs">Q</span>
                            <span class="font-semibold text-slate-900">{{ $r['question'] }}</span>
                        </div>
                        <p class="mt-2 pl-8 text-sm text-slate-600">{{ $r['answer'] }}</p>
                        <div class="mt-2 flex flex-wrap items-center gap-2 pl-8">
                            @forelse ($r['sources'] as $src)
                                <span class="inline-flex items-center gap-1 rounded-md bg-blue-50 px-2 py-0.5 text-xs font-medium text-blue-700"><svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 0 1 1.242 7.244l-4.5 4.5a4.5 4.5 0 0 1-6.364-6.364l1.757-1.757"/></svg>{{ $src }}</span>
                            @empty
                                <span class="rounded-md bg-rose-50 px-2 py-0.5 text-xs font-medium text-rose-700">Không có trích dẫn nguồn</span>
                            @endforelse
                        </div>
                    </div>
                    <div class="text-right">
                        @if ($r['cited'])
                            <span class="rounded-md bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-700">✓ Đạt</span>
                        @else
                            <span class="rounded-md bg-rose-50 px-2 py-0.5 text-xs font-medium text-rose-700">✕ Thiếu nguồn</span>
                        @endif
                        <div class="mt-1 text-lg font-bold text-slate-900">{{ (int) $r['score'] }}<span class="text-xs font-normal text-slate-400">/100</span></div>
                        <div class="text-[11px] text-slate-400">{{ $r['at'] }}</div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>
</x-filament-panels::page>
