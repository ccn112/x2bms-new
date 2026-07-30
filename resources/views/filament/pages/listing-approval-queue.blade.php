<x-filament-panels::page>
    <x-x2.action-bar title="Duyệt tin rao BĐS"
        subtitle="Duyệt/từ chối tin rao của dự án hiện tại · đẩy tin nghi ngờ lên SuperAdmin · bật/tắt duyệt tự động." />

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        @foreach ($kpis as $kpi)
            <x-x2.kpi-card :label="$kpi['label']" :value="$kpi['value']" :accent="$kpi['accent']" />
        @endforeach
    </div>

    {{ $this->table }}
</x-filament-panels::page>
