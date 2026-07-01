@php
    $statusMeta = [
        'active' => ['Active', '#10b981'], 'trial' => ['Trial', '#c8a24c'], 'pending_renewal' => ['Chờ gia hạn', '#0ea5e9'],
        'past_due' => ['Quá hạn', '#ef4444'], 'suspended' => ['Tạm ngưng', '#ef4444'], 'cancelled' => ['Đã hủy', '#94a3b8'],
    ];
@endphp

<x-filament-panels::page>
    <x-x2.action-bar
        title="Tổng quan doanh thu SaaS"
        subtitle="MRR/ARR · churn · overage · hóa đơn quá hạn · top tenant · dự báo gia hạn." />

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-4">
        @foreach ($kpis as $kpi)
            <x-x2.kpi-card :label="$kpi['label']" :value="$kpi['value']" :sub="$kpi['sub'] ?? null" :accent="$kpi['accent']" />
        @endforeach
    </div>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
        <x-x2.section-card title="MRR theo gói">
            <ul class="space-y-3">
                @forelse ($mrrByPlan as $row)
                    <li>
                        <div class="mb-1 flex items-center justify-between text-sm">
                            <span class="font-medium text-slate-700">{{ $row['label'] }}</span>
                            <span class="tabular-nums text-slate-500">{{ number_format($row['value'] / 1000000, 1) }}tr</span>
                        </div>
                        <div class="h-2 overflow-hidden rounded-full bg-slate-100">
                            <div class="h-full rounded-full bg-gradient-to-r from-x2-navy to-x2-blue" style="width: {{ round($row['value'] / $mrrByPlanMax * 100) }}%"></div>
                        </div>
                    </li>
                @empty
                    <li class="text-sm text-slate-400">Chưa có thuê bao.</li>
                @endforelse
            </ul>
        </x-x2.section-card>

        <x-x2.section-card title="Phân bố trạng thái thuê bao">
            <ul class="space-y-2 text-sm">
                @foreach ($byStatus as $st => $c)
                    <li class="flex items-center justify-between">
                        <span class="flex items-center gap-2 text-slate-700">
                            <span class="h-2.5 w-2.5 rounded-full" style="background-color: {{ ($statusMeta[$st][1] ?? '#94a3b8') }}"></span>
                            {{ $statusMeta[$st][0] ?? $st }}
                        </span>
                        <span class="tabular-nums text-slate-500">{{ $c }}</span>
                    </li>
                @endforeach
            </ul>
        </x-x2.section-card>

        <x-x2.section-card title="Top tenant theo MRR">
            <ul class="divide-y divide-slate-50 text-sm">
                @forelse ($topTenants as $s)
                    <li class="flex items-center justify-between py-2">
                        <span class="truncate text-slate-700">{{ $s->tenant?->name ?? '—' }}</span>
                        <span class="ml-2 shrink-0 text-xs text-slate-400">{{ $s->plan?->name }} · {{ number_format($s->mrr / 1000000, 1) }}tr</span>
                    </li>
                @empty
                    <li class="py-2 text-slate-400">—</li>
                @endforelse
            </ul>
        </x-x2.section-card>
    </div>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
        <x-x2.section-card title="Dự báo gia hạn (sắp hết hạn)">
            <ul class="divide-y divide-slate-50 text-sm">
                @forelse ($renewalForecast as $s)
                    <li class="flex items-center justify-between py-2">
                        <span class="truncate text-slate-700">{{ $s->tenant?->name ?? '—' }}</span>
                        <span class="ml-2 shrink-0 text-xs {{ $s->end_date && $s->end_date->isPast() ? 'text-red-500' : 'text-slate-400' }}">
                            HĐ đến {{ $s->end_date?->format('d/m/Y') ?? '—' }}{{ $s->auto_renew ? ' · tự gia hạn' : '' }}
                        </span>
                    </li>
                @empty
                    <li class="py-2 text-slate-400">—</li>
                @endforelse
            </ul>
        </x-x2.section-card>

        <x-x2.section-card title="Hóa đơn quá hạn">
            <ul class="divide-y divide-slate-50 text-sm">
                @forelse ($overdueInvoices as $inv)
                    <li class="flex items-center justify-between py-2">
                        <span class="truncate text-slate-700">{{ $inv->invoice_no }} · {{ $inv->tenant?->name }}</span>
                        <span class="ml-2 shrink-0 rounded bg-red-50 px-2 py-0.5 text-xs text-red-600">{{ number_format($inv->remaining_amount / 1000000, 1) }}tr</span>
                    </li>
                @empty
                    <li class="py-2 text-slate-400">Không có hóa đơn quá hạn.</li>
                @endforelse
            </ul>
        </x-x2.section-card>
    </div>
</x-filament-panels::page>
