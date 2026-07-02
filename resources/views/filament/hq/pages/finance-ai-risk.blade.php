<x-filament-panels::page>
@php
    $ty = fn ($v) => number_format($v / 1_000_000_000, 2).' tỷ';
    $sevBadge = ['critical' => 'bg-rose-100 text-rose-700', 'high' => 'bg-orange-100 text-orange-700', 'medium' => 'bg-amber-100 text-amber-700', 'low' => 'bg-emerald-100 text-emerald-700'];
    $scoreColor = fn ($s) => $s >= 80 ? 'bg-rose-500' : ($s >= 60 ? 'bg-orange-500' : ($s >= 45 ? 'bg-amber-500' : 'bg-emerald-500'));
    $maxF = max($forecast->flatMap(fn ($f) => [$f['actual'], $f['target']])->all() ?: [1]);
@endphp
<div class="space-y-6">
    <div>
        <h1 class="font-title text-2xl font-bold text-slate-900">AI phân tích rủi ro công nợ & dự báo tài chính</h1>
        <p class="mt-1 text-sm text-slate-500">Sử dụng AI để phát hiện rủi ro, dự báo thu hồi công nợ và đề xuất hành động.</p>
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-5">
        <div class="rounded-2xl border border-amber-200 bg-amber-50/40 p-5 shadow-sm"><div class="text-sm text-slate-500">Điểm rủi ro danh mục nợ</div><div class="mt-1 text-2xl font-bold text-amber-600">{{ (int) ($kpi['portfolio_risk'] ?? 0) }}<span class="text-base font-normal text-slate-400">/100</span></div><div class="text-xs text-amber-600">Rủi ro cao</div></div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><div class="text-sm text-slate-500">Dự báo thu tháng tới</div><div class="mt-1 text-2xl font-bold text-blue-600">{{ $ty($kpi['forecast_collection'] ?? 0) }}</div></div>
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50/40 p-5 shadow-sm"><div class="text-sm text-slate-500">Xác suất thu hồi (bình quân)</div><div class="mt-1 text-2xl font-bold text-emerald-600">{{ $kpi['avg_recovery_prob'] ?? 0 }}%</div></div>
        <div class="rounded-2xl border border-rose-200 bg-rose-50/40 p-5 shadow-sm"><div class="text-sm text-slate-500">Số cảnh báo AI</div><div class="mt-1 text-2xl font-bold text-rose-600">{{ (int) ($kpi['alerts'] ?? 0) }}</div></div>
        <div class="rounded-2xl border border-violet-200 bg-violet-50/40 p-5 shadow-sm"><div class="text-sm text-slate-500">Dự án rủi ro cao</div><div class="mt-1 text-2xl font-bold text-violet-600">{{ (int) ($kpi['high_risk_projects'] ?? 0) }}<span class="text-base font-normal text-slate-400">/28</span></div></div>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h3 class="font-title text-sm font-bold text-slate-900">Dự báo thu hồi công nợ (3 tháng tới)</h3>
            <div class="mt-4 flex h-52 items-end justify-between gap-6">
                @foreach ($forecast as $f)
                    <div class="flex flex-1 flex-col items-center gap-2">
                        <div class="flex w-full items-end justify-center gap-1" style="height: 160px">
                            <div class="w-1/2 rounded-t bg-blue-500" style="height: {{ max(6, round($f['actual'] / $maxF * 150)) }}px" title="Thực thu"></div>
                            <div class="w-1/2 rounded-t border-2 border-dashed border-emerald-400 bg-emerald-100" style="height: {{ max(6, round($f['target'] / $maxF * 150)) }}px" title="Mục tiêu"></div>
                        </div>
                        <span class="text-xs font-semibold text-slate-700">{{ number_format($f['actual'], 2) }}</span>
                        <span class="text-[10px] text-slate-400">{{ \Illuminate\Support\Str::after($f['period'], '-') }}/{{ \Illuminate\Support\Str::before($f['period'], '-') }}</span>
                    </div>
                @endforeach
            </div>
            <div class="mt-3 flex items-center gap-4 text-xs text-slate-500"><span class="flex items-center gap-1"><span class="h-2.5 w-2.5 rounded bg-blue-500"></span>Thực/Dự báo</span><span class="flex items-center gap-1"><span class="h-2.5 w-2.5 rounded border border-dashed border-emerald-400 bg-emerald-100"></span>Mục tiêu</span></div>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h3 class="font-title text-sm font-bold text-slate-900">Tóm tắt phân tích AI</h3>
            <ul class="mt-3 space-y-2.5 text-sm text-slate-600">
                <li class="flex gap-2"><span class="text-rose-500">●</span> 7 dự án có rủi ro cao, chiếm 61% tổng dư nợ. Tập trung tại Sunshine Riverside, Sunshine City Sài Gòn.</li>
                <li class="flex gap-2"><span class="text-amber-500">●</span> Nhóm khách hàng doanh nghiệp có xác suất thu hồi thấp nhất (48.1%) và thời gian trễ dự kiến cao nhất (42 ngày).</li>
                <li class="flex gap-2"><span class="text-blue-500">●</span> Dự báo thu tháng 07/2026 đạt 28.45 tỷ, thấp hơn 9.1% so với mục tiêu (31.25 tỷ).</li>
                <li class="flex gap-2"><span class="text-emerald-500">●</span> 3 khách hàng có khả năng quá hạn &gt; 60 ngày, cần ưu tiên xử lý ngay.</li>
            </ul>
        </div>
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 px-4 py-3 font-title text-sm font-bold text-slate-900">Xếp hạng rủi ro công nợ (Top 10)</div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-100 text-sm">
                <thead class="bg-slate-50/70 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                    <tr><th class="px-4 py-3">#</th><th class="px-4 py-3">Dự án / Khách hàng</th><th class="px-4 py-3">Nhóm KH</th><th class="px-4 py-3 text-right">Dư nợ (tỷ)</th><th class="px-4 py-3 text-center">AI điểm rủi ro</th><th class="px-4 py-3 text-right">Xác suất thu hồi</th><th class="px-4 py-3 text-right">Dự kiến trễ</th><th class="px-4 py-3">Đề xuất hành động</th><th class="px-4 py-3">Phụ trách</th></tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @foreach ($risks as $i => $r)
                        <tr class="hover:bg-slate-50/60">
                            <td class="px-4 py-3 text-slate-400">{{ $i + 1 }}</td>
                            <td class="whitespace-nowrap px-4 py-3 font-medium text-slate-800">{{ $r['name'] }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-slate-500">{{ $r['group'] }}</td>
                            <td class="px-4 py-3 text-right font-semibold text-slate-900">{{ number_format($r['debt'], 2) }}</td>
                            <td class="px-4 py-3 text-center"><span class="inline-flex h-7 w-9 items-center justify-center rounded-md text-xs font-bold text-white {{ $scoreColor($r['score']) }}">{{ (int) $r['score'] }}</span></td>
                            <td class="px-4 py-3 text-right text-slate-600">{{ $r['prob'] }}%</td>
                            <td class="px-4 py-3 text-right text-slate-500">{{ $r['delay'] }} ngày</td>
                            <td class="whitespace-nowrap px-4 py-3"><span class="rounded-md px-2 py-0.5 text-xs font-medium {{ $sevBadge[$r['severity']] ?? '' }}">{{ $r['action'] }}</span></td>
                            <td class="whitespace-nowrap px-4 py-3 text-slate-500">{{ $r['handler'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
</x-filament-panels::page>
