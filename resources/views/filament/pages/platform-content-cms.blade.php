<x-filament-panels::page>
    <x-x2.action-bar
        title="Nội dung & thông báo nền tảng"
        subtitle="Tin tức · thông báo · banner · hướng dẫn dùng chung. Vòng đời nháp → chờ duyệt → đăng → lưu trữ." />

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ($kpis as $kpi)
            <x-x2.kpi-card :label="$kpi['label']" :value="$kpi['value']" :accent="$kpi['accent']" />
        @endforeach
    </div>

    <div class="rounded-2xl border border-slate-100 bg-white p-1 shadow-sm">
        {{ $this->table }}
    </div>
</x-filament-panels::page>
