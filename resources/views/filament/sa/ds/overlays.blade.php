<div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
        {{-- 1. Modal & Drawer --}}
        <x-x2.card.info title="1. Modal & Drawer" icon="heroicon-o-window">
            <div class="space-y-3">
                <div class="rounded-xl border border-slate-200 p-3 text-center">
                    <span class="mx-auto mb-1 grid h-9 w-9 place-items-center rounded-full bg-x2-amber/10 text-x2-amber">@svg('heroicon-o-exclamation-triangle','h-5 w-5')</span>
                    <div class="text-sm font-semibold text-slate-700">Xác nhận xóa</div>
                    <p class="text-xs text-slate-500">Hành động không thể hoàn tác.</p>
                    <div class="mt-2 flex justify-center gap-2"><x-x2.btn size="sm">Hủy</x-x2.btn><x-x2.btn size="sm" variant="danger">Xác nhận</x-x2.btn></div>
                </div>
                <div class="rounded-xl border border-slate-200 p-3">
                    <div class="mb-1 flex items-center justify-between text-sm font-semibold text-slate-700">Ngăn kéo (Slide-over)@svg('heroicon-m-x-mark','h-4 w-4 text-slate-400')</div>
                    <p class="text-xs text-slate-500">Panel trượt phải cho chi tiết/biểu mẫu, không rời ngữ cảnh.</p>
                </div>
            </div>
        </x-x2.card.info>

        {{-- 2. Wizard --}}
        <x-x2.card.info title="2. Wizard / Biểu mẫu nhiều bước" icon="heroicon-o-queue-list">
            <div class="flex items-center gap-1 text-xs">
                <span class="grid h-6 w-6 place-items-center rounded-full bg-x2-primary text-white">1</span>
                <span class="h-0.5 w-8 bg-x2-primary"></span>
                <span class="grid h-6 w-6 place-items-center rounded-full bg-x2-primary text-white">2</span>
                <span class="h-0.5 w-8 bg-slate-200"></span>
                <span class="grid h-6 w-6 place-items-center rounded-full bg-slate-200 text-slate-500">3</span>
            </div>
            <div class="mt-2 text-xs text-slate-500">Thông tin → Chi tiết → Xác nhận</div>
            <div class="mt-3 space-y-2"><input class="w-full rounded-lg border-slate-200 text-sm" placeholder="Họ và tên *" /><input class="w-full rounded-lg border-slate-200 text-sm" placeholder="Số CCCD *" /></div>
            <div class="mt-2 flex justify-between"><x-x2.btn size="sm">Hủy</x-x2.btn><x-x2.btn size="sm" variant="primary">Tiếp tục</x-x2.btn></div>
        </x-x2.card.info>

        {{-- 3. Notifications --}}
        <x-x2.card.info title="3. Thông báo" icon="heroicon-o-bell">
            <div class="space-y-2 text-sm">
                <div class="flex items-center gap-2 rounded-lg border border-x2-green/30 bg-x2-green/5 px-3 py-2 text-x2-green">@svg('heroicon-o-check-circle','h-4 w-4') Cập nhật thành công</div>
                <div class="flex items-center gap-2 rounded-lg border border-x2-primary/30 bg-x2-primary/5 px-3 py-2 text-x2-primary">@svg('heroicon-o-information-circle','h-4 w-4') Hệ thống bảo trì 00:00</div>
                <div class="flex items-center gap-2 rounded-lg border border-x2-amber/30 bg-x2-amber/5 px-3 py-2 text-x2-amber">@svg('heroicon-o-exclamation-triangle','h-4 w-4') Thiết bị sắp đến hạn</div>
                <div class="flex items-center gap-2 rounded-lg border border-x2-red/30 bg-x2-red/5 px-3 py-2 text-x2-red">@svg('heroicon-o-x-circle','h-4 w-4') Đã xảy ra lỗi</div>
            </div>
            <p class="mt-2 text-xs text-slate-400">Toast (nhanh) · Banner (toàn trang) · Dropdown (trung tâm thông báo).</p>
        </x-x2.card.info>
    </div>

    <div class="mt-6 grid grid-cols-1 gap-5 xl:grid-cols-3">
        {{-- 4. Approval --}}
        <x-x2.card.info title="4. Phê duyệt (Approval)" icon="heroicon-o-check-badge">
            <div class="text-sm">
                <div class="font-semibold text-slate-800">Phạm Quốc Dũng · A12-08</div>
                <div class="mt-1 text-xs text-slate-500">Yêu cầu sửa chữa · Thay đèn hành lang tầng 12</div>
                <div class="mt-3 flex gap-2"><x-x2.btn size="sm" variant="danger">Từ chối</x-x2.btn><x-x2.btn size="sm" variant="primary">Phê duyệt</x-x2.btn></div>
                <p class="mt-2 text-xs text-slate-400">Nút quyết định sticky đáy panel; mọi quyết định ghi audit log.</p>
            </div>
        </x-x2.card.info>

        {{-- 5. AI Assistant --}}
        <x-x2.card.info title="5. AI Assistant" icon="heroicon-o-sparkles">
            <div class="rounded-xl border border-x2-ai/20 bg-x2-ai/5 p-3 text-sm">
                <div class="flex items-center gap-2 text-x2-ai">@svg('heroicon-o-sparkles','h-4 w-4') <span class="font-semibold">AI Trợ lý BMS</span> <span class="rounded bg-x2-ai/10 px-1 text-[10px]">Beta</span></div>
                <p class="mt-2 text-xs text-slate-600">Phân tích tiêu thụ điện tòa Sunshine Garden tháng 4/2024.</p>
                <div class="mt-2 rounded-lg bg-white p-2 text-xs text-slate-500">Điện năng tăng 12.5% · Thiết bị tiêu thụ lớn nhất: AHU-T12-01</div>
                <div class="mt-2 flex items-center gap-2 rounded-lg bg-white px-2 py-1.5 text-xs text-slate-400">Hỏi AI về dữ liệu, báo cáo… @svg('heroicon-m-paper-airplane','ml-auto h-4 w-4 text-x2-ai')</div>
            </div>
            <p class="mt-2 text-xs text-slate-400">AI chat = FAB nổi dùng chung; page rail chỉ hiển thị gợi ý read-only.</p>
        </x-x2.card.info>

        {{-- 6. System states --}}
        <x-x2.card.info title="6. Trạng thái hệ thống" icon="heroicon-o-signal">
            <div class="grid grid-cols-2 gap-3 text-center text-xs">
                <div class="rounded-lg border border-slate-200 p-3"><svg class="mx-auto mb-1 h-5 w-5 animate-spin text-x2-primary" viewBox="0 0 24 24" fill="none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/></svg>Đang tải</div>
                <div class="rounded-lg border border-slate-200 p-3">@svg('heroicon-o-inbox','mx-auto mb-1 h-5 w-5 text-slate-400')Trống</div>
                <div class="rounded-lg border border-slate-200 p-3">@svg('heroicon-o-lock-closed','mx-auto mb-1 h-5 w-5 text-slate-400')Không có quyền</div>
                <div class="rounded-lg border border-x2-red/30 bg-x2-red/5 p-3 text-x2-red">@svg('heroicon-o-exclamation-circle','mx-auto mb-1 h-5 w-5')Lỗi</div>
            </div>
        </x-x2.card.info>
    </div>
