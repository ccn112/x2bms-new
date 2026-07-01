<x-filament-panels::page>
    <x-x2.action-bar title="Báo cáo audit & kết quả xử lý"
        :subtitle="'Kỳ báo cáo: '.$period.' · SLA · MTTR · data fix · rollback · CSAT · root cause.'" />
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
        @foreach ($kpis as $kpi)
            <x-x2.kpi-card :label="$kpi['label']" :value="$kpi['value']" :accent="$kpi['accent']" />
        @endforeach
    </div>
    <x-x2.section-card title="Nhật ký audit hỗ trợ">
        {{ $this->table }}
    </x-x2.section-card>
</x-filament-panels::page>
