<x-filament-panels::page>
@php
    $ty = fn ($v) => number_format($v / 1_000_000_000, 2).' tỷ';
    $colors = ['#10b981', '#3b82f6', '#f59e0b', '#f97316', '#ef4444'];
    $C = 2 * M_PI * 54; $offset = 0; $agingTotal = $aging->sum('value');
    $maxTrend = max($trend->pluck('value')->all() ?: [1]);
@endphp
<div class="space-y-6">
    <div>
        <h1 class="font-title text-2xl font-bold text-slate-900">Tổng quan tài chính đa dự án</h1>
        <p class="mt-1 text-sm text-slate-500">Bức tranh công nợ, thu hồi và quỹ dự phòng hợp nhất toàn công ty.</p>
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><div class="text-sm text-slate-500">Tổng công nợ phải thu</div><div class="mt-1 text-2xl font-bold text-slate-900">{{ $ty($kpi['total_debt'] ?? 0) }}</div></div>
        <div class="rounded-2xl border border-rose-200 bg-rose-50/40 p-5 shadow-sm"><div class="text-sm text-slate-500">Nợ quá 90 ngày (nợ xấu)</div><div class="mt-1 text-2xl font-bold text-rose-600">{{ $ty($kpi['overdue_90'] ?? 0) }}</div></div>
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50/40 p-5 shadow-sm"><div class="text-sm text-slate-500">Tỷ lệ thu</div><div class="mt-1 text-2xl font-bold text-emerald-600">{{ $kpi['collection_rate'] ?? 0 }}%</div></div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><div class="text-sm text-slate-500">Doanh thu tháng</div><div class="mt-1 text-2xl font-bold text-slate-900">{{ $ty($kpi['revenue_month'] ?? 0) }}</div></div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><div class="text-sm text-slate-500">Đã thu trong tháng</div><div class="mt-1 text-2xl font-bold text-blue-600">{{ $ty($kpi['collected_month'] ?? 0) }}</div></div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><div class="text-sm text-slate-500">Quỹ dự phòng</div><div class="mt-1 text-2xl font-bold text-slate-900">{{ $ty($kpi['reserve_fund'] ?? 0) }}</div></div>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h3 class="font-title text-sm font-bold text-slate-900">Cơ cấu công nợ theo tuổi nợ</h3>
            <div class="mt-4 flex items-center gap-4">
                <div class="relative h-32 w-32 shrink-0">
                    <svg viewBox="0 0 120 120" class="h-32 w-32 -rotate-90">
                        <circle cx="60" cy="60" r="54" fill="none" stroke="#f1f5f9" stroke-width="12"/>
                        @foreach ($aging as $i => $seg)
                            @php $len = $C * ($agingTotal ? $seg['value'] / $agingTotal : 0); @endphp
                            <circle cx="60" cy="60" r="54" fill="none" stroke="{{ $colors[$i % 5] }}" stroke-width="12" stroke-dasharray="{{ $len }} {{ $C - $len }}" stroke-dashoffset="{{ -$offset }}"/>
                            @php $offset += $len; @endphp
                        @endforeach
                    </svg>
                </div>
                <div class="flex-1 space-y-1.5">
                    @foreach ($aging as $i => $seg)
                        <div class="flex items-center gap-2 text-xs"><span class="h-2.5 w-2.5 rounded-full" style="background: {{ $colors[$i % 5] }}"></span><span class="text-slate-600">{{ $seg['label'] }}</span><span class="ml-auto font-semibold text-slate-800">{{ $seg['pct'] }}%</span></div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm lg:col-span-2">
            <h3 class="font-title text-sm font-bold text-slate-900">Xu hướng tỷ lệ thu</h3>
            <div class="mt-4 flex h-40 items-end justify-between gap-3">
                @foreach ($trend as $t)
                    <div class="flex flex-1 flex-col items-center gap-2">
                        <span class="text-xs font-semibold text-slate-700">{{ $t['value'] }}%</span>
                        <div class="w-full rounded-t-lg bg-emerald-400" style="height: {{ max(6, round($t['value'] / $maxTrend * 120)) }}px"></div>
                        <span class="text-[10px] text-slate-400">{{ \Illuminate\Support\Str::after($t['period'], '-') }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <h3 class="font-title text-sm font-bold text-slate-900">Dự án công nợ cao nhất</h3>
        <ul class="mt-4 space-y-3">
            @foreach ($topProjects as $i => $p)
                <li class="flex items-center gap-3">
                    <span class="grid h-7 w-7 place-items-center rounded-full bg-slate-100 text-xs font-bold text-slate-500">{{ $i + 1 }}</span>
                    <span class="font-medium text-slate-700">{{ $p['project'] }}</span>
                    <span class="ml-auto text-sm font-semibold text-slate-900">{{ $ty($p['total']) }}</span>
                    <span class="w-14 text-right text-xs text-slate-400">{{ $p['share'] }}%</span>
                </li>
            @endforeach
        </ul>
    </div>
</div>
</x-filament-panels::page>
