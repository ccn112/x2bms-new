<x-filament-panels::page>
    {{-- BQL density layer (handoff 0307): scoped .x2-bql-page controls spacing/density. --}}
    <div class="x2-bql-page">
        {{-- DS-01: tab trang inline với action page-level (tiêu đề nằm ở topbar). --}}
        <x-x2.page.tabs :tabs="$tabs" :active="$activeTab">
            <x-slot:actions>
                <x-x2.btn icon="heroicon-m-arrow-up-tray" wire:click="notifyImport">Nhập dữ liệu</x-x2.btn>
                <x-x2.btn icon="heroicon-m-arrow-down-tray" wire:click="export">Xuất dữ liệu</x-x2.btn>
                <x-x2.btn as="a" href="{{ url('/fila/apartments/create') }}" variant="gold" icon="heroicon-m-plus">Thêm căn hộ</x-x2.btn>
            </x-slot:actions>
        </x-x2.page.tabs>

        {{-- KPI — tổng theo context (5 card compact 88–96px), BẤT BIẾN theo filter. --}}
        <x-x2.kpi-row :cols="5">
            @foreach ($kpis as $kpi)
                <x-x2.card.kpi
                    class="x2-kpi"
                    :label="$kpi['label']"
                    :value="$kpi['value']"
                    :sub="$kpi['sub'] ?? null"
                    :accent="$kpi['accent']"
                    :icon="$kpi['icon'] ?? 'heroicon-o-chart-bar'" />
            @endforeach
        </x-x2.kpi-row>

        {{-- Bảng Filament: search / lọc / sort / row + bulk action / phân trang (dense) --}}
        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            {{ $this->table }}
        </div>
    </div>
</x-filament-panels::page>
