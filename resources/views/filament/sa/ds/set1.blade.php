<x-filament-panels::page>
    @php
        $tabs = [
            'foundations' => 'Nền tảng',
            'data-display' => 'KPI & Bảng',
            'buttons' => 'Nút & Hành động',
            'forms' => 'Form & Lọc',
            'overlays' => 'Modal & AI',
            'records' => 'Tabs & Chi tiết',
        ];
    @endphp

    <div x-data="{ tab: 'foundations' }">
        {{-- DS tab bar — các trang của bộ gộp vào tab (DS-02 style: gạch chân active) --}}
        <div class="mb-6 flex flex-wrap items-center gap-1 overflow-x-auto border-b border-slate-200">
            @foreach ($tabs as $key => $label)
                <button type="button" @click="tab='{{ $key }}'"
                    class="font-title whitespace-nowrap border-b-2 px-4 py-2.5 text-[15px] font-semibold transition"
                    :class="tab === '{{ $key }}' ? 'border-x2-primary text-x2-primary' : 'border-transparent text-slate-500 hover:text-slate-800'">
                    {{ $label }}
                </button>
            @endforeach
        </div>

        <div x-show="tab === 'foundations'">@include('filament.sa.ds.foundations')</div>
        <div x-show="tab === 'data-display'" x-cloak>@include('filament.sa.ds.data-display')</div>
        <div x-show="tab === 'buttons'" x-cloak>@include('filament.sa.ds.buttons')</div>
        <div x-show="tab === 'forms'" x-cloak>@include('filament.sa.ds.forms')</div>
        <div x-show="tab === 'overlays'" x-cloak>@include('filament.sa.ds.overlays')</div>
        <div x-show="tab === 'records'" x-cloak>@include('filament.sa.ds.records')</div>
    </div>
</x-filament-panels::page>
