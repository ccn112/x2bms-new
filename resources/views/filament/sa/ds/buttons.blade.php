<div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
        {{-- 1. Button hierarchy --}}
        <x-x2.card.info title="1. Thứ bậc nút (Button hierarchy)" icon="heroicon-o-cursor-arrow-rays">
            <table class="w-full text-sm">
                <thead><tr class="text-left text-xs text-slate-400">
                    <th class="py-1"></th><th class="py-1">Ưu tiên cao</th><th class="py-1">Thứ cấp</th><th class="py-1">CTA</th><th class="py-1">Cảnh báo</th>
                </tr></thead>
                <tbody>
                    <tr><td class="py-2 pr-2 text-xs text-slate-500">Mặc định</td>
                        <td class="py-2 pr-2"><x-x2.btn variant="primary" size="sm" icon="heroicon-m-plus">Tạo mới</x-x2.btn></td>
                        <td class="py-2 pr-2"><x-x2.btn size="sm">Hủy bỏ</x-x2.btn></td>
                        <td class="py-2 pr-2"><x-x2.btn variant="gold" size="sm">Duyệt</x-x2.btn></td>
                        <td class="py-2 pr-2"><x-x2.btn variant="danger" size="sm">Xóa</x-x2.btn></td>
                    </tr>
                    <tr><td class="py-2 pr-2 text-xs text-slate-500">Disabled</td>
                        <td class="py-2 pr-2"><x-x2.btn variant="primary" size="sm" icon="heroicon-m-plus" :disabled="true">Tạo mới</x-x2.btn></td>
                        <td class="py-2 pr-2"><x-x2.btn size="sm" :disabled="true">Hủy bỏ</x-x2.btn></td>
                        <td class="py-2 pr-2"><x-x2.btn variant="gold" size="sm" :disabled="true">Duyệt</x-x2.btn></td>
                        <td class="py-2 pr-2"><x-x2.btn variant="danger" size="sm" :disabled="true">Xóa</x-x2.btn></td>
                    </tr>
                    <tr><td class="py-2 pr-2 text-xs text-slate-500">Loading / Icon</td>
                        <td class="py-2 pr-2"><x-x2.btn variant="primary" size="sm" :loading="true">Đang lưu</x-x2.btn></td>
                        <td class="py-2 pr-2"><x-x2.btn variant="ghost" size="sm">Tìm hiểu thêm</x-x2.btn></td>
                        <td class="py-2 pr-2"><x-x2.btn size="sm" icon="heroicon-m-plus" class="!px-2"><span class="sr-only">Thêm</span></x-x2.btn></td>
                        <td class="py-2 pr-2"></td>
                    </tr>
                </tbody>
            </table>
            <p class="mt-3 text-xs text-slate-500">Xanh = hành động hệ thống chính · Gold = CTA/duyệt theo ngữ cảnh · Outline = thứ cấp · Đỏ = phá hủy (không bao giờ là primary).</p>
        </x-x2.card.info>

        {{-- 2. Split & group --}}
        <x-x2.card.info title="2. Nút tách & nhóm hành động" icon="heroicon-o-squares-2x2">
            <div class="flex flex-wrap items-start gap-8">
                <div>
                    <div class="mb-2 text-xs font-semibold text-slate-500">Nút tách (Split)</div>
                    <div class="inline-flex overflow-hidden rounded-lg shadow-sm" x-data="{ open: false }">
                        <button class="bg-x2-primary px-3 py-2 text-sm font-semibold text-white">Xuất báo cáo</button>
                        <button @click="open=!open" class="border-l border-white/20 bg-x2-primary px-2 text-white">@svg('heroicon-m-chevron-down', 'h-4 w-4')</button>
                    </div>
                    <div class="mt-2 w-40 rounded-lg border border-slate-200 bg-white p-1 text-sm shadow">
                        <div class="rounded px-2 py-1 hover:bg-slate-50">Xuất PDF</div>
                        <div class="rounded px-2 py-1 hover:bg-slate-50">Xuất Excel</div>
                        <div class="rounded px-2 py-1 hover:bg-slate-50">Xuất CSV</div>
                    </div>
                </div>
                <div>
                    <div class="mb-2 text-xs font-semibold text-slate-500">Nhóm nút (Group)</div>
                    <div class="inline-flex overflow-hidden rounded-lg border border-slate-200 text-sm">
                        <button class="px-3 py-2 text-slate-600 hover:bg-slate-50">Ngày</button>
                        <button class="border-l border-slate-200 bg-x2-primary/10 px-3 py-2 font-semibold text-x2-primary">Tuần</button>
                        <button class="border-l border-slate-200 px-3 py-2 text-slate-600 hover:bg-slate-50">Tháng</button>
                        <button class="border-l border-slate-200 px-3 py-2 text-slate-600 hover:bg-slate-50">Năm</button>
                    </div>
                </div>
            </div>
        </x-x2.card.info>
    </div>

    <div class="mt-6 grid grid-cols-1 gap-5 xl:grid-cols-2">
        {{-- 3. Topbar elements --}}
        <x-x2.card.info title="3. Thành phần thanh đầu trang" icon="heroicon-o-window">
            <div class="flex flex-wrap items-center gap-4">
                <div class="flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-400">@svg('heroicon-o-magnifying-glass', 'h-4 w-4') Tìm kiếm (⌘K)</div>
                <div class="flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-600">@svg('heroicon-o-building-office', 'h-4 w-4') Sunshine Garden</div>
                <x-x2.btn variant="primary" size="sm" icon="heroicon-m-plus">Tạo mới</x-x2.btn>
                <span class="relative">@svg('heroicon-o-bell', 'h-6 w-6 text-slate-500')<span class="absolute -right-1 -top-1 grid h-4 w-4 place-items-center rounded-full bg-x2-red text-[10px] font-bold text-white">12</span></span>
                <span class="relative">@svg('heroicon-o-check-badge', 'h-6 w-6 text-slate-500')<span class="absolute -right-1 -top-1 grid h-4 w-4 place-items-center rounded-full bg-x2-red text-[10px] font-bold text-white">7</span></span>
                @svg('heroicon-o-question-mark-circle', 'h-6 w-6 text-slate-500')
            </div>
        </x-x2.card.info>

        {{-- 4. Dropdown & kebab --}}
        <x-x2.card.info title="4. Menu thả xuống & menu hành động" icon="heroicon-o-ellipsis-vertical">
            <div class="flex flex-wrap gap-6">
                <div class="w-44 rounded-lg border border-slate-200 bg-white p-1 text-sm shadow">
                    <div class="flex items-center gap-2 rounded px-2 py-1.5 hover:bg-slate-50">@svg('heroicon-m-eye','h-4 w-4 text-slate-400') Xem chi tiết</div>
                    <div class="flex items-center gap-2 rounded px-2 py-1.5 hover:bg-slate-50">@svg('heroicon-m-pencil-square','h-4 w-4 text-slate-400') Chỉnh sửa</div>
                    <div class="flex items-center gap-2 rounded px-2 py-1.5 hover:bg-slate-50">@svg('heroicon-m-document-duplicate','h-4 w-4 text-slate-400') Nhân bản</div>
                    <div class="my-1 border-t border-slate-100"></div>
                    <div class="flex items-center gap-2 rounded px-2 py-1.5 text-x2-red hover:bg-x2-red/5">@svg('heroicon-m-trash','h-4 w-4') Xóa</div>
                </div>
                <div class="text-xs text-slate-500">
                    <div class="mb-1 font-semibold">Kebab (row action)</div>
                    @svg('heroicon-m-ellipsis-vertical', 'h-5 w-5 text-slate-400')
                    <p class="mt-2 max-w-[12rem]">Gom hành động phụ vào kebab; giữ eye/edit ngoài cho thao tác chính.</p>
                </div>
            </div>
        </x-x2.card.info>
    </div>

    {{-- 5. Badges & status pills --}}
    <div class="mt-5">
        <x-x2.card.info title="5. Nhãn hiệu (Badges) & trạng thái (Status pills)" icon="heroicon-o-tag">
            <div class="flex flex-wrap items-center gap-3">
                <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-semibold text-slate-600">Mặc định 12</span>
                <span class="rounded-full bg-x2-green/10 px-2 py-0.5 text-xs font-semibold text-x2-green">Mới 24</span>
                <span class="rounded-full bg-x2-amber/10 px-2 py-0.5 text-xs font-semibold text-x2-amber">Cảnh báo 7</span>
                <span class="rounded-full bg-x2-red/10 px-2 py-0.5 text-xs font-semibold text-x2-red">Lỗi 3</span>
                <span class="rounded-full bg-x2-ai/10 px-2 py-0.5 text-xs font-semibold text-x2-ai">Tùy chỉnh 99+</span>
                <span class="mx-2 h-5 w-px bg-slate-200"></span>
                <x-x2.status-badge label="Hoạt động" tone="green" />
                <x-x2.status-badge label="Chờ xử lý" tone="amber" />
                <x-x2.status-badge label="Đã tạm dừng" tone="slate" />
                <x-x2.status-badge label="Thông tin" tone="blue" />
                <x-x2.status-badge label="Lỗi" tone="red" />
            </div>
        </x-x2.card.info>
    </div>
