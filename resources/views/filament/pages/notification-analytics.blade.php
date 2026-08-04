<x-filament-panels::page>
    <x-x2.action-bar
        title="Hiệu quả thông báo"
        subtitle="Tỉ lệ đọc (open-rate) · phễu giao nhận theo kênh · chi phí kênh trả phí. Số liệu theo phạm vi của bạn." />

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ($kpis as $kpi)
            <x-x2.kpi-card :label="$kpi['label']" :value="$kpi['value']" :sub="$kpi['sub'] ?? null" :accent="$kpi['accent']" />
        @endforeach
    </div>

    <div class="rounded-2xl border border-slate-100 bg-white p-4 shadow-sm">
        <div class="mb-3 text-sm font-semibold text-slate-700">Phễu giao nhận theo kênh</div>
        @if (count($channels) === 0)
            <div class="text-sm text-slate-400">Chưa có lượt gửi qua kênh nào.</div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="text-left text-slate-500">
                        <tr class="border-b border-slate-100">
                            <th class="py-2 pr-4">Kênh</th>
                            <th class="py-2 pr-4 text-right">Tổng</th>
                            <th class="py-2 pr-4 text-right">Đã gửi/nhận</th>
                            <th class="py-2 pr-4 text-right">Đã đọc</th>
                            <th class="py-2 pr-4 text-right">Thất bại</th>
                            <th class="py-2 pr-4 text-right">Bỏ (tắt/chờ)</th>
                            <th class="py-2 pr-4 text-right">Tỉ lệ gửi</th>
                            <th class="py-2 pr-4 text-right">Tỉ lệ đọc</th>
                            <th class="py-2 pr-4 text-right">Chi phí</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($channels as $c)
                            <tr class="border-b border-slate-50">
                                <td class="py-2 pr-4 font-medium">{{ $channelLabels[$c['channel']] ?? $c['channel'] }}</td>
                                <td class="py-2 pr-4 text-right">{{ number_format($c['total']) }}</td>
                                <td class="py-2 pr-4 text-right">{{ number_format($c['delivered']) }}</td>
                                <td class="py-2 pr-4 text-right">{{ number_format($c['read']) }}</td>
                                <td class="py-2 pr-4 text-right text-rose-600">{{ number_format($c['failed']) }}</td>
                                <td class="py-2 pr-4 text-right text-amber-600">{{ number_format($c['suppressed'] + $c['pending']) }}</td>
                                <td class="py-2 pr-4 text-right">{{ $c['delivery_rate'] }}%</td>
                                <td class="py-2 pr-4 text-right">{{ $c['read_rate'] }}%</td>
                                <td class="py-2 pr-4 text-right">{{ $c['cost'] > 0 ? number_format($c['cost']).' đ' : '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <div class="rounded-2xl border border-slate-100 bg-white p-1 shadow-sm">
        {{ $this->table }}
    </div>
</x-filament-panels::page>
