<x-filament-panels::page>
    {{-- DS-01-05: tab row inline with page-level actions (title stays in the topbar). --}}
    <x-x2.page.tabs :tabs="$tabs" :active="$activeTab" wire="setTab">
        <x-slot:actions>
            <x-x2.btn icon="heroicon-m-arrow-up-tray">Nhập dữ liệu</x-x2.btn>
            <x-x2.btn icon="heroicon-m-arrow-down-tray">Xuất dữ liệu</x-x2.btn>
            <x-x2.btn as="a" href="{{ url('/fila/residents/create') }}" variant="gold" icon="heroicon-m-plus">Thêm mới</x-x2.btn>
        </x-slot:actions>
    </x-x2.page.tabs>

    {{-- KPI row — context-wide totals (5 cards), Plus Jakarta numbers. --}}
    <x-x2.kpi-row :cols="5">
        @foreach ($kpis as $kpi)
            <x-x2.card.kpi
                :label="$kpi['label']"
                :value="$kpi['value']"
                :sub="$kpi['sub'] ?? null"
                :accent="$kpi['accent']"
                :icon="$kpi['icon'] ?? 'heroicon-o-chart-bar'" />
        @endforeach
    </x-x2.kpi-row>

    {{-- Filament table: search / filters / row + bulk actions / pagination --}}
    <div class="mt-4 rounded-xl border border-slate-200 bg-white p-1 shadow-sm">
        {{ $this->table }}
    </div>
</x-filament-panels::page>
