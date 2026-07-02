<x-filament-panels::page>
@php
    $money = fn ($v) => number_format($v).' đ';
    $st = ['paid'=>['Đã thanh toán','bg-emerald-50 text-emerald-700'],'partially_paid'=>['Thanh toán một phần','bg-amber-50 text-amber-700'],'issued'=>['Đã phát hành','bg-sky-50 text-sky-700'],'sent'=>['Đã gửi','bg-sky-50 text-sky-700'],'overdue'=>['Quá hạn','bg-rose-50 text-rose-700'],'draft'=>['Nháp','bg-slate-100 text-slate-500'],'voided'=>['Đã hủy','bg-slate-100 text-slate-500'],'credited'=>['Đã ghi có','bg-slate-100 text-slate-500']];
@endphp
<div class="space-y-6">
    <div><h1 class="font-title text-2xl font-bold text-slate-900">Hóa đơn platform</h1>
        <p class="mt-1 text-sm text-slate-500">Hóa đơn dịch vụ nền tảng X2-BMS phát hành cho công ty.</p></div>
    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm"><div class="text-xs text-slate-500">Tổng hóa đơn</div><div class="mt-1 text-xl font-bold text-slate-900">{{ $kpi['count'] }}</div></div>
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm"><div class="text-xs text-slate-500">Tổng giá trị</div><div class="mt-1 text-xl font-bold text-slate-900">{{ $money($kpi['total']) }}</div></div>
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm"><div class="text-xs text-slate-500">Đã thanh toán</div><div class="mt-1 text-xl font-bold text-emerald-600">{{ $money($kpi['paid']) }}</div></div>
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm"><div class="text-xs text-slate-500">Còn phải trả</div><div class="mt-1 text-xl font-bold text-rose-600">{{ $money($kpi['outstanding']) }}</div></div>
    </div>
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-100 text-sm">
                <thead class="bg-slate-50/70 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                    <tr><th class="px-4 py-3">Số hóa đơn</th><th class="px-4 py-3">Kỳ</th><th class="px-4 py-3">Ngày phát hành</th><th class="px-4 py-3">Hạn</th><th class="px-4 py-3 text-right">Tổng</th><th class="px-4 py-3 text-right">Đã trả</th><th class="px-4 py-3 text-right">Còn lại</th><th class="px-4 py-3">Trạng thái</th></tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @forelse ($rows as $r)
                        <tr class="hover:bg-slate-50/60">
                            <td class="whitespace-nowrap px-4 py-3 font-semibold text-blue-600">{{ $r['no'] }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-slate-600">{{ $r['period'] }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-slate-500">{{ $r['issue'] }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-slate-500">{{ $r['due'] }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-right font-medium text-slate-800">{{ $money($r['total']) }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-right text-emerald-600">{{ $money($r['paid']) }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-right {{ $r['remaining'] > 0 ? 'text-rose-600' : 'text-slate-400' }}">{{ $money($r['remaining']) }}</td>
                            <td class="whitespace-nowrap px-4 py-3"><span class="rounded-md px-2 py-0.5 text-xs font-medium {{ $st[$r['status']][1] ?? '' }}">{{ $st[$r['status']][0] ?? $r['status'] }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="px-4 py-8 text-center text-slate-400">Chưa có hóa đơn.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
</x-filament-panels::page>
