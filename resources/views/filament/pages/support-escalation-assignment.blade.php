<x-filament-panels::page>
    <x-x2.action-bar title="Escalation & phân công hỗ trợ"
        subtitle="Workload theo team · auto-assign · cân bằng tải · escalation events · rủi ro SLA." />
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
        @foreach ($kpis as $kpi)
            <x-x2.kpi-card :label="$kpi['label']" :value="$kpi['value']" :accent="$kpi['accent']" />
        @endforeach
    </div>
    <x-x2.section-card title="Tải theo team">
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($workload as $team)
                <div class="rounded-xl border border-slate-100 bg-white p-3">
                    <div class="flex items-center justify-between"><span class="font-medium text-slate-700">{{ $team->name }}</span><span class="rounded-full bg-slate-100 px-2 py-0.5 text-[11px] text-slate-500">{{ $team->level }}</span></div>
                    <div class="mt-2 text-2xl font-semibold text-slate-800">{{ $team->open_tickets }} <span class="text-xs font-normal text-slate-400">ticket mở</span></div>
                    <div class="mt-1 text-[11px] text-slate-400">{{ $team->member_count }} thành viên · SLA {{ $team->sla_target_response_minutes }}′</div>
                </div>
            @endforeach
        </div>
    </x-x2.section-card>
    {{ $this->table }}
</x-filament-panels::page>
