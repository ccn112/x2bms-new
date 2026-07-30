<x-filament-panels::page>
    <x-x2.action-bar title="Duyệt chứng từ chuyển khoản"
        subtitle="Cư dân tự chuyển khoản rồi nộp ảnh chứng từ · đối chiếu với sao kê ngân hàng rồi duyệt hoặc từ chối kèm lý do." />

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        @foreach ($kpis as $kpi)
            <x-x2.kpi-card :label="$kpi['label']" :value="$kpi['value']" :accent="$kpi['accent']" />
        @endforeach
    </div>

    {{ $this->table }}
</x-filament-panels::page>
