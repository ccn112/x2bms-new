<x-filament-panels::page>
@php $maxT = max($trend->pluck('value')->all() ?: [1]); @endphp
<div class="space-y-6">
    <div>
        <h1 class="font-title text-2xl font-bold text-slate-900">Tỷ lệ thu theo kỳ phí</h1>
        <p class="mt-1 text-sm text-slate-500">Hiệu quả thu phí theo từng kỳ và từng dự án. Tỷ lệ thu trung bình: <span class="font-semibold text-slate-700">{{ $avg }}%</span>.</p>
    </div>
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h3 class="font-title text-sm font-bold text-slate-900">Tỷ lệ thu theo kỳ</h3>
            <div class="mt-4 flex h-52 items-end justify-between gap-3">
                @foreach ($trend as $t)
                    <div class="flex flex-1 flex-col items-center gap-2">
                        <span class="text-xs font-semibold text-slate-700">{{ $t['value'] }}%</span>
                        <div class="w-full rounded-t-lg {{ $t['value'] >= 85 ? 'bg-emerald-500' : 'bg-amber-500' }}" style="height: {{ max(6, round($t['value'] / $maxT * 160)) }}px"></div>
                        <span class="text-[10px] text-slate-400">{{ \Illuminate\Support\Str::after($t['period'], '-') }}/{{ \Illuminate\Support\Str::before($t['period'], '-') }}</span>
                    </div>
                @endforeach
            </div>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h3 class="font-title text-sm font-bold text-slate-900">Tỷ lệ thu theo dự án (kỳ hiện tại)</h3>
            <div class="mt-4 space-y-3">
                @foreach ($byProject as $p)
                    <div>
                        <div class="mb-1 flex justify-between text-sm"><span class="text-slate-600">{{ $p['project'] }}</span><span class="font-semibold text-slate-800">{{ $p['value'] }}%</span></div>
                        <div class="h-2 rounded-full bg-slate-100"><div class="h-full rounded-full {{ $p['value'] >= 78 ? 'bg-emerald-500' : 'bg-amber-500' }}" style="width: {{ $p['value'] }}%"></div></div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
</x-filament-panels::page>
