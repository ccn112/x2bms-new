<x-filament-panels::page>
    <x-x2.action-bar title="Duyệt tin rao BĐS — toàn nền tảng"
        subtitle="Mọi tenant/dự án · duyệt được cả tin BQL chưa từng đụng tới · nhãn 'Được BQL đẩy lên' chỉ là ưu tiên hiển thị." />

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        @foreach ($kpis as $kpi)
            <x-x2.kpi-card :label="$kpi['label']" :value="$kpi['value']" :accent="$kpi['accent']" />
        @endforeach
    </div>

    {{ $this->table }}
</x-filament-panels::page>
