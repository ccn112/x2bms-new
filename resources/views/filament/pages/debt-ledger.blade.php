<x-filament-panels::page>
    {{-- Resident + apartment header with KPIs --}}
    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="grid grid-cols-1 gap-5 lg:grid-cols-[minmax(260px,340px)_1fr]">
            <div class="flex items-center gap-4">
                <span class="grid h-16 w-16 shrink-0 place-items-center rounded-full bg-slate-100 text-xl font-semibold text-slate-500">
                    {{ mb_substr($resident, 0, 1) }}
                </span>
                <div class="min-w-0">
                    <div class="flex items-center gap-2">
                        <h3 class="truncate text-lg font-semibold text-slate-900">{{ $resident }}</h3>
                        <span class="rounded-full bg-blue-50 px-2 py-0.5 text-[11px] font-medium text-x2-blue">Cư dân chính</span>
                    </div>
                    <div class="mt-0.5 text-sm text-slate-500">
                        Căn hộ {{ $apt?->code ?? '—' }} @if($apt?->building) – {{ $apt->building->name }} @endif
                    </div>
                    <div class="mt-1 text-xs text-slate-400">Mã công nợ: {{ $debt->code ?? '—' }}</div>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-3 xl:grid-cols-4">
                @foreach ($kpis as $kpi)
                    <x-x2.kpi-card :label="$kpi['label']" :value="$kpi['value']" :sub="$kpi['sub'] ?? null" :accent="$kpi['accent']" />
                @endforeach
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-[1fr_360px]">
        {{-- Per-period ledger --}}
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="flex items-center justify-between border-b border-slate-100 px-5 py-3">
                <h3 class="text-base font-semibold text-slate-900">Công nợ chi tiết theo kỳ phí</h3>
                <span class="text-xs text-slate-400">{{ count($rows) }} kỳ</span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[560px] text-sm">
                    <thead>
                        <tr class="border-b border-slate-100 text-left text-xs uppercase tracking-wide text-slate-400">
                            <th class="px-5 py-2.5 font-medium">Kỳ phí</th>
                            <th class="px-5 py-2.5 text-right font-medium">Phát sinh (đ)</th>
                            <th class="px-5 py-2.5 text-right font-medium">Đã thu (đ)</th>
                            <th class="px-5 py-2.5 text-right font-medium">Còn nợ (đ)</th>
                            <th class="px-5 py-2.5 font-medium">Trạng thái</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        @forelse ($rows as $r)
                            <tr class="hover:bg-slate-50/70">
                                <td class="whitespace-nowrap px-5 py-3 font-medium text-slate-700">{{ $r['period'] }}</td>
                                <td class="whitespace-nowrap px-5 py-3 text-right text-slate-600">{{ $r['accrued'] }}</td>
                                <td class="whitespace-nowrap px-5 py-3 text-right text-x2-green">{{ $r['collected'] }}</td>
                                <td class="whitespace-nowrap px-5 py-3 text-right {{ $r['remaining_raw'] > 0 ? 'font-medium text-x2-red' : 'text-slate-400' }}">{{ $r['remaining'] }}</td>
                                <td class="whitespace-nowrap px-5 py-3"><x-x2.status-badge :label="$r['status_label']" :tone="$r['status_tone']" /></td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-5 py-10 text-center text-sm text-slate-400">Chưa có bảng kê cho căn hộ này.</td></tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr class="border-t border-slate-200 bg-slate-50/60 font-semibold text-slate-800">
                            <td class="px-5 py-3">Tổng công nợ</td>
                            <td colspan="2"></td>
                            <td class="px-5 py-3 text-right text-x2-red">{{ $totalDebt }}</td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        {{-- Aging + quick actions --}}
        <div class="space-y-6">
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h3 class="text-base font-semibold text-slate-900">Biểu đồ tuổi nợ</h3>
                <p class="mb-4 text-xs text-slate-400">Đơn vị: đồng</p>
                <ul class="space-y-3">
                    @foreach ($buckets as $b)
                        <li>
                            <div class="mb-1 flex items-center justify-between text-xs">
                                <span class="text-slate-500">{{ $b['label'] }}</span>
                                <span class="font-medium text-slate-700">{{ $b['money'] }} · {{ $b['pct'] }}%</span>
                            </div>
                            <div class="h-2 w-full overflow-hidden rounded-full bg-slate-100">
                                <div class="h-full rounded-full {{ $loop->index >= 2 ? 'bg-x2-red' : ($loop->index === 1 ? 'bg-x2-amber' : 'bg-x2-blue') }}" style="width: {{ max($b['pct'], 1.5) }}%"></div>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h3 class="mb-3 text-base font-semibold text-slate-900">Thao tác nhanh</h3>
                <div class="grid grid-cols-2 gap-2">
                    <button class="rounded-lg border border-slate-200 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Tạo nhắc nợ</button>
                    <button class="rounded-lg border border-slate-200 px-3 py-2 text-sm font-medium text-x2-amber hover:bg-amber-50">Khóa tiện ích</button>
                    <button class="rounded-lg border border-slate-200 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Xuất sổ công nợ</button>
                    <button class="rounded-lg border border-slate-200 px-3 py-2 text-sm font-medium text-x2-primary hover:bg-slate-50">Gửi email/SMS</button>
                </div>
            </div>

            <a href="{{ url('/admin/debts') }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-slate-500 hover:text-x2-primary">← Quay lại danh sách công nợ</a>
        </div>
    </div>
</x-filament-panels::page>
