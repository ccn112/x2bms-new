<x-filament-panels::page>
    <x-x2.action-bar
        title="Hàng đợi phản ánh"
        subtitle="Tiếp nhận · phân loại · giao việc · theo dõi SLA (phạm vi dự án của bạn)." />

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ($kpis as $kpi)
            <x-x2.kpi-card :label="$kpi['label']" :value="$kpi['value']" :accent="$kpi['accent']" />
        @endforeach
    </div>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-12">
        <div class="xl:col-span-9">
            <div class="rounded-2xl border border-slate-100 bg-white p-1 shadow-sm">
                {{ $this->table }}
            </div>
        </div>

        <aside class="space-y-4 xl:col-span-3">
            <x-x2.section-card title="Phản ánh theo danh mục">
                <ul class="space-y-3">
                    @forelse ($categories as $c)
                        <li>
                            <div class="mb-1 flex items-center justify-between text-sm">
                                <span class="flex items-center gap-2 font-medium text-slate-700">
                                    <span class="h-2.5 w-2.5 rounded-full" style="background-color: {{ $c['color'] ?? '#94a3b8' }}"></span>{{ $c['name'] }}
                                </span>
                                <span class="tabular-nums text-slate-500">{{ $c['count'] }}</span>
                            </div>
                            <div class="h-2 overflow-hidden rounded-full bg-slate-100">
                                <div class="h-full rounded-full" style="width: {{ round($c['count'] / $catMax * 100) }}%; background-color: {{ $c['color'] ?? '#94a3b8' }}"></div>
                            </div>
                        </li>
                    @empty
                        <li class="text-sm text-slate-400">Chưa có phản ánh.</li>
                    @endforelse
                </ul>
            </x-x2.section-card>
        </aside>
    </div>
</x-filament-panels::page>
