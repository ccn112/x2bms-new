<x-filament-panels::page>
@php
    $tr = fn ($v) => number_format($v / 1_000_000, 1);
    $money = fn ($v) => number_format($v).' đ';
    $maxTrend = max($trend->pluck('value')->all() ?: [1]);
    $kpis = [
        ['Tổng chi phí tháng này', $money($total), '▲ 8.6% so với 06/2026', 'blue', true],
        ['Số dự án đang dùng', $projectsUsing, 'Đang có thuê bao', 'violet', null],
        ['Phí nền tảng', $money($platformFee), round($total ? $platformFee/$total*100 : 0).'% tổng chi phí', 'sky', true],
        ['Pass-through', $money($passThrough), round($total ? $passThrough/$total*100 : 0).'% tổng chi phí', 'amber', true],
        ['Số dư ví', $money($walletBalance), round($walletLimit ? $walletBalance/$walletLimit*100 : 0).'% hạn mức', 'green', null],
    ];
    $ac = ['blue'=>'bg-blue-500','violet'=>'bg-violet-500','sky'=>'bg-sky-500','amber'=>'bg-amber-500','green'=>'bg-emerald-500'];
    $C = 2 * M_PI * 54; $offset = 0;
@endphp
<div class="space-y-6">
    <div>
        <h1 class="font-title text-2xl font-bold text-slate-900">Tổng quan chi phí SaaS công ty</h1>
        <p class="mt-1 text-sm text-slate-500">Theo dõi chi phí nền tảng, pass-through và số dư ví theo tháng.</p>
    </div>

    {{-- KPI --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-5">
        @foreach ($kpis as [$label, $value, $sub, $c, $up])
            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <div class="flex items-center gap-3">
                    <span class="grid h-11 w-11 shrink-0 place-items-center rounded-xl {{ $ac[$c] }} text-white">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Z"/></svg>
                    </span>
                    <div class="min-w-0">
                        <div class="text-xs font-medium text-slate-500">{{ $label }}</div>
                        <div class="mt-0.5 truncate text-xl font-bold text-slate-900">{{ $value }}</div>
                    </div>
                </div>
                <div class="mt-2 text-xs {{ $up ? 'text-emerald-600' : 'text-slate-400' }}">{{ $sub }}</div>
            </div>
        @endforeach
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        {{-- Trend bar --}}
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h3 class="font-title text-sm font-bold text-slate-900">Xu hướng chi phí 6 tháng</h3>
            <p class="text-xs text-slate-400">Đơn vị: triệu đồng</p>
            <div class="mt-4 flex h-52 items-end justify-between gap-3">
                @foreach ($trend as $t)
                    <div class="flex flex-1 flex-col items-center gap-2">
                        <span class="text-xs font-semibold text-slate-700">{{ $tr($t['value']) }}</span>
                        <div class="w-full rounded-t-lg {{ $loop->last ? 'bg-blue-600' : 'bg-blue-200' }}" style="height: {{ max(6, round($t['value'] / $maxTrend * 160)) }}px"></div>
                        <span class="text-[11px] text-slate-400">{{ \Illuminate\Support\Str::of($t['period'])->after('-') }}/{{ \Illuminate\Support\Str::of($t['period'])->before('-') }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Cost component donut --}}
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h3 class="font-title text-sm font-bold text-slate-900">Cơ cấu chi phí tháng này</h3>
            <div class="mt-4 flex items-center gap-4">
                <div class="relative h-36 w-36 shrink-0">
                    <svg viewBox="0 0 120 120" class="h-36 w-36 -rotate-90">
                        <circle cx="60" cy="60" r="54" fill="none" stroke="#f1f5f9" stroke-width="13"/>
                        @foreach ($donut as $seg)
                            @php $len = $C * ($total ? $seg['value']/$total : 0); @endphp
                            <circle cx="60" cy="60" r="54" fill="none" stroke="{{ $seg['color'] }}" stroke-width="13" stroke-dasharray="{{ $len }} {{ $C - $len }}" stroke-dashoffset="{{ -$offset }}"/>
                            @php $offset += $len; @endphp
                        @endforeach
                    </svg>
                    <div class="absolute inset-0 grid place-items-center text-center">
                        <div><div class="text-[10px] text-slate-400">Tổng</div><div class="text-lg font-bold text-slate-900">{{ $tr($total) }}</div><div class="text-[10px] text-slate-400">triệu đồng</div></div>
                    </div>
                </div>
                <div class="flex-1 space-y-1.5">
                    @foreach ($donut as $seg)
                        <div class="flex items-center gap-2 text-xs">
                            <span class="h-2.5 w-2.5 rounded-full" style="background: {{ $seg['color'] }}"></span>
                            <span class="text-slate-600">{{ $seg['label'] }}</span>
                            <span class="ml-auto font-semibold text-slate-800">{{ $tr($seg['value']) }}</span>
                            <span class="w-12 text-right text-slate-400">{{ $seg['pct'] }}%</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        {{-- Top projects --}}
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h3 class="font-title text-sm font-bold text-slate-900">Dự án chi phí cao nhất</h3>
            <ul class="mt-4 space-y-3">
                @foreach ($topProjects as $i => $p)
                    <li class="flex items-center gap-3">
                        <span class="grid h-7 w-7 place-items-center rounded-full bg-slate-100 text-xs font-bold text-slate-500">{{ $i + 1 }}</span>
                        <span class="font-medium text-slate-700">{{ $p['name'] }}</span>
                        <span class="ml-auto text-sm font-semibold text-slate-900">{{ $tr($p['value']) }}</span>
                        <span class="w-12 text-right text-xs text-slate-400">{{ $p['pct'] }}%</span>
                    </li>
                @endforeach
            </ul>
        </div>

        {{-- Usage limits --}}
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h3 class="font-title text-sm font-bold text-slate-900">Tổng quan hạn mức & sử dụng</h3>
            <div class="mt-4 space-y-4">
                <div>
                    <div class="flex justify-between text-xs"><span class="text-slate-500">Hạn mức ví</span><span class="font-semibold text-slate-700">{{ number_format($walletBalance/1_000_000,1) }} / {{ number_format($walletLimit/1_000_000,0) }} (tr)</span></div>
                    <div class="mt-1 h-2 rounded-full bg-slate-100"><div class="h-full rounded-full bg-blue-500" style="width: {{ $walletLimit ? min(100,round($walletBalance/$walletLimit*100)) : 0 }}%"></div></div>
                </div>
                @foreach ($usage as $u)
                    <div>
                        <div class="flex justify-between text-xs"><span class="text-slate-500">Hạn mức {{ $u['meter'] }}</span><span class="font-semibold text-slate-700">{{ number_format($u['used']) }} / {{ number_format($u['limit']) }} ({{ $u['pct'] }}%)</span></div>
                        <div class="mt-1 h-2 rounded-full bg-slate-100"><div class="h-full rounded-full {{ $u['pct'] >= 75 ? 'bg-amber-500' : 'bg-emerald-500' }}" style="width: {{ min(100,$u['pct']) }}%"></div></div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
</x-filament-panels::page>
