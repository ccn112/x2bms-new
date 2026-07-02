<x-filament-panels::page>
@php
    $ty = fn ($v) => number_format($v / 1_000_000_000, 2);
    $colors = ['#10b981', '#3b82f6', '#f59e0b', '#f97316', '#ef4444'];
    $bucketKeys = ['current', 'd30', 'd60', 'd90', 'over90'];
    $C = 2 * M_PI * 52; $offset = 0;
@endphp
<div class="space-y-6">
    <div>
        <h1 class="font-title text-2xl font-bold text-slate-900">Báo cáo tuổi nợ (Aging Report)</h1>
        <p class="mt-1 text-sm text-slate-500">Phân tích công nợ theo nhóm tuổi nợ để ưu tiên thu hồi. Đơn vị: tỷ đồng.</p>
    </div>

    {{-- Bucket KPI cards --}}
    <div class="grid grid-cols-2 gap-4 md:grid-cols-3 xl:grid-cols-6">
        @foreach ($buckets as $i => $b)
            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <div class="flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full" style="background: {{ $colors[$i % 5] }}"></span><span class="text-xs text-slate-500">{{ $b['label'] }}</span></div>
                <div class="mt-1 text-xl font-bold text-slate-900">{{ $ty($b['value']) }} tỷ</div>
                <div class="text-xs text-slate-400">{{ $b['pct'] }}% tổng nợ</div>
            </div>
        @endforeach
        <div class="rounded-2xl border border-violet-200 bg-violet-50/40 p-4 shadow-sm">
            <div class="text-xs text-slate-500">Tỷ lệ nợ xấu (>90 ngày)</div>
            <div class="mt-1 text-xl font-bold text-violet-600">{{ $badPct }}%</div>
            <div class="text-xs text-slate-400">{{ $ty($badDebt) }} tỷ</div>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        {{-- Stacked bars per project --}}
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h3 class="font-title text-sm font-bold text-slate-900">Công nợ theo nhóm tuổi nợ theo dự án</h3>
            <div class="mt-4 space-y-3">
                @foreach ($projects as $p)
                    <div>
                        <div class="mb-1 flex justify-between text-xs"><span class="font-medium text-slate-600">{{ $p['project'] }}</span><span class="text-slate-500">{{ number_format($p['total'], 2) }} tỷ</span></div>
                        <div class="flex h-4 overflow-hidden rounded bg-slate-100">
                            @foreach ($bucketKeys as $bi => $bk)
                                @php $v = $p[$bk] ?? 0; $w = $p['total'] ? $v / $p['total'] * 100 : 0; @endphp
                                <div style="width: {{ $w }}%; background: {{ $colors[$bi] }}" title="{{ $v }}"></div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Donut --}}
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h3 class="font-title text-sm font-bold text-slate-900">Cơ cấu công nợ theo nhóm tuổi nợ (toàn hệ thống)</h3>
            <div class="mt-4 flex items-center gap-4">
                <div class="relative h-32 w-32 shrink-0">
                    <svg viewBox="0 0 120 120" class="h-32 w-32 -rotate-90">
                        <circle cx="60" cy="60" r="52" fill="none" stroke="#f1f5f9" stroke-width="14"/>
                        @foreach ($buckets as $i => $seg)
                            @php $len = $C * ($total ? $seg['value'] / $total : 0); @endphp
                            <circle cx="60" cy="60" r="52" fill="none" stroke="{{ $colors[$i % 5] }}" stroke-width="14" stroke-dasharray="{{ $len }} {{ $C - $len }}" stroke-dashoffset="{{ -$offset }}"/>
                            @php $offset += $len; @endphp
                        @endforeach
                    </svg>
                    <div class="absolute inset-0 grid place-items-center text-center"><div><div class="text-[10px] text-slate-400">Tổng nợ</div><div class="text-lg font-bold text-slate-900">{{ $ty($total) }}</div><div class="text-[10px] text-slate-400">tỷ đồng</div></div></div>
                </div>
                <div class="flex-1 space-y-1.5">
                    @foreach ($buckets as $i => $seg)
                        <div class="flex items-center gap-2 text-xs"><span class="h-2.5 w-2.5 rounded-full" style="background: {{ $colors[$i % 5] }}"></span><span class="text-slate-600">{{ $seg['label'] }}</span><span class="ml-auto font-semibold text-slate-800">{{ $ty($seg['value']) }}</span></div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    {{-- Detail table --}}
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 px-4 py-3 font-title text-sm font-bold text-slate-900">Chi tiết công nợ theo dự án (tỷ đồng)</div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-100 text-sm">
                <thead class="bg-slate-50/70 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                    <tr><th class="px-4 py-2">Dự án</th><th class="px-4 py-2 text-right">Current</th><th class="px-4 py-2 text-right">1–30</th><th class="px-4 py-2 text-right">31–60</th><th class="px-4 py-2 text-right">61–90</th><th class="px-4 py-2 text-right">&gt;90</th><th class="px-4 py-2 text-right">Tổng nợ</th><th class="px-4 py-2 text-right">Số căn</th><th class="px-4 py-2 text-right">% tổng</th></tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @foreach ($projects as $p)
                        <tr class="hover:bg-slate-50/60">
                            <td class="whitespace-nowrap px-4 py-2 font-medium text-slate-800">{{ $p['project'] }}</td>
                            <td class="px-4 py-2 text-right text-slate-600">{{ number_format($p['current'], 2) }}</td>
                            <td class="px-4 py-2 text-right text-slate-600">{{ number_format($p['d30'], 2) }}</td>
                            <td class="px-4 py-2 text-right text-slate-600">{{ number_format($p['d60'], 2) }}</td>
                            <td class="px-4 py-2 text-right text-slate-600">{{ number_format($p['d90'], 2) }}</td>
                            <td class="px-4 py-2 text-right text-rose-600">{{ number_format($p['over90'], 2) }}</td>
                            <td class="px-4 py-2 text-right font-semibold text-slate-900">{{ number_format($p['total'], 2) }}</td>
                            <td class="px-4 py-2 text-right text-slate-500">{{ number_format($p['units']) }}</td>
                            <td class="px-4 py-2 text-right text-slate-500">{{ $p['share'] }}%</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
</x-filament-panels::page>
