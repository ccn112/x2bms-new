<x-filament-panels::page>
    <x-x2.action-bar title="Hồ sơ hỗ trợ khách hàng (tenant)"
        subtitle="Gói hỗ trợ · liên hệ · entitlements · lịch sử ticket · health · CSAT · VIP notes." />
    <div class="grid gap-4 sm:grid-cols-3">
        @foreach ($kpis as $kpi)
            <x-x2.kpi-card :label="$kpi['label']" :value="$kpi['value']" :accent="$kpi['accent']" />
        @endforeach
    </div>
    {{ $this->table }}
</x-filament-panels::page>
