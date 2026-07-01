<x-filament-panels::page>
    <x-x2.action-bar title="Wizard sửa dữ liệu có kiểm soát"
        subtitle="Nhận diện → snapshot → preview diff → phê duyệt → thực thi → xác minh → rollback." />

    <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
        ⚠️ Thao tác sửa dữ liệu có rủi ro. Bắt buộc tạo <b>backup snapshot</b> trước khi thực thi; high/critical cần phê duyệt 2 người; mọi bước được ghi audit.
    </div>

    <x-x2.section-card title="Quy trình 8 bước">
        <ol class="flex flex-wrap gap-2 text-xs">
            @foreach ($steps as $i => $step)
                <li class="flex items-center gap-2 rounded-full border border-slate-100 bg-white px-3 py-1.5">
                    <span class="flex h-5 w-5 items-center justify-center rounded-full bg-x2-navy text-[10px] font-semibold text-white">{{ $i + 1 }}</span>
                    <span class="text-slate-600">{{ $step }}</span>
                </li>
            @endforeach
        </ol>
    </x-x2.section-card>

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        @foreach ($kpis as $kpi)
            <x-x2.kpi-card :label="$kpi['label']" :value="$kpi['value']" :accent="$kpi['accent']" />
        @endforeach
    </div>

    {{ $this->table }}
</x-filament-panels::page>
