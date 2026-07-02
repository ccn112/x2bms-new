<x-filament-panels::page>
@php $money = fn ($v) => number_format($v).' đ'; @endphp
<div class="space-y-6">
    <div>
        <h1 class="font-title text-2xl font-bold text-slate-900">Chi tiết usage / metering</h1>
        <p class="mt-1 text-sm text-slate-500">Lượng sử dụng theo từng meter, hạn mức, phần vượt và chi phí theo đơn giá.</p>
    </div>
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-100 text-sm">
                <thead class="bg-slate-50/70 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                    <tr><th class="px-4 py-3">Meter</th><th class="px-4 py-3">Đã dùng</th><th class="px-4 py-3">Hạn mức</th><th class="px-4 py-3">% dùng</th><th class="px-4 py-3">Vượt</th><th class="px-4 py-3 text-right">Đơn giá</th><th class="px-4 py-3 text-right">Chi phí</th></tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @foreach ($rows as $r)
                        <tr class="hover:bg-slate-50/60">
                            <td class="whitespace-nowrap px-4 py-3 font-medium text-slate-800">{{ $r['meter'] }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-slate-600">{{ number_format($r['used']) }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-slate-500">{{ number_format($r['limit']) }}</td>
                            <td class="whitespace-nowrap px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <div class="h-1.5 w-16 rounded-full bg-slate-100"><div class="h-full rounded-full {{ $r['pct'] >= 75 ? 'bg-amber-500' : 'bg-emerald-500' }}" style="width: {{ min(100, $r['pct']) }}%"></div></div>
                                    <span class="text-xs text-slate-500">{{ $r['pct'] }}%</span>
                                </div>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 {{ $r['overage'] > 0 ? 'text-rose-600' : 'text-slate-400' }}">{{ number_format($r['overage']) }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-right text-slate-600">{{ number_format($r['unit']) }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-right font-semibold text-slate-900">{{ $money($r['cost']) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot><tr class="border-t border-slate-200 bg-slate-50/70 font-semibold"><td colspan="6" class="px-4 py-3 text-right text-slate-600">Tổng chi phí usage</td><td class="px-4 py-3 text-right text-slate-900">{{ $money($totalCost) }}</td></tr></tfoot>
            </table>
        </div>
    </div>
</div>
</x-filament-panels::page>
