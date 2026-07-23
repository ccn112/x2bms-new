@php
    $levelMeta = [
        'policy_block' => ['Chặn duyệt', 'red'],
        'high_risk' => ['Rủi ro cao', 'red'],
        'warning' => ['Cảnh báo', 'amber'],
        'info' => ['Thông tin', 'slate'],
    ];
    $tileClass = [
        'red' => 'border-red-200 bg-red-50 text-red-700 dark:border-red-500/30 dark:bg-red-500/10 dark:text-red-300',
        'amber' => 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-300',
        'slate' => 'border-slate-200 bg-slate-50 text-slate-600 dark:border-white/10 dark:bg-white/5 dark:text-slate-300',
    ];
    $chipClass = [
        'red' => 'bg-red-50 text-red-600 dark:bg-red-500/10 dark:text-red-300',
        'amber' => 'bg-amber-50 text-amber-600 dark:bg-amber-500/10 dark:text-amber-300',
        'slate' => 'bg-slate-100 text-slate-500 dark:bg-white/10 dark:text-slate-300',
    ];
@endphp

<x-filament-panels::page>
    <p class="text-sm text-slate-500 dark:text-slate-400">
        Tổng hợp cảnh báo rule-based từ 4 luồng duyệt. Con người quyết định — hệ thống chỉ gợi ý (không dùng LLM).
    </p>

    {{-- Summary tiles theo mức --}}
    <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
        @foreach ($levelMeta as $lvl => [$lbl, $tone])
            <div class="rounded-2xl border p-4 {{ $tileClass[$tone] }}">
                <div class="text-2xl font-bold">{{ number_format($tally[$lvl] ?? 0) }}</div>
                <div class="text-sm font-medium">{{ $lbl }}</div>
            </div>
        @endforeach
    </div>

    {{-- Nguồn cảnh báo --}}
    <div class="grid grid-cols-1 gap-4 xl:grid-cols-2">
        @foreach ($sources as $s)
            <div class="flex flex-col rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-white/10 dark:bg-gray-900">
                <div class="flex items-center justify-between border-b border-slate-100 px-5 py-3 dark:border-white/10">
                    <div class="flex items-center gap-2.5">
                        <x-dynamic-component :component="$s['icon']" class="h-5 w-5 text-x2-primary" />
                        <h3 class="text-base font-semibold text-slate-900 dark:text-white">{{ $s['label'] }}</h3>
                        <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-500 dark:bg-white/10 dark:text-slate-300">{{ $s['flagged_count'] }} cần chú ý</span>
                    </div>
                    <a href="{{ $s['url'] }}" class="text-sm font-medium text-x2-primary hover:underline">Mở màn →</a>
                </div>
                <div class="divide-y divide-slate-50 dark:divide-white/5">
                    @forelse ($s['top'] as $item)
                        <a href="{{ $item['url'] }}" class="flex items-start justify-between gap-3 px-5 py-3 hover:bg-slate-50/70 dark:hover:bg-white/5">
                            <div class="min-w-0">
                                <div class="truncate font-medium text-slate-800 dark:text-slate-100">{{ $item['name'] }}</div>
                                <div class="mt-1 flex flex-wrap gap-1">
                                    @foreach ($item['findings'] as $f)
                                        <span class="inline-flex rounded-md px-2 py-0.5 text-xs font-medium {{ $chipClass[$f['tone']] ?? $chipClass['slate'] }}">{{ $f['label'] }}</span>
                                    @endforeach
                                </div>
                            </div>
                            <svg class="mt-1 h-4 w-4 shrink-0 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                        </a>
                    @empty
                        <div class="px-5 py-8 text-center text-sm text-x2-green">Không có cảnh báo 🎉</div>
                    @endforelse
                </div>
            </div>
        @endforeach
    </div>
</x-filament-panels::page>
