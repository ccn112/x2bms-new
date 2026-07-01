<x-filament-panels::page>
    <x-x2.action-bar title="Kho tri thức hỗ trợ"
        subtitle="SOP · runbook · FAQ · bài phổ biến · versioning · publish/archive." />
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
        @foreach ($kpis as $kpi)
            <x-x2.kpi-card :label="$kpi['label']" :value="$kpi['value']" :accent="$kpi['accent']" />
        @endforeach
    </div>
    <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
        <div class="xl:col-span-2">{{ $this->table }}</div>
        <x-x2.section-card title="Bài phổ biến">
            <ul class="divide-y divide-slate-50 text-sm">
                @foreach ($popular as $a)
                    <li class="flex items-center justify-between py-2"><span class="truncate text-slate-700">{{ Str::limit($a->title, 30) }}</span><span class="tabular-nums text-slate-400">{{ number_format($a->views) }} · ★{{ number_format((float) $a->rating, 1) }}</span></li>
                @endforeach
            </ul>
        </x-x2.section-card>
    </div>
</x-filament-panels::page>
