<x-filament-panels::page>
@php
    $money = fn ($v) => number_format($v).' đ';
    $tr = fn ($v) => number_format($v / 1_000_000, 1);
    $maxV = max(array_merge([1], $series->pluck('value')->all()));
    $confLabel = ['low' => 'Thấp', 'medium' => 'Trung bình', 'high' => 'Cao'];
@endphp
<div class="space-y-6">
    <div>
        <h1 class="font-title text-2xl font-bold text-slate-900">Dự báo chi phí tháng tới</h1>
        <p class="mt-1 text-sm text-slate-500">Dự báo chi phí SaaS dựa trên xu hướng sử dụng và cam kết gói dịch vụ.</p>
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><div class="text-sm text-slate-500">Chi phí tháng này</div><div class="mt-1 text-2xl font-bold text-slate-900">{{ $money($current) }}</div></div>
        <div class="rounded-2xl border border-blue-200 bg-blue-50/40 p-5 shadow-sm"><div class="text-sm text-slate-500">Dự báo tháng tới</div><div class="mt-1 text-2xl font-bold text-blue-700">{{ $money($projected) }}</div></div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><div class="text-sm text-slate-500">Tăng trưởng dự kiến</div><div class="mt-1 text-2xl font-bold {{ $growth >= 0 ? 'text-amber-600' : 'text-emerald-600' }}">{{ $growth >= 0 ? '+' : '' }}{{ $growth }}%</div><div class="text-xs text-slate-400">Độ tin cậy: {{ $confLabel[$confidence] ?? $confidence }}</div></div>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <h3 class="font-title text-sm font-bold text-slate-900">Xu hướng & dự báo (triệu đồng)</h3>
        <div class="mt-4 flex h-56 items-end justify-between gap-3">
            @foreach ($series as $s)
                <div class="flex flex-1 flex-col items-center gap-2">
                    <span class="text-xs font-semibold {{ $s['forecast'] ? 'text-blue-600' : 'text-slate-700' }}">{{ $tr($s['value']) }}</span>
                    <div class="w-full rounded-t-lg {{ $s['forecast'] ? 'border-2 border-dashed border-blue-400 bg-blue-100' : 'bg-blue-500' }}" style="height: {{ max(6, round($s['value'] / $maxV * 170)) }}px"></div>
                    <span class="text-[11px] {{ $s['forecast'] ? 'font-semibold text-blue-600' : 'text-slate-400' }}">{{ \Illuminate\Support\Str::of($s['period'])->after('-') }}/{{ \Illuminate\Support\Str::of($s['period'])->before('-') }}</span>
                </div>
            @endforeach
        </div>
        <p class="mt-3 text-xs text-slate-400">Cột nét đứt = tháng dự báo. Dựa trên xu hướng 6 tháng gần nhất và mức sử dụng pass-through hiện tại.</p>
    </div>
</div>
</x-filament-panels::page>
