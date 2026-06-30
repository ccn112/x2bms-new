@php
    $wfTone = ['active' => 'green', 'paused' => 'amber', 'draft' => 'slate'];
    $wfLabel = ['active' => 'Đang chạy', 'paused' => 'Tạm dừng', 'draft' => 'Nháp'];
    $triggerLabel = ['event' => 'Theo sự kiện', 'schedule' => 'Theo lịch', 'manual' => 'Thủ công'];
    $stepStyle = [
        'trigger' => ['bg-x2-navy text-white', 'M13 10V3L4 14h7v7l9-11h-7z'],
        'ai' => ['bg-x2-gold/15 text-x2-navy ring-1 ring-x2-gold/40', 'M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.3 6.2L22 12l-6.7 2.8L13 21l-2.3-6.2L4 12l6.7-2.8L13 3z'],
        'condition' => ['bg-x2-blue/10 text-x2-blue', 'M3 7h18M3 12h18M3 17h18'],
        'action' => ['bg-x2-green/10 text-x2-green', 'M5 13l4 4L19 7'],
    ];
@endphp

<x-filament-panels::page>
    <x-x2.action-bar
        title="Thiết kế Workflow Automation"
        subtitle="Tạo & theo dõi các luồng tự động hoá có AI: kích hoạt → xử lý → điều kiện → hành động." />

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ($kpis as $kpi)
            <x-x2.kpi-card :label="$kpi['label']" :value="$kpi['value']" :accent="$kpi['accent']" />
        @endforeach
    </div>

    <div x-data="{ sel: {{ $workflows->first()?->id ?? 'null' }} }" class="grid grid-cols-1 gap-6 xl:grid-cols-12">
        {{-- LEFT: workflow list --}}
        <div class="xl:col-span-3">
            <x-x2.section-card title="Workflow" subtitle="{{ $workflows->count() }} luồng">
                <div class="space-y-1.5">
                    @foreach ($workflows as $wf)
                        <button type="button" @click="sel = {{ $wf->id }}"
                                :class="sel === {{ $wf->id }} ? 'border-x2-gold bg-x2-gold/5' : 'border-transparent hover:bg-slate-50'"
                                class="w-full rounded-lg border px-3 py-2.5 text-left transition">
                            <div class="flex items-center justify-between gap-2">
                                <span class="truncate text-sm font-medium text-slate-800">{{ $wf->name }}</span>
                                <x-x2.status-badge :label="$wfLabel[$wf->status] ?? $wf->status" :tone="$wfTone[$wf->status] ?? 'slate'" />
                            </div>
                            <div class="mt-0.5 text-xs text-slate-400">{{ $triggerLabel[$wf->trigger_type] ?? $wf->trigger_type }} · {{ $wf->schedule }}</div>
                        </button>
                    @endforeach
                </div>
            </x-x2.section-card>
        </div>

        {{-- MIDDLE: canvas + config for the selected workflow --}}
        <div class="space-y-6 xl:col-span-9">
            @foreach ($workflows as $wf)
                <div x-show="sel === {{ $wf->id }}" x-cloak class="space-y-6">
                    {{-- Canvas --}}
                    <x-x2.section-card :title="$wf->name" :subtitle="$wf->description">
                        <x-slot:action>
                            <x-x2.status-badge :label="$wfLabel[$wf->status] ?? $wf->status" :tone="$wfTone[$wf->status] ?? 'slate'" />
                        </x-slot:action>
                        <div class="flex flex-wrap items-stretch gap-2 overflow-x-auto py-2">
                            @foreach ($wf->steps ?? [] as $i => $step)
                                @php [$cls, $icon] = $stepStyle[$step['type']] ?? $stepStyle['action']; @endphp
                                <div class="flex items-center gap-2">
                                    <div class="w-40 rounded-xl border border-slate-200 bg-white p-3 shadow-sm">
                                        <span class="grid h-8 w-8 place-items-center rounded-lg {{ $cls }}">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}"/></svg>
                                        </span>
                                        <div class="mt-2 text-[11px] font-semibold uppercase tracking-wide text-slate-400">{{ ['trigger'=>'Kích hoạt','ai'=>'AI','condition'=>'Điều kiện','action'=>'Hành động'][$step['type']] ?? 'Bước' }}</div>
                                        <div class="text-xs text-slate-700">{{ $step['label'] }}</div>
                                    </div>
                                    @if (! $loop->last)
                                        <svg class="h-4 w-4 shrink-0 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </x-x2.section-card>

                    {{-- Config + run log --}}
                    <div class="grid gap-6 lg:grid-cols-2">
                        <x-x2.section-card title="Cấu hình">
                            <dl class="space-y-2.5 text-sm">
                                <div class="flex justify-between"><dt class="text-slate-500">Kiểu kích hoạt</dt><dd class="font-medium text-slate-800">{{ $triggerLabel[$wf->trigger_type] ?? $wf->trigger_type }}</dd></div>
                                <div class="flex justify-between"><dt class="text-slate-500">Lịch / điều kiện</dt><dd class="font-medium text-slate-800">{{ $wf->schedule ?? '—' }}</dd></div>
                                <div class="flex justify-between"><dt class="text-slate-500">Số bước</dt><dd class="font-medium text-slate-800">{{ count($wf->steps ?? []) }}</dd></div>
                                <div class="flex justify-between"><dt class="text-slate-500">Tổng lần chạy</dt><dd class="font-medium text-slate-800">{{ number_format($wf->runs_count) }}</dd></div>
                                <div class="flex justify-between"><dt class="text-slate-500">Tỷ lệ thành công</dt><dd class="font-medium text-x2-green">{{ $wf->runs_count ? round($wf->success_count / $wf->runs_count * 100, 1) : 0 }}%</dd></div>
                                <div class="flex justify-between"><dt class="text-slate-500">Chạy gần nhất</dt><dd class="font-medium text-slate-800">{{ $wf->last_run_at?->diffForHumans() ?? 'Chưa chạy' }}</dd></div>
                            </dl>
                        </x-x2.section-card>

                        <x-x2.section-card title="Nhật ký chạy gần đây">
                            <ol class="space-y-2.5">
                                @forelse ($wf->runs as $run)
                                    <li class="flex items-start gap-2 text-sm">
                                        <span @class([
                                            'mt-1 h-2 w-2 shrink-0 rounded-full',
                                            'bg-x2-green' => $run->status === 'success',
                                            'bg-x2-red' => $run->status === 'failed',
                                            'bg-x2-amber' => $run->status === 'running',
                                        ])></span>
                                        <div class="min-w-0">
                                            <div class="text-slate-700">{{ $run->note }}</div>
                                            <div class="text-xs text-slate-400">{{ $run->started_at?->format('d/m H:i') }} · {{ number_format($run->duration_ms) }}ms</div>
                                        </div>
                                    </li>
                                @empty
                                    <li class="text-sm text-slate-400">Chưa có lần chạy nào.</li>
                                @endforelse
                            </ol>
                        </x-x2.section-card>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</x-filament-panels::page>
