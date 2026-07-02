<x-filament-panels::page>
@php
    $ty = fn ($v) => number_format($v / 1_000_000_000, 2).' tỷ';
    $colors = ['#2563eb', '#f59e0b', '#10b981', '#8b5cf6', '#94a3b8'];
    $C = 2 * M_PI * 54; $offset = 0;
@endphp
<div class="space-y-6">
    <div>
        <h1 class="font-title text-2xl font-bold text-slate-900">Công nợ theo loại phí</h1>
        <p class="mt-1 text-sm text-slate-500">Cơ cấu công nợ phải thu phân theo từng loại phí trên toàn công ty.</p>
    </div>
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-center gap-6">
                <div class="relative h-40 w-40 shrink-0">
                    <svg viewBox="0 0 120 120" class="h-40 w-40 -rotate-90">
                        <circle cx="60" cy="60" r="54" fill="none" stroke="#f1f5f9" stroke-width="13"/>
                        @foreach ($rows as $i => $seg)
                            @php $len = $C * ($total ? $seg['value'] / $total : 0); @endphp
                            <circle cx="60" cy="60" r="54" fill="none" stroke="{{ $colors[$i % 5] }}" stroke-width="13" stroke-dasharray="{{ $len }} {{ $C - $len }}" stroke-dashoffset="{{ -$offset }}"/>
                            @php $offset += $len; @endphp
                        @endforeach
                    </svg>
                    <div class="absolute inset-0 grid place-items-center text-center"><div><div class="text-[10px] text-slate-400">Tổng</div><div class="text-base font-bold text-slate-900">{{ number_format($total / 1_000_000_000, 1) }} tỷ</div></div></div>
                </div>
                <div class="flex-1 space-y-2">
                    @foreach ($rows as $i => $seg)
                        <div class="flex items-center gap-2 text-sm"><span class="h-3 w-3 rounded-full" style="background: {{ $colors[$i % 5] }}"></span><span class="text-slate-600">{{ $seg['fee'] }}</span><span class="ml-auto font-semibold text-slate-800">{{ $ty($seg['value']) }}</span><span class="w-12 text-right text-xs text-slate-400">{{ $total ? round($seg['value'] / $total * 100, 1) : 0 }}%</span></div>
                    @endforeach
                </div>
            </div>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h3 class="font-title text-sm font-bold text-slate-900">Chi tiết theo loại phí</h3>
            <div class="mt-4 space-y-3">
                @foreach ($rows as $i => $seg)
                    <div>
                        <div class="mb-1 flex justify-between text-sm"><span class="text-slate-600">{{ $seg['fee'] }}</span><span class="font-semibold text-slate-800">{{ $ty($seg['value']) }}</span></div>
                        <div class="h-2 rounded-full bg-slate-100"><div class="h-full rounded-full" style="width: {{ $total ? $seg['value'] / $total * 100 : 0 }}%; background: {{ $colors[$i % 5] }}"></div></div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
</x-filament-panels::page>
