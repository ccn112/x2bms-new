<x-filament-panels::page>
@php $ty = fn ($v) => number_format($v / 1_000_000_000, 2).' tỷ'; @endphp
<div class="space-y-6">
    <div>
        <h1 class="font-title text-2xl font-bold text-slate-900">Công nợ theo dự án</h1>
        <p class="mt-1 text-sm text-slate-500">Tổng hợp dư nợ phải thu, số căn nợ và tỷ lệ nợ xấu theo từng dự án.</p>
    </div>
    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm"><div class="text-xs text-slate-500">Tổng công nợ</div><div class="mt-1 text-xl font-bold text-slate-900">{{ $ty($total) }}</div></div>
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm"><div class="text-xs text-slate-500">Số dự án</div><div class="mt-1 text-xl font-bold text-slate-900">{{ $projects->count() }}</div></div>
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm"><div class="text-xs text-slate-500">Tổng số căn nợ</div><div class="mt-1 text-xl font-bold text-slate-900">{{ number_format($units) }}</div></div>
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm"><div class="text-xs text-slate-500">Tỷ lệ nợ xấu TB</div><div class="mt-1 text-xl font-bold text-rose-600">{{ $badAvg }}%</div></div>
    </div>
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-100 text-sm">
                <thead class="bg-slate-50/70 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                    <tr><th class="px-4 py-3">Dự án</th><th class="px-4 py-3 text-right">Tổng nợ</th><th class="px-4 py-3 text-right">Số căn</th><th class="px-4 py-3 text-right">% tổng</th><th class="px-4 py-3 text-right">Nợ xấu</th><th class="px-4 py-3 text-right">Xu hướng</th></tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @foreach ($projects as $p)
                        <tr class="hover:bg-slate-50/60">
                            <td class="whitespace-nowrap px-4 py-3 font-medium text-slate-800">{{ $p['project'] }}</td>
                            <td class="px-4 py-3 text-right font-semibold text-slate-900">{{ number_format($p['total'], 2) }} tỷ</td>
                            <td class="px-4 py-3 text-right text-slate-500">{{ number_format($p['units']) }}</td>
                            <td class="px-4 py-3 text-right text-slate-500">{{ $p['share'] }}%</td>
                            <td class="px-4 py-3 text-right text-rose-600">{{ $p['bad_pct'] }}%</td>
                            <td class="px-4 py-3 text-right {{ $p['trend'] >= 0 ? 'text-rose-600' : 'text-emerald-600' }}">{{ $p['trend'] >= 0 ? '▲' : '▼' }} {{ abs($p['trend']) }}%</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
</x-filament-panels::page>
