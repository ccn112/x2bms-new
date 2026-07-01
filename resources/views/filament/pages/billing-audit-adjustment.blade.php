<x-filament-panels::page>
    <x-x2.action-bar
        title="Audit billing & điều chỉnh hóa đơn"
        subtitle="Case điều chỉnh · duyệt/từ chối/bổ sung · phát hành credit note · timeline audit đầy đủ." />

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ($kpis as $kpi)
            <x-x2.kpi-card :label="$kpi['label']" :value="$kpi['value']" :accent="$kpi['accent']" />
        @endforeach
    </div>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-12">
        <div class="xl:col-span-8">
            <div class="rounded-2xl border border-slate-100 bg-white p-1 shadow-sm">{{ $this->table }}</div>
        </div>
        <aside class="space-y-4 xl:col-span-4">
            <x-x2.section-card title="Nhật ký billing gần đây">
                <ol class="relative space-y-3 border-l border-slate-100 pl-4 text-sm">
                    @forelse ($auditTimeline as $log)
                        <li class="relative">
                            <span class="absolute -left-[21px] top-1 h-2.5 w-2.5 rounded-full bg-x2-blue"></span>
                            <p class="font-medium text-slate-700">{{ $log->action }}</p>
                            <p class="text-xs text-slate-400">
                                {{ $log->entity_type }}#{{ $log->entity_id }} · {{ $log->actor?->name ?? 'Hệ thống' }}
                                · {{ $log->created_at?->format('d/m H:i') }}
                            </p>
                            @if ($log->reason)<p class="text-xs text-slate-500">{{ $log->reason }}</p>@endif
                        </li>
                    @empty
                        <li class="text-slate-400">Chưa có nhật ký.</li>
                    @endforelse
                </ol>
            </x-x2.section-card>
        </aside>
    </div>
</x-filament-panels::page>
