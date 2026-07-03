<x-filament-panels::page>
    @include('filament.sa.ds._nav', ['active' => 'forms'])

    <div class="grid grid-cols-1 gap-5 xl:grid-cols-3">
        {{-- 1. Inputs --}}
        <x-x2.card.info title="1. Các trường nhập liệu" icon="heroicon-o-pencil">
            <div class="space-y-3">
                <div><label class="mb-1 block text-xs font-medium text-slate-500">Nhập liệu văn bản</label>
                    <input type="text" placeholder="Nhập nội dung" class="w-full rounded-lg border-slate-200 text-sm" /></div>
                <div><label class="mb-1 block text-xs font-medium text-slate-500">Tìm kiếm</label>
                    <div class="relative"><input type="text" placeholder="Tìm kiếm…" class="w-full rounded-lg border-slate-200 pl-9 text-sm" /><span class="absolute left-3 top-2.5 text-slate-400">@svg('heroicon-m-magnifying-glass','h-4 w-4')</span></div></div>
                <div><label class="mb-1 block text-xs font-medium text-slate-500">Vùng văn bản</label>
                    <textarea rows="2" placeholder="Nhập nội dung chi tiết…" class="w-full rounded-lg border-slate-200 text-sm"></textarea></div>
            </div>
        </x-x2.card.info>

        {{-- 2. Controls --}}
        <x-x2.card.info title="2. Lựa chọn & Điều khiển" icon="heroicon-o-adjustments-horizontal">
            <div class="space-y-3 text-sm">
                <div><label class="mb-1 block text-xs font-medium text-slate-500">Chọn một (Select)</label>
                    <select class="w-full rounded-lg border-slate-200 text-sm"><option>Chọn một tùy chọn</option></select></div>
                <div><label class="mb-1 block text-xs font-medium text-slate-500">Chọn nhiều</label>
                    <div class="flex flex-wrap gap-1.5"><x-x2.filter.chip value="Kỹ thuật" removeWire="x" /><x-x2.filter.chip value="Vệ sinh" removeWire="x" /></div></div>
                <div class="flex gap-6">
                    <label class="flex items-center gap-2"><input type="checkbox" checked class="rounded text-x2-primary" /> Kỹ thuật</label>
                    <label class="flex items-center gap-2"><input type="radio" name="r" checked class="text-x2-primary" /> Tùy chọn 1</label>
                </div>
                <label class="flex items-center gap-2"><span class="relative inline-block h-5 w-9 rounded-full bg-x2-primary"><span class="absolute right-0.5 top-0.5 h-4 w-4 rounded-full bg-white"></span></span> Bật</label>
            </div>
        </x-x2.card.info>

        {{-- 3. Filter bar --}}
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
        </x-x2.card.info>
    </div>

    <div class="mt-5 grid grid-cols-1 gap-5 xl:grid-cols-2">
        {{-- 4. Validation states --}}
        <x-x2.card.info title="4. Trạng thái xác thực biểu mẫu" icon="heroicon-o-check-circle">
            <div class="space-y-3 text-sm">
                <div><label class="mb-1 block text-xs text-slate-500">Mặc định</label><input class="w-full rounded-lg border-slate-200 text-sm" placeholder="Nhập nội dung" /></div>
                <div><label class="mb-1 block text-xs text-slate-500">Thành công</label><input class="w-full rounded-lg border-x2-green text-sm ring-1 ring-x2-green/30" value="Dữ liệu hợp lệ" /><p class="mt-1 text-xs text-x2-green">Dữ liệu hợp lệ.</p></div>
                <div><label class="mb-1 block text-xs text-slate-500">Lỗi</label><input class="w-full rounded-lg border-x2-red text-sm ring-1 ring-x2-red/30" /><p class="mt-1 text-xs text-x2-red">Vui lòng nhập thông tin.</p></div>
                <div><label class="mb-1 block text-xs text-slate-400">Vô hiệu hóa</label><input class="w-full rounded-lg border-slate-200 bg-slate-50 text-sm" disabled placeholder="Nhập nội dung" /></div>
            </div>
        </x-x2.card.info>

        {{-- 5. Advanced filter drawer + label hierarchy --}}
        <x-x2.card.info title="5. Bộ lọc nâng cao (Drawer) & Thứ bậc nhãn" icon="heroicon-o-bars-arrow-down">
            <div class="rounded-xl border border-slate-200 p-3">
                <div class="mb-2 flex items-center justify-between"><span class="text-sm font-semibold text-slate-700">Bộ lọc nâng cao</span>@svg('heroicon-m-x-mark','h-4 w-4 text-slate-400')</div>
                <div class="grid grid-cols-3 gap-3 text-sm">
                    <div class="space-y-1 border-r border-slate-100 pr-2 text-xs">
                        <div class="rounded bg-x2-primary/10 px-2 py-1 font-medium text-x2-primary">Thông tin chung</div>
                        <div class="px-2 py-1 text-slate-500">Thời gian</div>
                        <div class="px-2 py-1 text-slate-500">Trạng thái</div>
                    </div>
                    <div class="col-span-2 space-y-2">
                        <input class="w-full rounded-lg border-slate-200 text-sm" placeholder="Nhập từ khóa" />
                        <select class="w-full rounded-lg border-slate-200 text-sm"><option>Chọn tòa nhà</option></select>
                    </div>
                </div>
                <div class="mt-3 flex justify-end gap-2"><x-x2.btn size="sm">Đặt lại</x-x2.btn><x-x2.btn size="sm" variant="primary">Áp dụng</x-x2.btn></div>
            </div>
            <div class="mt-3 flex items-start gap-2 rounded-lg bg-x2-primary/5 px-3 py-2 text-xs text-slate-600">
                @svg('heroicon-o-information-circle','h-4 w-4 text-x2-primary')
                <span>Nhãn chính (bắt buộc) có dấu <span class="text-x2-red">*</span>; nhãn phụ + chỉ dẫn giúp nhập liệu chính xác.</span>
            </div>
        </x-x2.card.info>
    </div>
</x-filament-panels::page>
