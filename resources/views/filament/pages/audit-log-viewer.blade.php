<x-filament-panels::page>
    <x-x2.kpi-row :cols="4">
        @foreach ($kpis as $kpi)
            <x-x2.kpi-card
                :label="$kpi['label']"
                :value="$kpi['value']"
                :sub="$kpi['sub'] ?? null"
                :accent="$kpi['accent']"
            />
        @endforeach
    </x-x2.kpi-row>

    {{ $this->table }}
</x-filament-panels::page>
