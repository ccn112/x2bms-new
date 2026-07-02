<x-filament-panels::page>
    <x-x2.action-bar>
        <a href="{{ url('/fila/vehicles/create') }}" class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-sm text-slate-600 hover:bg-slate-50">+ Đăng ký xe</a>
        <a href="{{ url('/fila/access-cards/create') }}" class="rounded-lg bg-x2-primary px-3 py-1.5 text-sm font-medium text-white hover:bg-x2-primary-600">+ Cấp thẻ</a>
    </x-x2.action-bar>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ($kpis as $kpi)
            <x-x2.kpi-card :label="$kpi['label']" :value="$kpi['value']" :accent="$kpi['accent']" />
        @endforeach
    </div>

    <x-x2.section-card title="Phương tiện" :subtitle="'Hiển thị '.count($vehicles).' / '.$vehicleTotal">
        <x-x2.data-table
            :columns="[
                ['key'=>'plate','label'=>'Biển số'],
                ['key'=>'type','label'=>'Loại xe'],
                ['key'=>'apartment','label'=>'Căn hộ'],
                ['key'=>'card','label'=>'Thẻ gửi xe'],
                ['key'=>'fee','label'=>'Phí/tháng'],
                ['key'=>'valid','label'=>'Hiệu lực'],
            ]"
            :rows="$vehicles" empty="Không có phương tiện" />
    </x-x2.section-card>

    <x-x2.section-card title="Thẻ ra vào & sinh trắc" :subtitle="'Hiển thị '.count($cards).' / '.$cardTotal">
        <x-x2.data-table
            :columns="[
                ['key'=>'no','label'=>'Mã thẻ'],
                ['key'=>'holder','label'=>'Chủ thẻ'],
                ['key'=>'apartment','label'=>'Căn hộ'],
                ['key'=>'type','label'=>'Loại'],
                ['key'=>'valid','label'=>'Hiệu lực đến'],
                ['key'=>'status','label'=>'Trạng thái'],
            ]"
            :rows="$cards" empty="Không có thẻ" />
    </x-x2.section-card>
</x-filament-panels::page>
