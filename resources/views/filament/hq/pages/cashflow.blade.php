<x-filament-panels::page>
@php
    $ty = fn ($v) => number_format($v / 1_000_000_000, 2).' tỷ';
    $tr = fn ($v) => number_format($v, 2);
@endphp
<div class="space-y-6">
    <div>
        <h1 class="font-title text-2xl font-bold text-slate-900">Thu chi & dòng tiền đa dự án</h1>
        <p class="mt-1 text-sm text-slate-500">Phân tích doanh thu, chi phí và dòng tiền hợp nhất của toàn bộ dự án. Đơn vị bảng: tỷ đồng.</p>
    </div>

    <div class="grid grid-cols-2 gap-4 md:grid-cols-3 xl:grid-cols-6">
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm"><div class="text-xs text-slate-500">Doanh thu tháng</div><div class="mt-1 text-lg font-bold text-blue-600">{{ $ty($kpi['revenue'] ?? 0) }}</div></div>
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm"><div class="text-xs text-slate-500">Chi phí tháng</div><div class="mt-1 text-lg font-bold text-orange-600">{{ $ty($kpi['expense'] ?? 0) }}</div></div>
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm"><div class="text-xs text-slate-500">Dòng tiền thuần</div><div class="mt-1 text-lg font-bold text-emerald-600">{{ $ty($kpi['netflow'] ?? 0) }}</div></div>
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm"><div class="text-xs text-slate-500">Ngân sách sử dụng</div><div class="mt-1 text-lg font-bold text-violet-600">{{ $kpi['budget_used_pct'] ?? 0 }}%</div></div>
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm"><div class="text-xs text-slate-500">Công nợ phải thu</div><div class="mt-1 text-lg font-bold text-blue-600">{{ $ty($kpi['receivable'] ?? 0) }}</div></div>
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm"><div class="text-xs text-slate-500">Quỹ dự phòng</div><div class="mt-1 text-lg font-bold text-slate-900">{{ $ty($kpi['reserve'] ?? 0) }}</div></div>
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 px-4 py-3 font-title text-sm font-bold text-slate-900">Hiệu quả tài chính theo dự án</div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-100 text-sm">
                <thead class="bg-slate-50/70 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                    <tr><th class="px-4 py-2">Dự án</th><th class="px-4 py-2 text-right">Doanh thu</th><th class="px-4 py-2 text-right">Thực thu</th><th class="px-4 py-2 text-right">Chi vận hành</th><th class="px-4 py-2 text-right">Chi bảo trì</th><th class="px-4 py-2 text-right">Lợi nhuận gộp</th><th class="px-4 py-2 text-right">Dòng tiền</th><th class="px-4 py-2 text-right">Ngân sách</th><th class="px-4 py-2 text-right">Thực chi</th><th class="px-4 py-2 text-right">% SD</th></tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @foreach ($projects as $p)
                        <tr class="hover:bg-slate-50/60">
                            <td class="whitespace-nowrap px-4 py-2 font-medium text-slate-800">{{ $p['project'] }}</td>
                            <td class="px-4 py-2 text-right text-slate-600">{{ $tr($p['revenue']) }}</td>
                            <td class="px-4 py-2 text-right text-slate-600">{{ $tr($p['actual']) }}</td>
                            <td class="px-4 py-2 text-right text-slate-600">{{ $tr($p['opex']) }}</td>
                            <td class="px-4 py-2 text-right text-slate-600">{{ $tr($p['maintenance']) }}</td>
                            <td class="px-4 py-2 text-right font-medium text-emerald-600">{{ $tr($p['gross']) }}</td>
                            <td class="px-4 py-2 text-right text-slate-700">{{ $tr($p['netflow']) }}</td>
                            <td class="px-4 py-2 text-right text-slate-500">{{ $tr($p['budget']) }}</td>
                            <td class="px-4 py-2 text-right text-slate-500">{{ $tr($p['spent']) }}</td>
                            <td class="px-4 py-2 text-right text-slate-500">{{ $p['sd_pct'] }}%</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <h3 class="font-title text-sm font-bold text-slate-900">Phê duyệt chi chờ xử lý</h3>
        <ul class="mt-3 divide-y divide-slate-50">
            @forelse ($pending as $e)
                <li class="flex items-center justify-between py-2 text-sm">
                    <span class="text-slate-700">{{ $e['desc'] }}</span>
                    <span class="font-semibold text-slate-900">{{ number_format($e['amount'] / 1_000_000, 0) }} tr</span>
                </li>
            @empty
                <li class="py-2 text-sm text-slate-400">Không có đề nghị chi chờ duyệt.</li>
            @endforelse
        </ul>
    </div>
</div>
</x-filament-panels::page>
