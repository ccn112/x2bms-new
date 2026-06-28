<x-x2.admin-shell active="apartments" breadcrumb="Cư dân & Căn hộ / Hồ sơ căn hộ">
    <x-x2.action-bar title="Hồ sơ căn hộ" subtitle="Danh sách căn hộ, chủ sở hữu, công nợ và cư dân">
        <div class="flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-1.5">
            <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.3-4.3M11 19a8 8 0 1 1 0-16 8 8 0 0 1 0 16Z"/></svg>
            <input type="text" wire:model.live.debounce.400ms="search" placeholder="Tìm mã căn…" class="w-40 border-0 p-0 text-sm focus:ring-0" />
        </div>
    </x-x2.action-bar>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ($kpis as $kpi)
            <x-x2.kpi-card :label="$kpi['label']" :value="$kpi['value']" :accent="$kpi['accent']" />
        @endforeach
    </div>

    <x-x2.section-card title="Căn hộ" :subtitle="'Hiển thị '.$shown.' / '.$total.' căn'">
        <x-x2.data-table
            :columns="[
                ['key' => 'code', 'label' => 'Mã căn'],
                ['key' => 'floor', 'label' => 'Tầng'],
                ['key' => 'area', 'label' => 'Diện tích'],
                ['key' => 'owner', 'label' => 'Chủ sở hữu'],
                ['key' => 'residents', 'label' => 'Cư dân'],
                ['key' => 'debt', 'label' => 'Công nợ'],
                ['key' => 'action', 'label' => ''],
            ]"
            :rows="$rows"
            empty="Không tìm thấy căn hộ" />
    </x-x2.section-card>
</x-x2.admin-shell>
