@php
    $statusMeta = [
        'active' => ['Hoạt động', '#10b981'], 'warning' => ['Cảnh báo', '#f59e0b'],
        'incident' => ['Sự cố', '#ef4444'], 'disabled' => ['Tắt', '#94a3b8'], 'archived' => ['Lưu trữ', '#94a3b8'],
    ];
    $evMeta = [
        'success' => ['Thành công', '#10b981'], 'failed' => ['Thất bại', '#ef4444'],
        'warning' => ['Cảnh báo', '#f59e0b'], 'pending' => ['Chờ', '#0ea5e9'],
    ];
@endphp

<x-filament-panels::page>
    <x-x2.action-bar
        title="Trung tâm tích hợp"
        subtitle="Kết nối bên ngoài · API · webhook · event log · retry queue · sức khỏe & sự cố." />

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
        @foreach ($kpis as $kpi)
            <x-x2.kpi-card :label="$kpi['label']" :value="$kpi['value']" :accent="$kpi['accent']" />
        @endforeach
    </div>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
        <div class="xl:col-span-2">
            <x-x2.section-card title="Kết nối theo nhóm">
                @foreach ($connectionsByCategory as $cat => $conns)
                    <div class="mb-4">
                        <div class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-400">{{ $cat }}</div>
                        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                            @foreach ($conns as $c)
                                <div class="rounded-xl border border-slate-100 bg-white p-3">
                                    <div class="flex items-center justify-between">
                                        <span class="truncate text-sm font-medium text-slate-700">{{ $c->name }}</span>
                                        <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[11px] font-medium"
                                              style="color: {{ $statusMeta[$c->status][1] ?? '#94a3b8' }}; background: {{ ($statusMeta[$c->status][1] ?? '#94a3b8') }}1a">
                                            {{ $statusMeta[$c->status][0] ?? $c->status }}
                                        </span>
                                    </div>
                                    <div class="mt-2 flex items-center justify-between text-[11px] text-slate-400">
                                        <span>{{ strtoupper($c->environment) }} · {{ $c->api_version }}</span>
                                        <span class="tabular-nums">{{ $c->success_rate_24h !== null ? number_format($c->success_rate_24h, 1).'%' : '—' }} · {{ $c->avg_latency_ms ?? '—' }}ms</span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </x-x2.section-card>
        </div>

        <div class="space-y-6">
            <x-x2.section-card title="Sự kiện theo trạng thái">
                <ul class="space-y-2 text-sm">
                    @foreach ($eventStatus as $st => $c)
                        <li>
                            <div class="mb-1 flex items-center justify-between">
                                <span class="flex items-center gap-2 text-slate-700">
                                    <span class="h-2.5 w-2.5 rounded-full" style="background: {{ $evMeta[$st][1] ?? '#94a3b8' }}"></span>
                                    {{ $evMeta[$st][0] ?? $st }}
                                </span>
                                <span class="tabular-nums text-slate-500">{{ $c }}</span>
                            </div>
                            <div class="h-1.5 overflow-hidden rounded-full bg-slate-100">
                                <div class="h-full rounded-full" style="width: {{ round($c / $eventTotal * 100) }}%; background: {{ $evMeta[$st][1] ?? '#94a3b8' }}"></div>
                            </div>
                        </li>
                    @endforeach
                </ul>
                <div class="mt-3 border-t border-slate-50 pt-3 text-sm text-slate-500">
                    Webhook success rate TB: <span class="font-semibold text-slate-700">{{ number_format($webhookAvg, 1) }}%</span>
                </div>
            </x-x2.section-card>

            <x-x2.section-card title="Sự cố đang mở">
                <ul class="divide-y divide-slate-50 text-sm">
                    @forelse ($openIncidents as $inc)
                        <li class="flex items-center justify-between py-2">
                            <span class="truncate text-slate-700">{{ $inc->title }}</span>
                            <span class="rounded-full bg-red-50 px-2 py-0.5 text-[11px] font-medium text-red-600">{{ $inc->severity }}</span>
                        </li>
                    @empty
                        <li class="py-2 text-slate-400">Không có sự cố.</li>
                    @endforelse
                </ul>
            </x-x2.section-card>

            <x-x2.section-card title="Credential sắp hết hạn">
                <ul class="divide-y divide-slate-50 text-sm">
                    @forelse ($expiringCreds as $cr)
                        <li class="flex items-center justify-between py-2">
                            <span class="truncate text-slate-700">{{ $cr->connection?->name ?? '—' }}</span>
                            <span class="tabular-nums text-amber-600">{{ $cr->expires_at?->format('d/m/Y') ?? '—' }}</span>
                        </li>
                    @empty
                        <li class="py-2 text-slate-400">Không có.</li>
                    @endforelse
                </ul>
            </x-x2.section-card>
        </div>
    </div>

    <x-x2.section-card title="Sự kiện gần đây">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-xs uppercase tracking-wide text-slate-400">
                        <th class="pb-2">Event ID</th><th class="pb-2">Nguồn</th><th class="pb-2">Loại</th>
                        <th class="pb-2">Trạng thái</th><th class="pb-2 text-right">Thời lượng</th><th class="pb-2">Thông điệp</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @foreach ($recentEvents as $e)
                        <tr>
                            <td class="py-2 font-mono text-[11px] text-slate-500">{{ Str::limit($e->event_id, 18) }}</td>
                            <td class="py-2 text-slate-700">{{ $e->source }}</td>
                            <td class="py-2 text-slate-500">{{ $e->event_type }}</td>
                            <td class="py-2"><span class="rounded-full px-2 py-0.5 text-[11px] font-medium" style="color: {{ $evMeta[$e->status][1] ?? '#94a3b8' }}; background: {{ ($evMeta[$e->status][1] ?? '#94a3b8') }}1a">{{ $evMeta[$e->status][0] ?? $e->status }}</span></td>
                            <td class="py-2 text-right tabular-nums text-slate-500">{{ $e->duration_ms }}ms</td>
                            <td class="py-2 text-slate-500">{{ Str::limit($e->message, 40) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-x2.section-card>
</x-filament-panels::page>
