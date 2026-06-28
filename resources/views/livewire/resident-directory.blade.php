<x-x2.admin-shell active="residents" breadcrumb="Cư dân & Căn hộ / Danh sách cư dân">
    <x-x2.action-bar title="Danh sách cư dân" subtitle="Quản lý cư dân, chủ sở hữu, người thuê và thành viên hộ">
        <div class="flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-1.5">
            <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.3-4.3M11 19a8 8 0 1 1 0-16 8 8 0 0 1 0 16Z"/></svg>
            <input type="text" wire:model.live.debounce.400ms="search" placeholder="Tìm tên / SĐT…" class="w-44 border-0 p-0 text-sm focus:ring-0" />
        </div>
        <button type="button" class="rounded-lg bg-x2-primary px-3 py-1.5 text-sm font-medium text-white hover:bg-x2-primary-600">+ Thêm cư dân</button>
    </x-x2.action-bar>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ($kpis as $kpi)
            <x-x2.kpi-card :label="$kpi['label']" :value="$kpi['value']" :sub="$kpi['sub'] ?? null" :accent="$kpi['accent']" />
        @endforeach
    </div>

    <x-x2.section-card title="Cư dân" :subtitle="'Hiển thị '.$shown.' / '.$total.' cư dân'">
        <x-slot:action>
            <a href="{{ url('/resident-approvals') }}" class="text-x2-primary hover:underline">Hàng đợi duyệt →</a>
        </x-slot:action>
        <x-x2.data-table
            :columns="[
                ['key' => 'name', 'label' => 'Họ tên'],
                ['key' => 'phone', 'label' => 'SĐT'],
                ['key' => 'email', 'label' => 'Email'],
                ['key' => 'role', 'label' => 'Vai trò'],
                ['key' => 'status', 'label' => 'Xác thực'],
                ['key' => 'location', 'label' => 'Căn hộ'],
                ['key' => 'action', 'label' => ''],
            ]"
            :rows="$rows"
            empty="Không tìm thấy cư dân" />
    </x-x2.section-card>
</x-x2.admin-shell>
