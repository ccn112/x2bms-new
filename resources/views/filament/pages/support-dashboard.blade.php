@php
    $statusMeta = [
        'new' => ['Mới', '#0ea5e9'], 'open' => ['Đang mở', '#0ea5e9'], 'in_progress' => ['Đang xử lý', '#c8a24c'],
        'waiting_customer' => ['Chờ KH', '#f59e0b'], 'escalated' => ['Escalated', '#ef4444'],
        'resolved' => ['Đã xử lý', '#10b981'], 'closed' => ['Đóng', '#94a3b8'], 'reopened' => ['Mở lại', '#f59e0b'],
    ];
    $prMeta = ['critical' => '#ef4444', 'high' => '#f59e0b', 'medium' => '#0ea5e9', 'low' => '#10b981'];
@endphp

<x-filament-panels::page>
    <x-x2.action-bar title="Support Dashboard"
        subtitle="Ticket · SLA · escalation · data correction · CSAT · hiệu suất hỗ trợ." />

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
        @foreach ($kpis as $kpi)
            <x-x2.kpi-card :label="$kpi['label']" :value="$kpi['value']" :accent="$kpi['accent']" />
        @endforeach
    </div>

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        @foreach ($priority as $p)
            <x-x2.kpi-card :label="'Ticket '.$p['label']" :value="$p['value']" :accent="$p['accent']" />
        @endforeach
    </div>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
        <div class="xl:col-span-2">
            <x-x2.section-card title="Ticket gần đây">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead><tr class="text-left text-xs uppercase tracking-wide text-slate-400">
                            <th class="pb-2">Mã</th><th class="pb-2">Tiêu đề</th><th class="pb-2">Tenant</th>
                            <th class="pb-2">Ưu tiên</th><th class="pb-2">Trạng thái</th><th class="pb-2">Owner</th>
                        </tr></thead>
                        <tbody class="divide-y divide-slate-50">
                            @foreach ($recentTickets as $t)
                                <tr>
                                    <td class="py-2 font-mono text-[11px] text-slate-500">{{ $t->ticket_no }}</td>
                                    <td class="py-2 text-slate-700">{{ Str::limit($t->subject, 34) }}</td>
                                    <td class="py-2 text-slate-500">{{ $t->tenant?->name ?? '—' }}</td>
                                    <td class="py-2"><span class="rounded-full px-2 py-0.5 text-[11px] font-medium" style="color: {{ $prMeta[$t->priority] ?? '#94a3b8' }}; background: {{ ($prMeta[$t->priority] ?? '#94a3b8') }}1a">{{ ucfirst($t->priority) }}</span></td>
                                    <td class="py-2"><span class="rounded-full px-2 py-0.5 text-[11px] font-medium" style="color: {{ $statusMeta[$t->status][1] ?? '#94a3b8' }}; background: {{ ($statusMeta[$t->status][1] ?? '#94a3b8') }}1a">{{ $statusMeta[$t->status][0] ?? $t->status }}</span></td>
                                    <td class="py-2 text-slate-500">{{ $t->owner?->name ?? '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-x2.section-card>
        </div>
        <div class="space-y-6">
            <x-x2.section-card title="Ticket theo trạng thái">
                <ul class="space-y-2 text-sm">
                    @foreach ($byStatus as $st => $c)
                        <li>
                            <div class="mb-1 flex items-center justify-between">
                                <span class="flex items-center gap-2 text-slate-700"><span class="h-2.5 w-2.5 rounded-full" style="background: {{ $statusMeta[$st][1] ?? '#94a3b8' }}"></span>{{ $statusMeta[$st][0] ?? $st }}</span>
                                <span class="tabular-nums text-slate-500">{{ $c }}</span>
                            </div>
                            <div class="h-1.5 overflow-hidden rounded-full bg-slate-100"><div class="h-full rounded-full" style="width: {{ round($c / $statusTotal * 100) }}%; background: {{ $statusMeta[$st][1] ?? '#94a3b8' }}"></div></div>
                        </li>
                    @endforeach
                </ul>
                <div class="mt-3 border-t border-slate-50 pt-3 text-sm text-slate-500">Data correction đang mở: <span class="font-semibold text-slate-700">{{ $openCorrections }}</span></div>
            </x-x2.section-card>
            <x-x2.section-card title="Escalation đang mở">
                <ul class="divide-y divide-slate-50 text-sm">
                    @forelse ($escalatedTickets as $t)
                        <li class="flex items-center justify-between py-2"><span class="truncate text-slate-700">{{ $t->ticket_no }} · {{ Str::limit($t->subject, 22) }}</span><span class="rounded-full bg-red-50 px-2 py-0.5 text-[11px] font-medium text-red-600">{{ ucfirst($t->priority) }}</span></li>
                    @empty
                        <li class="py-2 text-slate-400">Không có.</li>
                    @endforelse
                </ul>
            </x-x2.section-card>
        </div>
    </div>
</x-filament-panels::page>
