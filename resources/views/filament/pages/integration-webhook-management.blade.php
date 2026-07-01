<x-filament-panels::page>
    <x-x2.action-bar
        title="Quản lý Webhook Endpoint"
        subtitle="URL · nhóm sự kiện · HMAC · retry policy · success rate · lịch sử delivery." />

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
        @foreach ($kpis as $kpi)
            <x-x2.kpi-card :label="$kpi['label']" :value="$kpi['value']" :accent="$kpi['accent']" />
        @endforeach
    </div>

    {{ $this->table }}
</x-filament-panels::page>
