<x-filament-panels::page>
@php
    $money = fn ($v) => number_format($v).' đ';
    $bucketBadge = [
        'over_90' => ['Quá hạn 90+ ngày', 'bg-rose-50 text-rose-700'],
        'd60_90' => ['Quá hạn 60-90 ngày', 'bg-orange-50 text-orange-700'],
        'd30_60' => ['Quá hạn 30-60 ngày', 'bg-amber-50 text-amber-700'],
        'd0_30' => ['Quá hạn 0-30 ngày', 'bg-slate-100 text-slate-600'],
    ];
@endphp
<div class="space-y-6" x-data="{ sel: null }">
    <div>
        <h1 class="font-title text-2xl font-bold text-slate-900">Top căn hộ / cư dân nợ cao</h1>
        <p class="mt-1 text-sm text-slate-500">Theo dõi các khoản nợ lớn cần ưu tiên xử lý trên toàn bộ dự án.</p>
    </div>

    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm"><div class="text-xs text-slate-500">Số hồ sơ nợ cao</div><div class="mt-1 text-2xl font-bold text-blue-600">{{ number_format($kpi['high_debt_records'] ?? 0) }}</div></div>
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm"><div class="text-xs text-slate-500">Tổng dư nợ Top 50</div><div class="mt-1 text-lg font-bold text-emerald-600">{{ $money($kpi['top50_debt'] ?? 0) }}</div></div>
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm"><div class="text-xs text-slate-500">Trường hợp quá 90 ngày</div><div class="mt-1 text-2xl font-bold text-orange-600">{{ number_format($kpi['over_90_cases'] ?? 0) }}</div></div>
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm"><div class="text-xs text-slate-500">Hồ sơ đang xử lý</div><div class="mt-1 text-2xl font-bold text-violet-600">{{ number_format($kpi['in_progress'] ?? 0) }}</div></div>
    </div>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-[1fr_320px]">
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100 text-sm">
                    <thead class="bg-slate-50/70 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <tr><th class="px-4 py-3">Mã hồ sơ</th><th class="px-4 py-3">Cư dân / KH</th><th class="px-4 py-3">Căn hộ</th><th class="px-4 py-3">Dự án</th><th class="px-4 py-3">Loại phí</th><th class="px-4 py-3 text-right">Tháng nợ</th><th class="px-4 py-3 text-right">Tổng nợ</th><th class="px-4 py-3">Trạng thái</th></tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        @foreach ($rows as $r)
                            <tr class="cursor-pointer hover:bg-blue-50/40" @click="sel = {{ \Illuminate\Support\Js::from($r) }}">
                                <td class="whitespace-nowrap px-4 py-3 font-semibold text-blue-600">{{ $r['code'] }}</td>
                                <td class="whitespace-nowrap px-4 py-3 font-medium text-slate-800">{{ $r['name'] }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-slate-500">{{ $r['apartment'] }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-slate-500">{{ $r['project'] }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-slate-500">{{ $r['fee'] }}</td>
                                <td class="px-4 py-3 text-right text-slate-600">{{ $r['months'] }}</td>
                                <td class="px-4 py-3 text-right font-semibold text-rose-600">{{ $money($r['amount']) }}</td>
                                <td class="whitespace-nowrap px-4 py-3"><span class="rounded-md px-2 py-0.5 text-xs font-medium {{ $bucketBadge[$r['bucket']][1] ?? '' }}">{{ $bucketBadge[$r['bucket']][0] ?? $r['bucket'] }}</span></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Detail panel --}}
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm" x-show="sel" x-cloak>
            <div class="flex items-center justify-between">
                <h3 class="font-title text-sm font-bold text-slate-900">Chi tiết hồ sơ</h3>
                <button @click="sel = null" class="text-slate-400 hover:text-slate-600">✕</button>
            </div>
            <div class="mt-3">
                <div class="text-lg font-bold text-slate-900" x-text="sel?.name"></div>
                <div class="text-sm text-slate-500">Căn hộ: <span x-text="sel?.apartment"></span> · <span x-text="sel?.project"></span></div>
            </div>
            <div class="mt-4 rounded-xl bg-rose-50 p-3">
                <div class="text-xs text-slate-500">Tổng dư nợ</div>
                <div class="text-2xl font-bold text-rose-600" x-text="new Intl.NumberFormat('vi-VN').format(sel?.amount || 0) + ' đ'"></div>
                <div class="text-xs text-slate-500">Số tháng nợ: <span x-text="sel?.months"></span></div>
            </div>
            <dl class="mt-4 space-y-2 text-sm">
                <div class="flex justify-between"><dt class="text-slate-500">Loại phí</dt><dd class="font-medium text-slate-700" x-text="sel?.fee"></dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">Người phụ trách</dt><dd class="font-medium text-slate-700" x-text="sel?.handler"></dd></div>
            </dl>
            <div class="mt-4 flex gap-2">
                <button class="flex-1 rounded-lg bg-blue-600 px-3 py-2 text-xs font-semibold text-white">Gửi nhắc nợ</button>
                <button class="flex-1 rounded-lg border border-slate-200 px-3 py-2 text-xs font-medium text-slate-600">Giao xử lý</button>
            </div>
        </div>
        <div class="rounded-2xl border border-dashed border-slate-200 bg-white p-8 text-center text-sm text-slate-400" x-show="!sel">Chọn một hồ sơ để xem chi tiết.</div>
    </div>
</div>
</x-filament-panels::page>
