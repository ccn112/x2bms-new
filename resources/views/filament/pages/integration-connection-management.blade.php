<x-filament-panels::page>
    <x-x2.action-bar
        title="Quản lý kết nối bên ngoài"
        subtitle="Zalo · SMS · Email · FCM · VNPay · ngân hàng · e-invoice · ERP · AI · webhook gateway." />

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
        @foreach ($kpis as $kpi)
            <x-x2.kpi-card :label="$kpi['label']" :value="$kpi['value']" :accent="$kpi['accent']" />
        @endforeach
    </div>

    {{ $this->table }}
</x-filament-panels::page>
