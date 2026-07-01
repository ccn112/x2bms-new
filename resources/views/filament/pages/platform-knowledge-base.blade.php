<x-filament-panels::page>
    <x-x2.action-bar
        title="Thư viện tri thức nền tảng (AI)"
        subtitle="KB cho X2AI + vận hành. AI không truy xuất tài liệu lưu trữ/hết hạn; tài liệu hạn chế cần quyền ai_read." />

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
        @foreach ($kpis as $kpi)
            <x-x2.kpi-card :label="$kpi['label']" :value="$kpi['value']" :accent="$kpi['accent']" />
        @endforeach
    </div>

    <div class="rounded-2xl border border-slate-100 bg-white p-1 shadow-sm">
        {{ $this->table }}
    </div>
</x-filament-panels::page>
