@php
    $icons = [
        'squares' => 'M4 4h7v7H4zM13 4h7v7h-7zM4 13h7v7H4zM13 13h7v7h-7z',
        'cash' => 'M3 7h18v10H3V7Zm9 2.5A2.5 2.5 0 1 1 12 14.5 2.5 2.5 0 0 1 12 9.5Z',
        'doc' => 'M8 3h6l4 4v14H6V5a2 2 0 0 1 2-2Zm6 0v4h4M9 13h6M9 17h6',
        'bell' => 'M18 8a6 6 0 1 0-12 0v5l-2 2v1h16v-1l-2-2V8ZM9 21h6',
        'puzzle' => 'M10 3a2 2 0 1 1 4 0v2h3a1 1 0 0 1 1 1v3h2a2 2 0 1 1 0 4h-2v3a1 1 0 0 1-1 1h-3v-2a2 2 0 1 0-4 0v2H7a1 1 0 0 1-1-1v-3H4a2 2 0 1 1 0-4h2V6a1 1 0 0 1 1-1h3V3Z',
        'shield' => 'M12 3l7 3v5c0 4.5-3 8-7 10-4-2-7-5.5-7-10V6l7-3Z',
    ];
    $sourceBadge = fn ($s) => $s === 'HQ'
        ? 'bg-blue-50 text-blue-600'
        : 'bg-violet-50 text-violet-600';
    $overrideBadge = [
        'allowed' => ['Cho phép override', 'bg-green-50 text-green-600', 'M5 13l4 4L19 7'],
        'overriding' => ['Đang override tại dự án', 'bg-amber-50 text-amber-600', 'M12 9v4m0 4h.01M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h16a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z'],
        'none' => ['Không override', 'bg-slate-100 text-slate-500', 'M6 18 18 6M6 6l12 12'],
    ];
    // Donut segments (conic-gradient) from source breakdown.
    $donutTotal = max(collect($sourceBreakdown)->sum('count'), 1);
    $acc = 0; $stops = [];
    foreach ($sourceBreakdown as $seg) {
        $from = $acc / $donutTotal * 100;
        $acc += $seg['count'];
        $to = $acc / $donutTotal * 100;
        $stops[] = $seg['color'].' '.round($from, 1).'% '.round($to, 1).'%';
    }
    $donutCss = 'conic-gradient('.implode(',', $stops).')';
@endphp

<x-filament-panels::page>
    <x-x2.action-bar>
        <a href="#" class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-sm font-medium text-slate-600 hover:bg-slate-50">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M2.5 12S5.5 5.5 12 5.5 21.5 12 21.5 12 18.5 18.5 12 18.5 2.5 12 2.5 12Z"/><circle cx="12" cy="12" r="2.5"/></svg>
            Xem chi tiết
        </a>
        <a href="#" class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-sm font-medium text-slate-600 hover:bg-slate-50">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M16 4l4 4-10 10H6v-4L16 4Z"/></svg>
            Đề xuất thay đổi
        </a>
        <a href="{{ url('/admin/audit-logs') }}" class="inline-flex items-center gap-1.5 rounded-lg bg-x2-primary px-3 py-1.5 text-sm font-medium text-white hover:bg-x2-primary-600">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 2m6-2a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
            Lịch sử thay đổi
        </a>
    </x-x2.action-bar>

    {{-- Inheritance info banner --}}
    <div class="flex items-start gap-3 rounded-xl border border-blue-100 bg-blue-50 p-4 dark:border-blue-500/20 dark:bg-blue-500/10">
        <svg class="mt-0.5 h-5 w-5 shrink-0 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M12 3a9 9 0 1 0 0 18 9 9 0 0 0 0-18Z"/></svg>
        <div class="text-sm">
            <div class="font-semibold text-blue-800 dark:text-blue-200">Dự án đang kế thừa cấu hình từ SuperAdmin.</div>
            <div class="text-blue-600 dark:text-blue-300">Bạn có thể override (ghi đè) một số cấu hình cho phù hợp với nhu cầu thực tế của dự án.</div>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-[1fr_320px]">
        {{-- Left: 6 config group cards --}}
        <div class="space-y-4">
            @foreach ($groups as $g)
                @php [$ovLabel, $ovClass, $ovIcon] = $overrideBadge[$g['override']] ?? $overrideBadge['none']; @endphp
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-gray-900">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div class="flex items-start gap-3">
                            <span class="grid h-11 w-11 shrink-0 place-items-center rounded-xl bg-slate-100 text-slate-500 dark:bg-white/5">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icons[$g['icon']] }}"/></svg>
                            </span>
                            <div>
                                <div class="font-semibold text-slate-900 dark:text-white">{{ $g['title'] }}</div>
                                <div class="text-sm text-slate-500">{{ $g['desc'] }}</div>
                            </div>
                        </div>
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="inline-flex items-center rounded-lg px-2.5 py-1 text-xs font-semibold {{ $sourceBadge($g['source']) }}">Kế thừa từ {{ $g['source'] }}</span>
                            <span class="inline-flex items-center gap-1 rounded-lg px-2.5 py-1 text-xs font-semibold {{ $ovClass }}">
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $ovIcon }}"/></svg>
                                {{ $ovLabel }}
                            </span>
                            <a href="#" class="inline-flex items-center rounded-lg border border-slate-200 px-2.5 py-1 text-xs font-medium text-slate-600 hover:bg-slate-50">Xem chi tiết</a>
                        </div>
                    </div>

                    <div class="mt-4 grid grid-cols-2 gap-x-6 gap-y-3 border-t border-slate-100 pt-4 dark:border-white/10 sm:grid-cols-3 xl:grid-cols-5">
                        @foreach ($g['fields'] as [$k, $v])
                            <div>
                                <div class="text-xs text-slate-400">{{ $k }}</div>
                                <div class="mt-0.5 text-sm font-medium text-slate-800 dark:text-slate-100">{{ $v }}</div>
                            </div>
                        @endforeach
                        @if (! empty($g['extra']))
                            <div class="flex items-end">
                                <span class="inline-flex items-center gap-1 text-xs font-medium text-x2-primary">{{ $g['extra'] }}
                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6"/></svg>
                                </span>
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Right column --}}
        <div class="space-y-4">
            {{-- Inheritance overview --}}
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-gray-900">
                <h3 class="text-base font-semibold text-slate-900 dark:text-white">Tổng quan kế thừa</h3>
                <div class="mt-4 space-y-3 text-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-slate-500">Nguồn cấu hình chính</span>
                        <span class="inline-flex items-center rounded-lg bg-violet-50 px-2 py-0.5 text-xs font-semibold text-violet-600">{{ $summary['primary_source'] }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-slate-500">Phạm vi áp dụng</span>
                        <span class="font-medium text-slate-800 dark:text-slate-100">{{ $summary['scope'] }}</span>
                    </div>
                    <div class="space-y-2 border-t border-slate-100 pt-3 dark:border-white/10">
                        <div class="flex items-center justify-between"><span class="text-slate-500">Tổng số nhóm cấu hình</span><span class="font-semibold text-slate-800 dark:text-slate-100">{{ $summary['total'] }}</span></div>
                        <div class="flex items-center justify-between"><span class="text-slate-500">Kế thừa nguyên gốc</span><span class="font-semibold text-slate-800 dark:text-slate-100">{{ $summary['inherited'] }}</span></div>
                        <div class="flex items-center justify-between"><span class="text-amber-600">Đang override tại dự án</span><span class="font-semibold text-amber-600">{{ $summary['overriding'] }}</span></div>
                    </div>
                </div>
            </div>

            {{-- Source donut --}}
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-gray-900">
                <h3 class="text-base font-semibold text-slate-900 dark:text-white">Nguồn cấu hình</h3>
                <div class="mt-4 flex items-center gap-4">
                    <div class="relative h-24 w-24 shrink-0 rounded-full" style="background: {{ $donutCss }}">
                        <div class="absolute inset-[18%] rounded-full bg-white dark:bg-gray-900"></div>
                    </div>
                    <ul class="flex-1 space-y-2 text-sm">
                        @foreach ($sourceBreakdown as $seg)
                            <li class="flex items-center gap-2">
                                <span class="h-2.5 w-2.5 rounded-full" style="background: {{ $seg['color'] }}"></span>
                                <span class="flex-1 text-slate-600 dark:text-slate-300">{{ $seg['label'] }}</span>
                                <span class="font-semibold text-slate-800 dark:text-slate-100">{{ $seg['count'] }} ({{ round($seg['count'] / $donutTotal * 100) }}%)</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>

            {{-- Recent updaters --}}
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-gray-900">
                <h3 class="text-base font-semibold text-slate-900 dark:text-white">Người cập nhật gần nhất</h3>
                <ul class="mt-4 space-y-4">
                    @forelse ($recent as $r)
                        <li class="flex items-start gap-3">
                            <span class="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-slate-100 text-xs font-bold text-slate-500 dark:bg-white/5">
                                {{ \Illuminate\Support\Str::of($r['actor'])->explode(' ')->map(fn ($w) => mb_substr($w, 0, 1))->take(2)->implode('') }}
                            </span>
                            <div class="min-w-0">
                                <div class="truncate text-sm font-medium text-slate-800 dark:text-slate-100">{{ $r['actor'] }}</div>
                                <div class="truncate text-xs text-slate-500">{{ $r['desc'] }}</div>
                                <div class="mt-0.5 text-xs text-slate-400">{{ $r['at'] }}</div>
                            </div>
                        </li>
                    @empty
                        <li class="text-sm text-slate-400">Chưa có thay đổi nào được ghi nhận.</li>
                    @endforelse
                </ul>
                <a href="{{ url('/admin/audit-logs') }}" class="mt-4 flex items-center justify-center gap-1.5 rounded-xl bg-slate-50 py-2 text-sm font-medium text-x2-primary hover:bg-slate-100 dark:bg-white/5">
                    Xem lịch sử thay đổi đầy đủ
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14m-6-6 6 6-6 6"/></svg>
                </a>
            </div>
        </div>
    </div>
</x-filament-panels::page>
