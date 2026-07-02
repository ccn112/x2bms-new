<x-filament-panels::page>
@php
    $money = fn ($v) => number_format($v).' đ';
    $recSt = ['matched' => ['Đã khớp', 'bg-emerald-50 text-emerald-700'], 'mismatch' => ['Chênh lệch', 'bg-rose-50 text-rose-700'], 'pending' => ['Chờ đối soát', 'bg-amber-50 text-amber-700']];
    $adjSt = ['pending_approval' => ['Chờ duyệt', 'bg-amber-50 text-amber-700'], 'approved' => ['Đã duyệt', 'bg-emerald-50 text-emerald-700'], 'rejected' => ['Từ chối', 'bg-rose-50 text-rose-700'], 'need_more_info' => ['Cần bổ sung', 'bg-sky-50 text-sky-700']];
    $adjType = ['overcharge_sms' => 'Tính trùng SMS', 'duplicate_overage' => 'Trùng phí vượt', 'tax_correction' => 'Điều chỉnh thuế', 'usage_adjustment' => 'Điều chỉnh usage', 'courtesy_discount' => 'Giảm giá thiện chí', 'credit_note_issued' => 'Phát hành credit note'];
@endphp
<div class="space-y-6">
    <div>
        <h1 class="font-title text-2xl font-bold text-slate-900">Đối soát ví, hóa đơn, usage</h1>
        <p class="mt-1 text-sm text-slate-500">Đối chiếu giao dịch ngân hàng với hóa đơn và xử lý điều chỉnh chênh lệch.</p>
    </div>
    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm"><div class="text-xs text-slate-500">Đã khớp</div><div class="mt-1 text-2xl font-bold text-emerald-600">{{ $kpi['matched'] }}</div></div>
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm"><div class="text-xs text-slate-500">Chênh lệch</div><div class="mt-1 text-2xl font-bold text-rose-600">{{ $kpi['mismatch'] }}</div></div>
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm"><div class="text-xs text-slate-500">Tổng chênh lệch</div><div class="mt-1 text-lg font-bold text-slate-900">{{ $money($kpi['diffTotal']) }}</div></div>
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm"><div class="text-xs text-slate-500">Điều chỉnh chờ duyệt</div><div class="mt-1 text-2xl font-bold text-amber-600">{{ $kpi['adjPending'] }}</div></div>
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 px-4 py-3 font-title text-sm font-bold text-slate-900">Phiên đối soát</div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-100 text-sm">
                <thead class="bg-slate-50/70 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                    <tr><th class="px-4 py-3">Mã GD ngân hàng</th><th class="px-4 py-3">Hóa đơn</th><th class="px-4 py-3 text-right">Chênh lệch</th><th class="px-4 py-3">Trạng thái</th><th class="px-4 py-3">Xác nhận</th></tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @forelse ($recs as $r)
                        <tr class="hover:bg-slate-50/60">
                            <td class="whitespace-nowrap px-4 py-3 font-medium text-slate-700">{{ $r['ref'] }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-blue-600">{{ $r['invoice'] }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-right {{ $r['diff'] != 0 ? 'text-rose-600' : 'text-slate-400' }}">{{ $money($r['diff']) }}</td>
                            <td class="whitespace-nowrap px-4 py-3"><span class="rounded-md px-2 py-0.5 text-xs font-medium {{ $recSt[$r['status']][1] ?? '' }}">{{ $recSt[$r['status']][0] ?? $r['status'] }}</span></td>
                            <td class="whitespace-nowrap px-4 py-3 text-slate-500">{{ $r['confirmed'] ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-8 text-center text-slate-400">Chưa có phiên đối soát.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 px-4 py-3 font-title text-sm font-bold text-slate-900">Điều chỉnh / giảm trừ</div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-100 text-sm">
                <thead class="bg-slate-50/70 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                    <tr><th class="px-4 py-3">Mã case</th><th class="px-4 py-3">Hóa đơn</th><th class="px-4 py-3">Loại</th><th class="px-4 py-3">Lý do</th><th class="px-4 py-3 text-right">Số tiền</th><th class="px-4 py-3">Trạng thái</th></tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @forelse ($adjustments as $a)
                        <tr class="hover:bg-slate-50/60">
                            <td class="whitespace-nowrap px-4 py-3 font-medium text-slate-700">{{ $a['case'] }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-blue-600">{{ $a['invoice'] }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-slate-600">{{ $adjType[$a['type']] ?? $a['type'] }}</td>
                            <td class="px-4 py-3 text-slate-500">{{ $a['reason'] }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-right font-semibold text-slate-900">{{ $money($a['amount']) }}</td>
                            <td class="whitespace-nowrap px-4 py-3"><span class="rounded-md px-2 py-0.5 text-xs font-medium {{ $adjSt[$a['status']][1] ?? '' }}">{{ $adjSt[$a['status']][0] ?? $a['status'] }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-8 text-center text-slate-400">Chưa có điều chỉnh.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
</x-filament-panels::page>
