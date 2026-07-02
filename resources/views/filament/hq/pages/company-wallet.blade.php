<x-filament-panels::page>
@php
    $money = fn ($v) => number_format($v).' đ';
    $palette = ['#2563eb','#10b981','#f59e0b','#8b5cf6','#14b8a6'];
    $maxChart = max($chart->pluck('value')->all() ?: [1]);
@endphp
<div class="space-y-6">
    <div>
        <h1 class="font-title text-2xl font-bold text-slate-900">Ví công ty</h1>
        <p class="mt-1 text-sm text-slate-500">Quản lý số dư, hạn mức và chi tiêu của công ty.</p>
    </div>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-[1fr_320px]">
        <div class="space-y-6">
            {{-- Balance card --}}
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div class="flex items-center gap-4">
                        <span class="grid h-14 w-14 place-items-center rounded-2xl bg-blue-600 text-white">
                            <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21 12a2.25 2.25 0 0 0-2.25-2.25H15a3 3 0 1 1-6 0H5.25A2.25 2.25 0 0 0 3 12m18 0v6a2.25 2.25 0 0 1-2.25 2.25H5.25A2.25 2.25 0 0 1 3 18v-6m18 0V9M3 12V9m18 0a2.25 2.25 0 0 0-2.25-2.25H5.25A2.25 2.25 0 0 0 3 9m18 0V6a2.25 2.25 0 0 0-2.25-2.25H5.25A2.25 2.25 0 0 0 3 6v3"/></svg>
                        </span>
                        <div>
                            <div class="text-sm text-slate-500">Số dư hiện tại</div>
                            <div class="text-3xl font-bold text-slate-900">{{ $money($balance) }}</div>
                            <div class="mt-1 text-xs text-emerald-600">▲ đã cập nhật hôm nay</div>
                        </div>
                    </div>
                    <div class="text-right">
                        <div class="text-sm text-slate-500">Hạn mức tín dụng</div>
                        <div class="text-2xl font-bold text-slate-900">{{ $money($limit) }}</div>
                    </div>
                </div>
                <div class="mt-5">
                    <div class="h-3 w-full overflow-hidden rounded-full bg-slate-100"><div class="h-full rounded-full bg-blue-600" style="width: {{ min(100,$usedPct) }}%"></div></div>
                    <div class="mt-2 flex justify-between text-sm"><span class="font-medium text-blue-600">{{ $usedPct }}% đã sử dụng</span><span class="text-slate-500">Còn lại {{ $money($remaining) }}</span></div>
                </div>
            </div>

            {{-- Mini KPIs --}}
            <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
                <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm"><div class="text-xs text-slate-500">Tiền đã nạp trong tháng</div><div class="mt-1 text-lg font-bold text-emerald-600">{{ $money($topupMonth) }}</div></div>
                <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm"><div class="text-xs text-slate-500">Số lần nạp</div><div class="mt-1 text-lg font-bold text-slate-900">{{ $topupCount }} lần</div></div>
                <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm"><div class="text-xs text-slate-500">Ngưỡng nạp tự động</div><div class="mt-1 text-lg font-bold text-slate-900">{{ $money((float)($wallet?->auto_topup_threshold ?? 0)) }}</div></div>
                <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm"><div class="text-xs text-slate-500">Phương thức</div><div class="mt-1 text-sm font-bold text-slate-900">{{ $wallet?->payment_method }}</div><div class="text-xs text-slate-400">{{ $wallet?->payment_account }}</div></div>
            </div>

            {{-- Balance chart --}}
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h3 class="font-title text-sm font-bold text-slate-900">Biểu đồ số dư ví</h3>
                <div class="mt-4 flex h-48 items-end justify-between gap-2">
                    @foreach ($chart as $pt)
                        <div class="flex flex-1 flex-col items-center gap-2">
                            <div class="w-full rounded-t bg-blue-400/70" style="height: {{ max(6, round($pt['value']/$maxChart*150)) }}px"></div>
                            <span class="text-[10px] text-slate-400">{{ $pt['date'] }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Right: allocation + actions --}}
        <div class="space-y-4">
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h3 class="font-title text-sm font-bold text-slate-900">Phân bổ ngân sách theo dự án</h3>
                <ul class="mt-3 space-y-3">
                    @foreach ($allocations as $i => $a)
                        <li>
                            <div class="flex items-center justify-between text-sm">
                                <span class="flex items-center gap-2"><span class="h-2.5 w-2.5 rounded-full" style="background: {{ $palette[$i % 5] }}"></span>{{ $a['name'] }}</span>
                                <span class="font-semibold text-slate-800">{{ number_format($a['value']/1_000_000,0) }}M</span>
                            </div>
                            <div class="mt-1 h-1.5 rounded-full bg-slate-100"><div class="h-full rounded-full" style="width: {{ $allocTotal ? round($a['value']/$allocTotal*100) : 0 }}%; background: {{ $palette[$i % 5] }}"></div></div>
                        </li>
                    @endforeach
                </ul>
                <div class="mt-3 flex justify-between border-t border-slate-100 pt-2 text-sm font-semibold"><span class="text-slate-500">Tổng cộng</span><span class="text-slate-900">{{ $money($allocTotal) }}</span></div>
            </div>
            <div class="space-y-2">
                <button class="flex w-full items-center gap-3 rounded-xl bg-blue-600 px-4 py-3 text-left text-white hover:bg-blue-700"><span class="text-sm font-semibold">Nạp ví</span><span class="ml-auto">→</span></button>
                <button class="flex w-full items-center gap-3 rounded-xl border border-slate-200 bg-white px-4 py-3 text-left hover:bg-slate-50"><span class="text-sm font-medium text-slate-700">Thiết lập tự động nạp</span><span class="ml-auto text-slate-400">→</span></button>
                <button class="flex w-full items-center gap-3 rounded-xl border border-slate-200 bg-white px-4 py-3 text-left hover:bg-slate-50"><span class="text-sm font-medium text-slate-700">Yêu cầu tăng hạn mức</span><span class="ml-auto text-slate-400">→</span></button>
            </div>
        </div>
    </div>
</div>
</x-filament-panels::page>
