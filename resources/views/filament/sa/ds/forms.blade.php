<x-filament-panels::page>
    @include('filament.sa.ds._nav', ['active' => 'forms'])

    <div class="mb-4 flex items-start gap-2 rounded-xl border border-x2-primary/20 bg-x2-primary/5 px-4 py-3 text-sm text-slate-600">
        <span class="text-x2-primary">@svg('heroicon-o-information-circle', 'h-5 w-5')</span>
        <p>Các trường bên dưới là <b>component Filament thật</b> (TextInput, Select, CheckboxList, Radio, Toggle, DatePicker, FileUpload) — đúng như UI hệ thống render, không phải mô phỏng.</p>
    </div>

    {{-- 1 · 2 · 5-6: real Filament fields --}}
    {{ $this->form }}

    {{-- 3 · 4: composite patterns (design mocks, không phải field đơn) --}}
    <div class="mt-5 grid grid-cols-1 gap-5 xl:grid-cols-2">
        <x-x2.card.info title="3. Thanh lọc (Filter Bar) tại chỗ" icon="heroicon-o-funnel">
            <x-x2.filter.bar :advancedCount="3">
                <x-slot:search>
                    <div class="relative"><input type="text" placeholder="Tìm trong bảng…" class="w-full rounded-lg border-slate-200 pl-9 text-sm" /><span class="absolute left-3 top-2.5 text-slate-400">@svg('heroicon-m-magnifying-glass','h-4 w-4')</span></div>
                </x-slot:search>
            </x-x2.filter.bar>
            <div class="mt-2 flex flex-wrap gap-1.5">
                <x-x2.filter.chip label="Trạng thái" value="Đang xử lý" removeWire="x" />
                <x-x2.filter.chip label="Ưu tiên" value="Cao" removeWire="x" />
                <x-x2.filter.chip label="Bộ phận" value="Kỹ thuật" removeWire="x" />
            </div>
            <p class="mt-3 text-xs text-slate-400">Filter bar là component X2 tùy biến; bên trong dùng lại control Filament. Chỉ ảnh hưởng bảng, không đổi KPI.</p>
        </x-x2.card.info>

        <x-x2.card.info title="4. Bộ lọc nâng cao (Drawer)" icon="heroicon-o-bars-arrow-down">
            <div class="rounded-xl border border-slate-200 p-3">
                <div class="mb-2 flex items-center justify-between"><span class="text-sm font-semibold text-slate-700">Bộ lọc nâng cao</span>@svg('heroicon-m-x-mark','h-4 w-4 text-slate-400')</div>
                <div class="grid grid-cols-3 gap-3 text-sm">
                    <div class="space-y-1 border-r border-slate-100 pr-2 text-xs">
                        <div class="rounded bg-x2-primary/10 px-2 py-1 font-medium text-x2-primary">Thông tin chung</div>
                        <div class="px-2 py-1 text-slate-500">Thời gian</div>
                        <div class="px-2 py-1 text-slate-500">Trạng thái</div>
                        <div class="px-2 py-1 text-slate-500">Bộ phận</div>
                    </div>
                    <div class="col-span-2 space-y-2">
                        <input class="w-full rounded-lg border-slate-200 text-sm" placeholder="Nhập từ khóa" />
                        <select class="w-full rounded-lg border-slate-200 text-sm"><option>Chọn tòa nhà</option></select>
                    </div>
                </div>
                <div class="mt-3 flex justify-end gap-2"><x-x2.btn size="sm">Đặt lại</x-x2.btn><x-x2.btn size="sm" variant="primary">Áp dụng</x-x2.btn></div>
            </div>
            <p class="mt-3 text-xs text-slate-400">Drawer mở từ nút “Bộ lọc nâng cao”; nhóm điều kiện theo tab, đáy có Đặt lại / Áp dụng.</p>
        </x-x2.card.info>
    </div>
</x-filament-panels::page>
