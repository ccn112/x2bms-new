<x-filament-panels::page>
@php
    $money = fn ($v) => number_format($v).' đ';
    $st = ['active' => ['Đang hoạt động', 'bg-emerald-50 text-emerald-700'], 'trial' => ['Trial', 'bg-sky-50 text-sky-700'], 'suspended' => ['Tạm ngừng', 'bg-slate-100 text-slate-500']];
@endphp
<div class="space-y-6">
    <div>
        <h1 class="font-title text-2xl font-bold text-slate-900">Chi tiết billing theo dự án</h1>
        <p class="mt-1 text-sm text-slate-500">Phân bổ chi phí nền tảng và pass-through theo từng dự án (kỳ 07/2026).</p>
    </div>
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-100 text-sm">
                <thead class="bg-slate-50/70 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                    <tr><th class="px-4 py-3">Mã</th><th class="px-4 py-3">Dự án</th><th class="px-4 py-3">Gói</th><th class="px-4 py-3">Trạng thái</th><th class="px-4 py-3 text-right">Phí nền tảng</th><th class="px-4 py-3 text-right">Pass-through</th><th class="px-4 py-3 text-right">Tổng chi phí</th></tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @foreach ($rows as $r)
                        <tr class="hover:bg-slate-50/60">
                            <td class="whitespace-nowrap px-4 py-3 font-semibold text-blue-600">{{ $r['code'] }}</td>
                            <td class="whitespace-nowrap px-4 py-3 font-medium text-slate-800">{{ $r['name'] }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-slate-500">{{ $r['plan'] }}</td>
                            <td class="whitespace-nowrap px-4 py-3"><span class="rounded-md px-2 py-0.5 text-xs font-medium {{ $st[$r['status']][1] ?? '' }}">{{ $st[$r['status']][0] ?? $r['status'] }}</span></td>
                            <td class="whitespace-nowrap px-4 py-3 text-right text-slate-600">{{ $money($r['fee']) }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-right text-slate-600">{{ $money($r['passthrough']) }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-right font-semibold text-slate-900">{{ $money($r['total']) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot><tr class="border-t border-slate-200 bg-slate-50/70 font-semibold"><td colspan="6" class="px-4 py-3 text-right text-slate-600">Tổng cộng</td><td class="px-4 py-3 text-right text-slate-900">{{ $money($grandTotal) }}</td></tr></tfoot>
            </table>
        </div>
    </div>
</div>
</x-filament-panels::page>
