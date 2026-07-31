<x-filament-panels::page>
    <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5">
        @foreach ($kpis as $kpi)
            <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
                <div class="text-xs text-gray-500 dark:text-gray-400">{{ $kpi['label'] }}</div>
                <div class="mt-1 text-2xl font-bold text-gray-900 dark:text-gray-100">{{ number_format($kpi['value']) }}</div>
            </div>
        @endforeach
    </div>

    <div class="mt-4">
        {{ $this->table }}
    </div>
</x-filament-panels::page>
