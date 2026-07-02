<x-filament-panels::page>
@php $maxB = max($byBuilding->pluck('count')->all() ?: [1]); @endphp
<div class="space-y-6">
    <div><h1 class="font-title text-2xl font-bold text-slate-900">Báo cáo SLA</h1>
        <p class="mt-1 text-sm text-slate-500">Theo dõi thời gian phản hồi/giải quyết và tỷ lệ tuân thủ SLA của hỗ trợ.</p></div>
    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><div class="text-xs text-slate-500">Thời gian phản hồi TB</div><div class="mt-1 text-2xl font-bold text-slate-900">{{ (int)($sla['response_time']??0) }} phút</div></div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><div class="text-xs text-slate-500">Thời gian giải quyết TB</div><div class="mt-1 text-2xl font-bold text-slate-900">{{ round(($sla['resolution_time']??0)/60,1) }} giờ</div></div>
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50/40 p-5 shadow-sm"><div class="text-xs text-slate-500">Tỷ lệ đúng SLA</div><div class="mt-1 text-2xl font-bold text-emerald-600">{{ $sla['sla_compliance']??0 }}%</div></div>
        <div class="rounded-2xl border border-rose-200 bg-rose-50/40 p-5 shadow-sm"><div class="text-xs text-slate-500">Tỷ lệ vi phạm</div><div class="mt-1 text-2xl font-bold text-rose-600">{{ $sla['breach_rate']??0 }}%</div></div>
    </div>
    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <h3 class="font-title text-sm font-bold text-slate-900">Ticket theo tòa nhà</h3>
        <div class="mt-4 space-y-3">
            @foreach ($byBuilding as $b)
                <div><div class="mb-1 flex justify-between text-sm"><span class="text-slate-600">{{ $b['label'] }}</span><span class="font-semibold text-slate-800">{{ $b['count'] }}</span></div><div class="h-2 rounded-full bg-slate-100"><div class="h-full rounded-full bg-blue-500" style="width: {{ round($b['count']/$maxB*100) }}%"></div></div></div>
            @endforeach
        </div>
    </div>
</div>
</x-filament-panels::page>
