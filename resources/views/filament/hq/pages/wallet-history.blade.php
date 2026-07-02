<x-filament-panels::page>
@php
    $money = fn ($v) => number_format($v).' đ';
    $tBadge = ['top_up' => ['Nạp ví', 'text-emerald-600', '+'], 'deduct' => ['Trừ phí', 'text-rose-600', '-'], 'allocation' => ['Phân bổ', 'text-blue-600', ''], 'refund' => ['Hoàn', 'text-emerald-600', '+'], 'adjustment' => ['Điều chỉnh', 'text-amber-600', '']];
@endphp
<div class="space-y-6">
    <div>
        <h1 class="font-title text-2xl font-bold text-slate-900">Lịch sử nạp ví & thanh toán platform</h1>
        <p class="mt-1 text-sm text-slate-500">Toàn bộ giao dịch nạp, trừ phí và phân bổ ngân sách của ví công ty.</p>
    </div>
    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm"><div class="text-xs text-slate-500">Tổng nạp</div><div class="mt-1 text-lg font-bold text-emerald-600">{{ $money($kpi['topup']) }}</div></div>
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm"><div class="text-xs text-slate-500">Tổng trừ phí</div><div class="mt-1 text-lg font-bold text-rose-600">{{ $money($kpi['deduct']) }}</div></div>
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm"><div class="text-xs text-slate-500">Phân bổ dự án</div><div class="mt-1 text-lg font-bold text-blue-600">{{ $money($kpi['allocation']) }}</div></div>
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm"><div class="text-xs text-slate-500">Số giao dịch</div><div class="mt-1 text-lg font-bold text-slate-900">{{ $kpi['count'] }}</div></div>
    </div>
    <div class="flex w-fit flex-wrap items-center gap-1 rounded-xl bg-slate-100 p-1">
        @foreach (['all' => 'Tất cả', 'top_up' => 'Nạp ví', 'deduct' => 'Trừ phí', 'allocation' => 'Phân bổ'] as $k => $l)
            <button wire:click="$set('filter', '{{ $k }}')" @class(['rounded-lg px-3 py-1.5 text-sm font-medium', 'bg-white text-blue-700 shadow-sm' => $filter === $k, 'text-slate-500' => $filter !== $k])>{{ $l }}</button>
        @endforeach
    </div>
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-100 text-sm">
                <thead class="bg-slate-50/70 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                    <tr><th class="px-4 py-3">Mã GD</th><th class="px-4 py-3">Loại</th><th class="px-4 py-3">Nội dung</th><th class="px-4 py-3 text-right">Số tiền</th><th class="px-4 py-3 text-right">Số dư sau</th><th class="px-4 py-3">Thời gian</th></tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @forelse ($rows as $r)
                        <tr class="hover:bg-slate-50/60">
                            <td class="whitespace-nowrap px-4 py-3 font-medium text-slate-700">{{ $r['ref'] }}</td>
                            <td class="whitespace-nowrap px-4 py-3"><span class="text-xs font-semibold {{ $tBadge[$r['type']][1] ?? '' }}">{{ $tBadge[$r['type']][0] ?? $r['type'] }}</span></td>
                            <td class="px-4 py-3 text-slate-600">{{ $r['desc'] }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-right font-semibold {{ $tBadge[$r['type']][1] ?? '' }}">{{ $tBadge[$r['type']][2] ?? '' }}{{ $money($r['amount']) }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-right text-slate-500">{{ $r['balance'] ? $money($r['balance']) : '—' }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-slate-500">{{ $r['at'] }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-8 text-center text-slate-400">Chưa có giao dịch.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
</x-filament-panels::page>
