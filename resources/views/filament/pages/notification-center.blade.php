<x-filament-panels::page>
    <x-x2.action-bar
        title="Trung tâm thông báo"
        subtitle="Soạn · chọn phạm vi (3 lớp) · hẹn giờ / phát hành · theo dõi đã gửi và đã đọc." />

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ($kpis as $kpi)
            <x-x2.kpi-card :label="$kpi['label']" :value="$kpi['value']" :sub="$kpi['sub'] ?? null" :accent="$kpi['accent']" />
        @endforeach
    </div>

    <div class="rounded-2xl border border-slate-100 bg-white p-1 shadow-sm">
        {{ $this->table }}
    </div>
</x-filament-panels::page>
