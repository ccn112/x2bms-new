<x-filament-panels::page>
    <x-x2.kpi-row :cols="5">
        @foreach ($kpis as $kpi)
            <x-x2.kpi-card :label="$kpi['label']" :value="$kpi['value']" :accent="$kpi['accent']" />
        @endforeach
    </x-x2.kpi-row>

    {{ $this->table }}
</x-filament-panels::page>
