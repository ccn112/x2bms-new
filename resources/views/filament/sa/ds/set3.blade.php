<x-filament-panels::page>
    @php
        $tabs = [
            'hierarchy' => 'Button Hierarchy',
            'actionbar' => 'Page Action Bar',
            'compact' => 'Compact Action',
            'create' => 'Quick vs Page Create',
            'row' => 'Row Actions',
            'bulk' => 'Bulk Action Bar',
            'split' => 'Split Button',
            'badge' => 'Badge Count',
            'status' => 'Status Pill',
            'matrix' => 'Decision Matrix',
        ];
        // icon-pill helper data (status pill = icon + màu + text)
        $pill = function ($label, $icon, $tone) {
            $tones = ['green' => 'bg-x2-green/10 text-x2-green', 'amber' => 'bg-x2-amber/10 text-x2-amber', 'red' => 'bg-x2-red/10 text-x2-red', 'blue' => 'bg-x2-blue/10 text-x2-blue', 'slate' => 'bg-slate-100 text-slate-600', 'ai' => 'bg-x2-ai/10 text-x2-ai'];
            return '<span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[11px] font-medium '.($tones[$tone] ?? $tones['slate']).'">'.\Illuminate\Support\Facades\Blade::render("@svg('$icon','h-3.5 w-3.5')").' '.$label.'</span>';
        };
    @endphp

    <div x-data="{ tab: 'hierarchy' }">
        <div class="mb-6 flex flex-wrap items-center gap-1 overflow-x-auto border-b border-slate-200">
            @foreach ($tabs as $key => $label)
                <button type="button" @click="tab='{{ $key }}'"
                    class="font-title whitespace-nowrap border-b-2 px-3.5 py-2.5 text-[15px] font-semibold transition"
                    :class="tab === '{{ $key }}' ? 'border-x2-primary text-x2-primary' : 'border-transparent text-slate-500 hover:text-slate-800'">
                    {{ $label }}
                </button>
            @endforeach
        </div>

        {{-- ============ 01 · BUTTON HIERARCHY ============ --}}
        <div x-show="tab === 'hierarchy'">
            <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
                <div class="space-y-6 xl:col-span-2">
                    <x-x2.card.info title="1. Kiểu nút (Button types)" icon="heroicon-o-cursor-arrow-rays">
                        <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                            <div><div class="mb-1.5 text-xs text-slate-400">Primary</div><x-x2.btn variant="primary" icon="heroicon-m-check">Lưu thay đổi</x-x2.btn></div>
                            <div><div class="mb-1.5 text-xs text-slate-400">Secondary</div><x-x2.btn>Hủy bỏ</x-x2.btn></div>
                            <div><div class="mb-1.5 text-xs text-slate-400">Ghost</div><x-x2.btn variant="ghost" icon="heroicon-m-eye">Xem chi tiết</x-x2.btn></div>
                            <div><div class="mb-1.5 text-xs text-slate-400">Nguy hiểm</div><x-x2.btn variant="danger" icon="heroicon-m-trash">Xóa dữ liệu</x-x2.btn></div>
                            <div><div class="mb-1.5 text-xs text-slate-400">CTA (Gold)</div><x-x2.btn variant="gold" icon="heroicon-m-plus">Tạo mới</x-x2.btn></div>
                            <div><div class="mb-1.5 text-xs text-slate-400">Disabled</div><x-x2.btn :disabled="true">Không khả dụng</x-x2.btn></div>
                            <div><div class="mb-1.5 text-xs text-slate-400">Icon + Label</div><x-x2.btn icon="heroicon-m-arrow-down-tray">Xuất dữ liệu</x-x2.btn></div>
                            <div><div class="mb-1.5 text-xs text-slate-400">Icon only</div><x-x2.btn icon="heroicon-m-ellipsis-vertical" class="!px-2"><span class="sr-only">Thêm</span></x-x2.btn></div>
                        </div>
                    </x-x2.card.info>

                    <x-x2.card.info title="2. Nút có icon (With icons)" icon="heroicon-o-sparkles">
                        <div class="flex flex-wrap items-end gap-6">
                            <div><div class="mb-1.5 text-xs text-slate-400">Icon trái</div><x-x2.btn variant="primary" icon="heroicon-m-plus">Thêm mới</x-x2.btn></div>
                            <div><div class="mb-1.5 text-xs text-slate-400">Icon phải</div><x-x2.btn iconRight="heroicon-m-arrow-down-tray">Xuất dữ liệu</x-x2.btn></div>
                            <div><div class="mb-1.5 text-xs text-slate-400">Hai icon</div><x-x2.btn icon="heroicon-m-document-duplicate">Sao chép</x-x2.btn></div>
                            <div><div class="mb-1.5 text-xs text-slate-400">Chỉ icon (tròn)</div><span class="grid h-9 w-9 place-items-center rounded-full border border-slate-200 text-slate-500">@svg('heroicon-m-pencil','h-4 w-4')</span></div>
                            <div><div class="mb-1.5 text-xs text-slate-400">Chỉ icon (vuông)</div><span class="grid h-9 w-9 place-items-center rounded-lg border border-x2-red/30 text-x2-red">@svg('heroicon-m-trash','h-4 w-4')</span></div>
                        </div>
                    </x-x2.card.info>

                    <x-x2.card.info title="3. Kích thước (Sizes)" icon="heroicon-o-arrows-pointing-out">
                        <div class="flex flex-wrap items-center gap-6">
                            <div><div class="mb-1.5 text-xs text-slate-400">Nhỏ (Small)</div><x-x2.btn variant="primary" size="sm">Lưu</x-x2.btn></div>
                            <div><div class="mb-1.5 text-xs text-slate-400">Mặc định (Default)</div><x-x2.btn variant="primary">Lưu thay đổi</x-x2.btn></div>
                            <div><div class="mb-1.5 text-xs text-slate-400">Lớn (Large)</div><span class="inline-flex h-12 items-center rounded-lg bg-x2-primary px-5 text-sm font-semibold text-white">Lưu thay đổi</span></div>
                        </div>
                    </x-x2.card.info>

                    <x-x2.card.info title="4. Ví dụ trong ngữ cảnh thực tế" icon="heroicon-o-rectangle-group">
                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-3">
                            <div class="rounded-xl border border-slate-200 p-3"><div class="mb-2 text-xs font-semibold text-slate-500">Thanh hành động trang</div><div class="flex gap-2"><x-x2.btn variant="primary" size="sm">Lưu thay đổi</x-x2.btn><x-x2.btn size="sm">Hủy bỏ</x-x2.btn></div></div>
                            <div class="rounded-xl border border-slate-200 p-3"><div class="mb-2 text-xs font-semibold text-slate-500">Table toolbar</div><div class="flex items-center gap-2"><x-x2.btn size="sm" icon="heroicon-m-arrows-right-left">Gộp bản ghi</x-x2.btn><span class="grid h-8 w-8 place-items-center rounded-lg border border-x2-red/30 text-x2-red">@svg('heroicon-m-trash','h-4 w-4')</span></div></div>
                            <div class="rounded-xl border border-slate-200 p-3"><div class="mb-2 text-xs font-semibold text-slate-500">Form footer</div><div class="flex gap-2"><x-x2.btn size="sm">Quay lại</x-x2.btn><x-x2.btn size="sm" variant="primary">Xác nhận</x-x2.btn></div></div>
                            <div class="rounded-xl border border-slate-200 p-3"><div class="mb-2 text-xs font-semibold text-slate-500">Xác nhận hành động</div><div class="flex gap-2"><x-x2.btn size="sm">Hủy bỏ</x-x2.btn><x-x2.btn size="sm" variant="danger">Xóa vĩnh viễn</x-x2.btn></div></div>
                            <div class="rounded-xl border border-slate-200 p-3"><div class="mb-2 text-xs font-semibold text-slate-500">Row actions</div><div class="flex gap-1 text-slate-400">@svg('heroicon-m-eye','h-5 w-5')@svg('heroicon-m-pencil-square','h-5 w-5')@svg('heroicon-m-document-duplicate','h-5 w-5')<span class="text-x2-red">@svg('heroicon-m-trash','h-5 w-5')</span></div></div>
                        </div>
                    </x-x2.card.info>

                    <x-x2.card.info title="5. Quy tắc sử dụng theo thứ bậc" icon="heroicon-o-check-badge">
                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-3">
                            @foreach ([
                                ['Primary (Chính)', 'blue', ['Hành động chính, thường dùng nhất', 'Dẫn tới bước tiếp theo', 'Chỉ 1 Primary mỗi ngữ cảnh']],
                                ['Secondary (Phụ)', 'slate', ['Hành động bổ trợ', 'Xem chi tiết, mở rộng, cấu hình', 'Hỗ trợ nhưng không nổi bật']],
                                ['Ghost (Nhẹ)', 'blue', ['Tác vụ nhẹ: tìm, lọc, xem', 'Giảm nhiễu thị giác', 'Hành động ít rủi ro']],
                                ['Danger (Nguy hiểm)', 'red', ['Hành động phá hủy/rủi ro cao', 'Xóa, hủy, thu hồi, khóa', 'Hiển thị rõ ràng cảnh báo']],
                                ['CTA (Gold)', 'amber', ['Hành động giá trị cao, đổi trạng thái', 'Gửi phê duyệt, phát hành, thanh toán', 'Thu hút chú ý, dùng chọn lọc']],
                                ['Disabled (Vô hiệu)', 'slate', ['Trạng thái chưa sẵn sàng', 'Không cho tương tác', 'Kèm tooltip/lý do']],
                            ] as [$name, $tone, $items])
                                <div class="rounded-xl border border-slate-200 p-3">
                                    <div class="mb-1.5 text-sm font-semibold text-x2-{{ $tone === 'slate' ? 'slate' : $tone }}">{{ $name }}</div>
                                    <ul class="space-y-1 text-xs text-slate-500">@foreach ($items as $i)<li>• {{ $i }}</li>@endforeach</ul>
                                </div>
                            @endforeach
                        </div>
                        <p class="mt-3 rounded-lg bg-x2-primary/5 px-3 py-2 text-xs text-slate-500">Đặt nút theo thứ bậc trái → phải: Chính → Phụ → Nhẹ → Nhấn mạnh → Nguy hiểm → Hủy. Giữ nhất quán để tạo thói quen.</p>
                    </x-x2.card.info>
                </div>

                <x-x2.card.info title="Hướng dẫn sử dụng" icon="heroicon-o-information-circle">
                    @foreach ([
                        ['Primary (Chính)', 'blue', 'Hành động chính, ưu tiên cao nhất. Chỉ 1 nút Primary trong một vùng.'],
                        ['Secondary (Phụ)', 'slate', 'Hành động phụ trợ. Dùng khi có ≥ 2 hành động.'],
                        ['Ghost (Tertiary)', 'blue', 'Ít ưu tiên, điều hướng hoặc xem chi tiết.'],
                        ['Nguy hiểm (Danger)', 'red', 'Phá hủy dữ liệu/không hoàn tác. Luôn xác nhận.'],
                        ['CTA (Gold)', 'amber', 'Tạo mới hoặc nâng cấp. Nổi bật, thu hút.'],
                        ['Disabled', 'slate', 'Không khả dụng. Cần tooltip/label hỗ trợ.'],
                    ] as [$n, $tone, $d])
                        <div class="flex gap-2 border-b border-slate-50 py-2">
                            <span class="mt-1 h-2.5 w-2.5 shrink-0 rounded-full bg-x2-{{ $tone === 'slate' ? 'slate' : $tone }}"></span>
                            <div><div class="text-sm font-semibold text-slate-700">{{ $n }}</div><div class="text-xs text-slate-500">{{ $d }}</div></div>
                        </div>
                    @endforeach
                    <div class="mt-3 text-xs text-slate-500">
                        <div class="mb-1 font-semibold text-slate-700">Nguyên tắc chung</div>
                        <ul class="space-y-1">
                            <li>✓ Ngôn từ rõ ràng, nhất quán.</li>
                            <li>✓ Ưu tiên văn bản hơn icon để giảm hiểu nhầm.</li>
                            <li>✓ Tránh lạm dụng màu và kiểu nút.</li>
                            <li>✓ Đảm bảo khả năng truy cập và tương phản.</li>
                        </ul>
                    </div>
                </x-x2.card.info>
            </div>
        </div>

        {{-- ============ 02 · PAGE ACTION BAR ============ --}}
        <div x-show="tab === 'actionbar'" x-cloak>
            <x-x2.card.info title="1. Action Bar là gì?" icon="heroicon-o-rectangle-group">
                <div class="grid gap-4 lg:grid-cols-2">
                    <p class="text-sm text-slate-600">Action Bar là tập hợp hành động chính của trang, đặt cùng hàng với tab trang, giúp thực hiện các tác vụ phổ biến mà không cần cuộn.</p>
                    <div>
                        <div class="mb-2 text-xs font-semibold text-slate-500">Thứ tự ưu tiên hành động</div>
                        <div class="flex flex-wrap items-center gap-2 text-xs">
                            <x-x2.btn size="sm" variant="gold" icon="heroicon-m-plus">Thêm mới</x-x2.btn><span class="text-slate-300">›</span>
                            <x-x2.btn size="sm" icon="heroicon-m-arrow-down-tray">Xuất dữ liệu</x-x2.btn><span class="text-slate-300">›</span>
                            <x-x2.btn size="sm" icon="heroicon-m-arrow-up-tray">Nhập dữ liệu</x-x2.btn><span class="text-slate-300">›</span>
                            <x-x2.btn size="sm">··· Khác</x-x2.btn>
                        </div>
                    </div>
                </div>
            </x-x2.card.info>
            <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-4">
                @foreach ([
                    ['2. Khi nào dùng?', ['Trang danh sách để thêm/xuất/nhập', 'Trang chi tiết cho lưu/hủy/duyệt', 'Trang có tab thao tác theo ngữ cảnh', 'Tác vụ áp cho toàn trang']],
                    ['3. Vị trí & hành vi', ['Cùng hàng với tab, canh phải', 'Cố định (sticky) khi cuộn', 'Khoảng cách với tab 12–16px', 'Không đặt trong vùng cuộn']],
                    ['4. Responsive', ['≥1280px: hiện đầy đủ nhãn', '1024–1279: ẩn bớt nhãn, giữ icon', '≤1023: gom vào "Khác"', 'Nút Primary luôn hiển thị']],
                    ['5. Nguyên tắc', ['Tối đa 3–5 action trực tiếp', 'Không lặp action trong bảng', 'Dùng "Khác" cho tác vụ ít dùng', 'Thứ tự: Chính→Phụ→Hỗ trợ→Khác']],
                ] as [$title, $items])
                    <x-x2.card.info :title="$title">
                        <ul class="space-y-1.5 text-xs text-slate-600">@foreach ($items as $i)<li class="flex gap-1.5">@svg('heroicon-s-check-circle','h-4 w-4 shrink-0 text-x2-green') {{ $i }}</li>@endforeach</ul>
                    </x-x2.card.info>
                @endforeach
            </div>
            <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-3">
                <x-x2.card.info title="6. Ví dụ — Trang danh sách" icon="heroicon-o-list-bullet">
                    <div class="rounded-xl border border-slate-200 p-2">
                        <div class="flex items-center justify-between border-b border-slate-100 pb-2"><span class="text-sm font-semibold">Cư dân</span><div class="flex gap-1"><x-x2.btn size="sm">Nhập</x-x2.btn><x-x2.btn size="sm">Xuất</x-x2.btn><x-x2.btn size="sm" variant="gold">+ Thêm</x-x2.btn></div></div>
                        <div class="pt-2 text-xs text-slate-400">Bảng danh sách…</div>
                    </div>
                </x-x2.card.info>
                <x-x2.card.info title="7. Ví dụ — Trang chi tiết" icon="heroicon-o-document-text">
                    <div class="rounded-xl border border-slate-200 p-2">
                        <div class="flex items-center justify-between border-b border-slate-100 pb-2"><span class="text-sm font-semibold">Chi tiết cư dân</span><div class="flex gap-1"><x-x2.btn size="sm">Hủy</x-x2.btn><x-x2.btn size="sm" variant="primary">Lưu</x-x2.btn><x-x2.btn size="sm" variant="gold">Phê duyệt</x-x2.btn></div></div>
                        <div class="pt-2 text-xs text-slate-400">Thông tin cá nhân…</div>
                    </div>
                </x-x2.card.info>
                <x-x2.card.info title="8. Ví dụ — Trang có tab" icon="heroicon-o-rectangle-stack">
                    <div class="rounded-xl border border-slate-200 p-2">
                        <div class="flex items-center justify-between border-b border-slate-100 pb-2"><div class="flex gap-2 text-xs"><span class="font-semibold text-x2-primary">Chung</span><span class="text-slate-400">Phân quyền</span></div><x-x2.btn size="sm" variant="gold">+ Thêm</x-x2.btn></div>
                        <div class="pt-2 text-xs text-slate-400">Nội dung tab…</div>
                    </div>
                </x-x2.card.info>
            </div>
        </div>

        {{-- ============ 03 · COMPACT ACTION GROUP ============ --}}
        <div x-show="tab === 'compact'" x-cloak>
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                <x-x2.card.info title="Nhóm hành động gọn (Compact)" icon="heroicon-o-squares-2x2">
                    <p class="mb-3 text-sm text-slate-600">Hiển thị 2–3 hành động thường dùng nhất; đưa hành động ít dùng vào menu overflow "···".</p>
                    <div class="flex items-center gap-2 rounded-xl border border-slate-200 p-2">
                        <x-x2.btn size="sm" variant="primary" icon="heroicon-m-check">Duyệt</x-x2.btn>
                        <x-x2.btn size="sm" icon="heroicon-m-pencil-square">Sửa</x-x2.btn>
                        <span class="grid h-8 w-8 place-items-center rounded-lg border border-slate-200 text-slate-500">@svg('heroicon-m-ellipsis-horizontal','h-4 w-4')</span>
                    </div>
                    <div class="mt-3 w-48 rounded-lg border border-slate-200 bg-white p-1 text-sm shadow">
                        <div class="rounded px-2 py-1.5 hover:bg-slate-50">Sao chép</div>
                        <div class="rounded px-2 py-1.5 hover:bg-slate-50">Gửi thông báo</div>
                        <div class="rounded px-2 py-1.5 hover:bg-slate-50">Lịch sử thay đổi</div>
                        <div class="rounded px-2 py-1.5 text-x2-red hover:bg-x2-red/5">Xóa</div>
                    </div>
                </x-x2.card.info>
                <x-x2.card.info title="Nguyên tắc & Responsive" icon="heroicon-o-device-phone-mobile">
                    <ul class="space-y-2 text-sm text-slate-600">
                        <li class="flex gap-2">@svg('heroicon-s-check-circle','h-5 w-5 text-x2-green') Hiển thị 2–3 action dùng nhiều nhất.</li>
                        <li class="flex gap-2">@svg('heroicon-s-check-circle','h-5 w-5 text-x2-green') Action ít dùng đưa vào overflow "···".</li>
                        <li class="flex gap-2">@svg('heroicon-s-check-circle','h-5 w-5 text-x2-green') Trên mobile ưu tiên icon + tooltip + overflow.</li>
                        <li class="flex gap-2">@svg('heroicon-s-check-circle','h-5 w-5 text-x2-green') Giữ ý nghĩa thao tác dù thu gọn.</li>
                    </ul>
                    <div class="mt-4 rounded-xl border border-slate-200 p-3">
                        <div class="mb-2 text-xs font-semibold text-slate-500">Trên mobile (icon + overflow)</div>
                        <div class="flex gap-2 text-slate-500">@svg('heroicon-m-check','h-5 w-5 text-x2-green')@svg('heroicon-m-pencil-square','h-5 w-5')@svg('heroicon-m-ellipsis-horizontal','h-5 w-5')</div>
                    </div>
                </x-x2.card.info>
            </div>
        </div>

        {{-- ============ 04 · HEADER QUICK CREATE vs PAGE CREATE ============ --}}
        <div x-show="tab === 'create'" x-cloak>
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                <x-x2.card.info title="Header · + Tạo mới (Global Quick Create)" icon="heroicon-o-bolt">
                    <p class="mb-3 text-sm text-slate-600">Tạo nhanh nhiều loại bản ghi từ bất kỳ đâu trong hệ thống.</p>
                    <x-x2.btn variant="gold" icon="heroicon-m-plus">Tạo mới</x-x2.btn>
                    <div class="mt-2 w-56 rounded-lg border border-slate-200 bg-white p-1 text-sm shadow">
                        @foreach (['heroicon-m-user-plus' => 'Tạo cư dân', 'heroicon-m-home' => 'Tạo căn hộ', 'heroicon-m-banknotes' => 'Tạo phiếu thu', 'heroicon-m-chat-bubble-left-ellipsis' => 'Tạo phản ánh', 'heroicon-m-megaphone' => 'Tạo thông báo'] as $ic => $lbl)
                            <div class="flex items-center gap-2 rounded px-2 py-1.5 hover:bg-slate-50">@svg($ic,'h-4 w-4 text-slate-400') {{ $lbl }}</div>
                        @endforeach
                    </div>
                    <p class="mt-3 text-xs text-slate-400">Toàn cục, không phụ thuộc module đang xem.</p>
                </x-x2.card.info>
                <x-x2.card.info title="Page · + Thêm mới (Page Create)" icon="heroicon-o-plus-circle">
                    <p class="mb-3 text-sm text-slate-600">Tạo bản ghi thuộc đúng module/trang đang xem.</p>
                    <div class="flex items-center justify-between rounded-xl border border-slate-200 p-3">
                        <span class="text-sm font-semibold text-slate-700">Danh sách cư dân</span>
                        <x-x2.btn variant="gold" icon="heroicon-m-plus">Thêm mới</x-x2.btn>
                    </div>
                    <div class="mt-3 rounded-lg bg-x2-amber/5 px-3 py-2 text-xs text-slate-600">
                        <b>Phân biệt:</b> Header Quick Create không thay thế form chuẩn của module — nó chỉ mở nhanh; form đầy đủ vẫn thuộc trang module.
                    </div>
                </x-x2.card.info>
            </div>
        </div>

        {{-- ============ 05 · ROW ACTIONS ============ --}}
        <div x-show="tab === 'row'" x-cloak>
            <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
                <div class="space-y-6 xl:col-span-2">
                    <x-x2.card.info title="1. Nguyên tắc hành động trên mỗi dòng" icon="heroicon-o-bars-3">
                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
                            @foreach ([
                                ['heroicon-o-eye', 'Icon-only (mặc định)', 'Giữ bảng gọn, dễ quét. Cho màn mật độ cao.', 'green', 'Hay dùng'],
                                ['heroicon-o-question-mark-circle', 'Icon + Tooltip', 'Hiện tooltip khi hover để giải thích.', 'green', 'Hay dùng'],
                                ['heroicon-o-trash', 'Hành động nguy hiểm', 'Đặt cuối nhóm/trong More. Cần xác nhận.', 'red', 'Nguy hiểm'],
                                ['heroicon-o-ellipsis-horizontal', 'Đưa vào More', 'Hành động hiếm dùng nên vào menu More.', 'blue', 'Đưa vào More'],
                            ] as [$ic, $t, $d, $tone, $tag])
                                <div class="rounded-xl border border-slate-200 p-3">
                                    <div class="mb-1 text-x2-{{ $tone === 'blue' ? 'primary' : $tone }}">@svg($ic,'h-5 w-5')</div>
                                    <div class="text-sm font-semibold text-slate-700">{{ $t }}</div>
                                    <p class="mt-0.5 text-xs text-slate-500">{{ $d }}</p>
                                    <span class="mt-2 inline-block rounded bg-x2-{{ $tone === 'blue' ? 'primary' : $tone }}/10 px-1.5 py-0.5 text-[10px] font-semibold text-x2-{{ $tone === 'blue' ? 'primary' : $tone }}">{{ $tag }}</span>
                                </div>
                            @endforeach
                        </div>
                    </x-x2.card.info>

                    <x-x2.card.info title="2. Bảng danh sách với row actions" icon="heroicon-o-table-cells">
                        <x-x2.table.data>
                            <x-slot:head><th class="px-4 py-2">Mã CD</th><th class="px-4 py-2">Họ tên</th><th class="px-4 py-2">Căn hộ</th><th class="px-4 py-2">Trạng thái</th><th class="px-4 py-2 text-right">Hành động</th></x-slot:head>
                            @foreach ([['CD-0001256', 'Nguyễn Văn Hùng', 'A12.06', 'Chờ duyệt', 'amber'], ['CD-0001248', 'Trần Thị Lan', 'B-0504', 'Đang xử lý', 'blue'], ['CD-0001239', 'Phạm Thị Mai', 'A-0205', 'Đã chấp thuận', 'green']] as [$c, $n, $ap, $st, $tone])
                                <tr class="hover:bg-slate-50">
                                    <td class="px-4 py-2.5 text-x2-primary">{{ $c }}</td><td class="px-4 py-2.5">{{ $n }}</td><td class="px-4 py-2.5">{{ $ap }}</td>
                                    <td class="px-4 py-2.5"><x-x2.status-badge :label="$st" :tone="$tone" /></td>
                                    <td class="px-4 py-2.5"><div class="flex justify-end gap-1 text-slate-400">@svg('heroicon-m-eye','h-5 w-5')@svg('heroicon-m-pencil-square','h-5 w-5')<span class="text-x2-green">@svg('heroicon-m-check-circle','h-5 w-5')</span>@svg('heroicon-m-document-duplicate','h-5 w-5')<span class="text-x2-red">@svg('heroicon-m-trash','h-5 w-5')</span>@svg('heroicon-m-ellipsis-vertical','h-5 w-5')</div></td>
                                </tr>
                            @endforeach
                        </x-x2.table.data>
                    </x-x2.card.info>
                </div>

                <div class="space-y-6">
                    <x-x2.card.info title="3. Thứ tự khuyến nghị" icon="heroicon-o-numbered-list">
                        <div class="flex items-center gap-3 text-slate-400">@svg('heroicon-m-eye','h-5 w-5')@svg('heroicon-m-pencil-square','h-5 w-5')<span class="text-x2-green">@svg('heroicon-m-check-circle','h-5 w-5')</span>@svg('heroicon-m-document-duplicate','h-5 w-5')<span class="text-x2-red">@svg('heroicon-m-trash','h-5 w-5')</span>@svg('heroicon-m-ellipsis-vertical','h-5 w-5')</div>
                        <ol class="mt-3 space-y-1 text-xs text-slate-600">
                            <li>1. Xem chi tiết</li><li>2. Chỉnh sửa</li><li>3. Phê duyệt / Xác nhận</li><li>4. Sao chép / Tạo lại</li><li>5. Xóa (nguy hiểm)</li><li>6. More (hành động hiếm)</li>
                        </ol>
                    </x-x2.card.info>
                    <x-x2.card.info title="5. Ví dụ menu More" icon="heroicon-o-ellipsis-horizontal">
                        <div class="rounded-lg border border-slate-200 p-1 text-sm">
                            <div class="flex items-center gap-2 rounded px-2 py-1.5 hover:bg-slate-50">@svg('heroicon-m-paper-airplane','h-4 w-4 text-slate-400') Gửi thông báo</div>
                            <div class="flex items-center gap-2 rounded px-2 py-1.5 hover:bg-slate-50">@svg('heroicon-m-pencil','h-4 w-4 text-slate-400') Ghi chú</div>
                            <div class="flex items-center gap-2 rounded px-2 py-1.5 hover:bg-slate-50">@svg('heroicon-m-clock','h-4 w-4 text-slate-400') Lịch sử hoạt động</div>
                            <div class="flex items-center gap-2 rounded px-2 py-1.5 text-x2-red hover:bg-x2-red/5">@svg('heroicon-m-lock-closed','h-4 w-4') Khóa cư dân</div>
                        </div>
                    </x-x2.card.info>
                </div>
            </div>
        </div>

        {{-- ============ 06 · BULK ACTION BAR ============ --}}
        <div x-show="tab === 'bulk'" x-cloak>
            <x-x2.card.info title="Bulk Action Bar là gì?" icon="heroicon-o-queue-list">
                <p class="text-sm text-slate-600">Xuất hiện khi chọn ≥1 bản ghi; giúp thao tác hàng loạt: gộp, gửi yêu cầu, gắn nhãn, xuất dữ liệu, xóa. Thanh cố định (sticky) và luôn hiển thị số bản ghi đang chọn.</p>
            </x-x2.card.info>
            <div class="mt-6">
                <x-x2.card.info title="Ví dụ — chọn nhiều bản ghi" icon="heroicon-o-table-cells">
                    <x-x2.table.bulk-actions :count="5" label="Đã chọn">
                        <x-x2.btn size="sm" icon="heroicon-m-arrows-right-left">Gộp bản ghi</x-x2.btn>
                        <x-x2.btn size="sm" icon="heroicon-m-paper-airplane">Gửi yêu cầu cập nhật</x-x2.btn>
                        <x-x2.btn size="sm" icon="heroicon-m-tag">Gắn nhãn</x-x2.btn>
                        <x-x2.btn size="sm" icon="heroicon-m-arrow-down-tray">Xuất dữ liệu</x-x2.btn>
                        <x-x2.btn size="sm" variant="danger" icon="heroicon-m-trash">Xóa</x-x2.btn>
                        <span class="grid h-8 w-8 place-items-center rounded-lg border border-slate-200 text-slate-400">@svg('heroicon-m-ellipsis-horizontal','h-4 w-4')</span>
                    </x-x2.table.bulk-actions>
                    <div class="mt-3">
                        <x-x2.table.data>
                            <x-slot:head><th class="px-4 py-2 w-8"><input type="checkbox" checked class="rounded text-x2-primary" /></th><th class="px-4 py-2">Mã CD</th><th class="px-4 py-2">Họ tên</th><th class="px-4 py-2">Căn hộ</th><th class="px-4 py-2">Trạng thái</th></x-slot:head>
                            @foreach ([['CD-0001256', 'Nguyễn Văn Hùng', 'A12.06', true], ['CD-0001248', 'Trần Thị Lan', 'B-0504', true], ['CD-0001245', 'Lê Văn Cường', 'B-0503', true], ['CD-0001239', 'Phạm Thị Mai', 'A-0205', false], ['CD-0001231', 'Hoàng Minh Quân', 'C-0902', true]] as [$c, $n, $ap, $sel])
                                <tr class="{{ $sel ? 'bg-x2-primary/5' : '' }} hover:bg-slate-50"><td class="px-4 py-2.5"><input type="checkbox" @checked($sel) class="rounded text-x2-primary" /></td><td class="px-4 py-2.5 text-x2-primary">{{ $c }}</td><td class="px-4 py-2.5">{{ $n }}</td><td class="px-4 py-2.5">{{ $ap }}</td><td class="px-4 py-2.5"><x-x2.status-badge label="Đang hoạt động" tone="green" /></td></tr>
                            @endforeach
                        </x-x2.table.data>
                    </div>
                    <div class="mt-4 grid gap-3 text-xs text-slate-500 sm:grid-cols-3">
                        <div><div class="font-semibold text-slate-700">Vị trí</div>Trên bảng, dưới bộ lọc; sticky khi cuộn.</div>
                        <div><div class="font-semibold text-slate-700">Trạng thái</div>Ẩn khi chưa chọn; hiện khi ≥1 bản ghi.</div>
                        <div><div class="font-semibold text-slate-700">Progressive disclosure</div>Action phổ biến hiện trực tiếp; hiếm vào "···".</div>
                    </div>
                </x-x2.card.info>
            </div>
        </div>

        {{-- ============ 07 · SPLIT BUTTON ============ --}}
        <div x-show="tab === 'split'" x-cloak>
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-4">
                <x-x2.card.info title="1. Tổng quan Split Button" icon="heroicon-o-cursor-arrow-rays">
                    <p class="text-sm text-slate-600">Kết hợp hành động chính (mặc định) + các hành động liên quan trong một nút.</p>
                    <div class="mt-3 inline-flex overflow-hidden rounded-lg shadow-sm"><button class="bg-x2-primary px-3 py-2 text-sm font-semibold text-white">Phê duyệt</button><button class="border-l border-white/20 bg-x2-primary px-2 text-white">@svg('heroicon-m-chevron-down','h-4 w-4')</button></div>
                </x-x2.card.info>
                <x-x2.card.info title="2. Khi nào dùng Split?">
                    <ul class="space-y-1.5 text-xs text-slate-600"><li class="flex gap-1.5">@svg('heroicon-s-check-circle','h-4 w-4 text-x2-green') 1 hành động chính tần suất cao (≥70%)</li><li class="flex gap-1.5">@svg('heroicon-s-check-circle','h-4 w-4 text-x2-green') Các hành động cùng mục tiêu</li><li class="flex gap-1.5">@svg('heroicon-s-check-circle','h-4 w-4 text-x2-green') Tiết kiệm không gian</li></ul>
                </x-x2.card.info>
                <x-x2.card.info title="3. Khi nào dùng nút thường?">
                    <ul class="space-y-1.5 text-xs text-slate-600"><li class="flex gap-1.5">@svg('heroicon-s-x-circle','h-4 w-4 text-x2-red') Chỉ 1 hành động duy nhất</li><li class="flex gap-1.5">@svg('heroicon-s-x-circle','h-4 w-4 text-x2-red') Hành động không liên quan</li><li class="flex gap-1.5">@svg('heroicon-s-x-circle','h-4 w-4 text-x2-red') Tần suất tương đương</li></ul>
                </x-x2.card.info>
                <x-x2.card.info title="4. Gợi ý nhãn mặc định">
                    <ul class="space-y-1.5 text-xs text-slate-600"><li>Dùng động từ, ngắn gọn, rõ.</li><li>Phản ánh hành động phổ biến nhất.</li><li>Tránh nhãn chung như "Thao tác".</li></ul>
                </x-x2.card.info>
            </div>
            <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-3">
                <x-x2.card.info title="5. Ví dụ — Phê duyệt" icon="heroicon-o-check-badge">
                    <div class="inline-flex overflow-hidden rounded-lg shadow-sm"><button class="bg-x2-primary px-3 py-2 text-sm font-semibold text-white">Phê duyệt</button><button class="border-l border-white/20 bg-x2-primary px-2 text-white">@svg('heroicon-m-chevron-down','h-4 w-4')</button></div>
                    <div class="mt-2 w-64 rounded-lg border border-slate-200 bg-white p-1 text-sm shadow">
                        <div class="rounded px-2 py-1.5 hover:bg-slate-50">✓ Phê duyệt</div>
                        <div class="rounded px-2 py-1.5 hover:bg-slate-50">Phê duyệt có điều kiện</div>
                        <div class="rounded px-2 py-1.5 hover:bg-slate-50">Phê duyệt & gửi thông báo</div>
                        <div class="rounded px-2 py-1.5 hover:bg-slate-50">Phê duyệt & in</div>
                    </div>
                </x-x2.card.info>
                <x-x2.card.info title="6. Ví dụ — Xuất dữ liệu" icon="heroicon-o-arrow-down-tray">
                    <div class="inline-flex overflow-hidden rounded-lg border border-slate-200"><button class="px-3 py-2 text-sm font-medium text-slate-600">Xuất dữ liệu</button><button class="border-l border-slate-200 px-2 text-slate-500">@svg('heroicon-m-chevron-down','h-4 w-4')</button></div>
                    <div class="mt-2 w-52 rounded-lg border border-slate-200 bg-white p-1 text-sm shadow"><div class="rounded px-2 py-1.5 hover:bg-slate-50">Excel (.xlsx)</div><div class="rounded px-2 py-1.5 hover:bg-slate-50">CSV (.csv)</div><div class="rounded px-2 py-1.5 hover:bg-slate-50">PDF</div><div class="rounded px-2 py-1.5 hover:bg-slate-50">Google Sheets</div></div>
                </x-x2.card.info>
                <x-x2.card.info title="7. Ví dụ — Thanh toán / Form footer" icon="heroicon-o-banknotes">
                    <div class="inline-flex overflow-hidden rounded-lg shadow-sm"><button class="bg-x2-gold px-3 py-2 text-sm font-semibold text-white">Thanh toán</button><button class="border-l border-white/20 bg-x2-gold px-2 text-white">@svg('heroicon-m-chevron-down','h-4 w-4')</button></div>
                    <div class="mt-2 w-56 rounded-lg border border-slate-200 bg-white p-1 text-sm shadow"><div class="rounded px-2 py-1.5 hover:bg-slate-50">Thanh toán ngay</div><div class="rounded px-2 py-1.5 hover:bg-slate-50">Thanh toán & in biên lai</div><div class="rounded px-2 py-1.5 hover:bg-slate-50">Trả góp</div><div class="rounded px-2 py-1.5 hover:bg-slate-50">Thanh toán qua QR</div></div>
                    <div class="mt-4 border-t border-slate-100 pt-3"><div class="mb-2 text-xs font-semibold text-slate-500">Form footer</div><div class="inline-flex overflow-hidden rounded-lg shadow-sm"><button class="bg-x2-primary px-3 py-2 text-sm font-semibold text-white">Lưu</button><button class="border-l border-white/20 bg-x2-primary px-2 text-white">@svg('heroicon-m-chevron-down','h-4 w-4')</button></div></div>
                </x-x2.card.info>
            </div>
        </div>

        {{-- ============ 08 · BADGE COUNT ============ --}}
        <div x-show="tab === 'badge'" x-cloak>
            <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
                <div class="space-y-6 xl:col-span-2">
                    <x-x2.card.info title="1. Hệ thống Badge Count" icon="heroicon-o-bell-alert">
                        <div class="grid grid-cols-2 gap-4 sm:grid-cols-3">
                            <div><div class="mb-1.5 text-xs text-slate-400">Sidebar (Menu)</div><span class="inline-flex items-center gap-2 rounded-lg bg-x2-navy px-3 py-1.5 text-sm text-white">Duyệt cư dân <span class="grid h-5 min-w-5 place-items-center rounded-full bg-x2-red px-1 text-[11px] font-bold">11</span></span></div>
                            <div><div class="mb-1.5 text-xs text-slate-400">Header (Notification)</div><span class="relative inline-block">@svg('heroicon-o-bell','h-6 w-6 text-slate-500')<span class="absolute -right-1.5 -top-1.5 grid h-4 min-w-4 place-items-center rounded-full bg-x2-red px-0.5 text-[10px] font-bold text-white">27</span></span></div>
                            <div><div class="mb-1.5 text-xs text-slate-400">Inbox / Approval</div><span class="inline-flex items-center gap-1.5 text-sm text-slate-600">Chờ duyệt <span class="grid h-5 min-w-5 place-items-center rounded-full bg-x2-red px-1 text-[11px] font-bold text-white">11</span></span></div>
                            <div><div class="mb-1.5 text-xs text-slate-400">Tabs</div><span class="text-sm font-semibold text-x2-primary">Chờ duyệt (11)</span></div>
                            <div><div class="mb-1.5 text-xs text-slate-400">Card / KPI</div><span class="text-sm">Chờ duyệt <b class="text-x2-primary">11</b> <span class="text-x2-green">↑5</span></span></div>
                            <div><div class="mb-1.5 text-xs text-slate-400">List item</div><span class="inline-flex items-center gap-1.5 text-sm">Yêu cầu cập nhật <span class="grid h-5 min-w-5 place-items-center rounded-full bg-x2-primary px-1 text-[11px] font-bold text-white">3</span></span></div>
                        </div>
                    </x-x2.card.info>
                    <x-x2.card.info title="2. Biến thể Badge" icon="heroicon-o-squares-2x2">
                        <div class="flex flex-wrap items-center gap-6 text-sm">
                            <div><div class="mb-1 text-xs text-slate-400">Số nhỏ (0–99)</div><span class="grid h-5 min-w-5 place-items-center rounded-full bg-x2-red px-1 text-[11px] font-bold text-white">11</span></div>
                            <div><div class="mb-1 text-xs text-slate-400">Số lớn (≥100)</div><span class="grid h-5 min-w-5 place-items-center rounded-full bg-x2-red px-1 text-[11px] font-bold text-white">99+</span></div>
                            <div><div class="mb-1 text-xs text-slate-400">Dot (chỉ báo)</div><span class="inline-block h-2.5 w-2.5 rounded-full bg-x2-red"></span></div>
                            <div><div class="mb-1 text-xs text-slate-400">Trong tab</div><span class="font-semibold text-x2-primary">Chờ duyệt (11)</span></div>
                            <div><div class="mb-1 text-xs text-slate-400">Trong KPI</div><b class="text-x2-primary">37</b></div>
                        </div>
                    </x-x2.card.info>
                    <x-x2.card.info title="4. Ví dụ thực tế — Duyệt cư dân" icon="heroicon-o-inbox-stack">
                        <x-x2.kpi-row :cols="6">
                            <x-x2.card.kpi label="Tổng yêu cầu" value="1.269" accent="blue" icon="heroicon-o-inbox" trend="12,4%" />
                            <x-x2.card.kpi label="Chờ duyệt" value="11" accent="amber" icon="heroicon-o-clock" trend="5" />
                            <x-x2.card.kpi label="Quá hạn" value="3" accent="red" icon="heroicon-o-exclamation-triangle" trend="2" />
                            <x-x2.card.kpi label="Hồ sơ bổ sung" value="7" accent="blue" icon="heroicon-o-document-plus" />
                            <x-x2.card.kpi label="Đã duyệt hôm nay" value="14" accent="green" icon="heroicon-o-check-badge" trend="20%" />
                            <x-x2.card.kpi label="Đã từ chối" value="2" accent="slate" icon="heroicon-o-x-circle" :trendUp="false" trend="33%" />
                        </x-x2.kpi-row>
                    </x-x2.card.info>
                </div>
                <x-x2.card.info title="3. Ý nghĩa màu sắc" icon="heroicon-o-swatch">
                    @foreach ([['red', 'Đỏ (Error)', 'Lỗi, khẩn cấp, quá hạn, bị từ chối'], ['amber', 'Cam (Warning)', 'Cảnh báo, sắp đến hạn'], ['blue', 'Xanh (Info)', 'Thông tin chung, chờ xử lý'], ['slate', 'Xám (Default)', 'Số phụ, không quan trọng']] as [$tone, $name, $desc])
                        <div class="flex items-start gap-2 border-b border-slate-50 py-2"><span class="grid h-5 min-w-5 place-items-center rounded-full bg-x2-{{ $tone === 'slate' ? 'slate' : $tone }} px-1 text-[11px] font-bold text-white">11</span><div><div class="text-sm font-semibold text-slate-700">{{ $name }}</div><div class="text-xs text-slate-500">{{ $desc }}</div></div></div>
                    @endforeach
                    <div class="mt-3 text-xs text-slate-500"><div class="mb-1 font-semibold text-slate-700">Quy tắc</div><ul class="space-y-1"><li>• Chỉ hiện khi count > 0.</li><li>• ≥100 hiển thị 99+.</li><li>• Badge tự ẩn khi về 0.</li></ul></div>
                </x-x2.card.info>
            </div>
        </div>

        {{-- ============ 09 · STATUS PILL ============ --}}
        <div x-show="tab === 'status'" x-cloak>
            <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
                <div class="space-y-6 xl:col-span-2">
                    <x-x2.card.info title="1. Danh sách trạng thái (Status pills)" icon="heroicon-o-flag">
                        <div class="flex flex-wrap gap-2">
                            {!! $pill('Hoạt động', 'heroicon-m-check-circle', 'green') !!}
                            {!! $pill('Chờ duyệt', 'heroicon-m-clock', 'amber') !!}
                            {!! $pill('Tạm khóa', 'heroicon-m-pause-circle', 'amber') !!}
                            {!! $pill('Đã khóa', 'heroicon-m-lock-closed', 'slate') !!}
                            {!! $pill('Quá hạn', 'heroicon-m-exclamation-triangle', 'red') !!}
                            {!! $pill('Thành công', 'heroicon-m-check-badge', 'green') !!}
                            {!! $pill('Thất bại', 'heroicon-m-x-circle', 'red') !!}
                            {!! $pill('Cần xác minh', 'heroicon-m-shield-exclamation', 'blue') !!}
                            {!! $pill('AI đề xuất', 'heroicon-m-sparkles', 'ai') !!}
                        </div>
                        <p class="mt-3 text-xs text-slate-500">Status pill = <b>icon + màu + text</b>, không chỉ dùng màu. Text ngắn 2–3 từ, map từ enum backend.</p>
                    </x-x2.card.info>
                    <x-x2.card.info title="2. Bảng dữ liệu với status" icon="heroicon-o-table-cells">
                        <x-x2.table.data>
                            <x-slot:head><th class="px-4 py-2">Mã yêu cầu</th><th class="px-4 py-2">Loại</th><th class="px-4 py-2">Người yêu cầu</th><th class="px-4 py-2">Hạn xử lý</th><th class="px-4 py-2">Trạng thái</th></x-slot:head>
                            @foreach ([
                                ['REQ-2026-0712', 'Đề nghị thanh toán', 'Nguyễn Văn Hùng', '14/07', 'Chờ duyệt', 'heroicon-m-clock', 'amber'],
                                ['REQ-2026-0711', 'Đăng ký khách', 'Trần Thị Lan', '12/07', 'Hoạt động', 'heroicon-m-check-circle', 'green'],
                                ['INV-2026-0708', 'Hóa đơn dịch vụ', 'Hoàng Minh Quân', '12/07', 'Quá hạn', 'heroicon-m-exclamation-triangle', 'red'],
                                ['VER-2026-0705', 'Xác minh CCCD', 'Đỗ Thị Hà', '12/07', 'Cần xác minh', 'heroicon-m-shield-exclamation', 'blue'],
                                ['SUG-2026-0704', 'Đề xuất AI', 'AI Assistant', '—', 'AI đề xuất', 'heroicon-m-sparkles', 'ai'],
                            ] as [$mid, $type, $who, $due, $st, $ic, $tone])
                                <tr class="hover:bg-slate-50"><td class="px-4 py-2.5 text-x2-primary">{{ $mid }}</td><td class="px-4 py-2.5">{{ $type }}</td><td class="px-4 py-2.5">{{ $who }}</td><td class="px-4 py-2.5 text-slate-500">{{ $due }}</td><td class="px-4 py-2.5">{!! $pill($st, $ic, $tone) !!}</td></tr>
                            @endforeach
                        </x-x2.table.data>
                    </x-x2.card.info>
                    <x-x2.card.info title="6. Ứng dụng theo ngữ cảnh" icon="heroicon-o-rectangle-group">
                        <div class="grid grid-cols-2 gap-4 sm:grid-cols-5 text-xs">
                            <div><div class="mb-1.5 font-semibold text-slate-500">Phê duyệt</div><div class="space-y-1">{!! $pill('Chờ duyệt','heroicon-m-clock','amber') !!}{!! $pill('Đã duyệt','heroicon-m-check-circle','green') !!}{!! $pill('Từ chối','heroicon-m-x-circle','red') !!}</div></div>
                            <div><div class="mb-1.5 font-semibold text-slate-500">Tài chính</div><div class="space-y-1">{!! $pill('Chờ thanh toán','heroicon-m-clock','amber') !!}{!! $pill('Đã thanh toán','heroicon-m-check-circle','green') !!}{!! $pill('Quá hạn','heroicon-m-exclamation-triangle','red') !!}</div></div>
                            <div><div class="mb-1.5 font-semibold text-slate-500">Vận hành</div><div class="space-y-1">{!! $pill('Mới tạo','heroicon-m-plus-circle','blue') !!}{!! $pill('Đang xử lý','heroicon-m-arrow-path','amber') !!}{!! $pill('Hoàn tất','heroicon-m-check-circle','green') !!}</div></div>
                            <div><div class="mb-1.5 font-semibold text-slate-500">Hồ sơ</div><div class="space-y-1">{!! $pill('Cần xác minh','heroicon-m-shield-exclamation','blue') !!}{!! $pill('Xác minh OK','heroicon-m-check-circle','green') !!}{!! $pill('Xác minh lỗi','heroicon-m-x-circle','red') !!}</div></div>
                            <div><div class="mb-1.5 font-semibold text-slate-500">AI</div><div class="space-y-1">{!! $pill('AI đề xuất','heroicon-m-sparkles','ai') !!}{!! $pill('Đang đánh giá','heroicon-m-cpu-chip','amber') !!}{!! $pill('Đã áp dụng','heroicon-m-check-circle','green') !!}</div></div>
                        </div>
                    </x-x2.card.info>
                </div>
                <div class="space-y-6">
                    <x-x2.card.info title="3. Chi tiết yêu cầu" icon="heroicon-o-identification">
                        <x-slot:actions>{!! $pill('Hoạt động','heroicon-m-check-circle','green') !!}</x-slot:actions>
                        <dl class="space-y-1.5 text-sm">
                            <div class="flex justify-between"><dt class="text-slate-500">Mã yêu cầu</dt><dd class="font-medium">REQ-2026-0711</dd></div>
                            <div class="flex justify-between"><dt class="text-slate-500">Loại</dt><dd class="font-medium">Đăng ký khách</dd></div>
                            <div class="flex justify-between"><dt class="text-slate-500">Người yêu cầu</dt><dd class="font-medium">Trần Thị Lan</dd></div>
                            <div class="flex justify-between"><dt class="text-slate-500">Ưu tiên</dt><dd>{!! $pill('Trung bình','heroicon-m-flag','amber') !!}</dd></div>
                        </dl>
                    </x-x2.card.info>
                    <x-x2.card.info title="Quy tắc đặt nhãn" icon="heroicon-o-information-circle">
                        <ul class="space-y-1.5 text-xs text-slate-600">
                            <li>• Ngắn gọn, rõ ràng, tối đa 2–3 từ.</li>
                            <li>• Dùng danh từ hoặc cụm danh từ.</li>
                            <li>• Nhất quán về thuật ngữ và màu sắc.</li>
                            <li>• Kết hợp icon + màu tăng nhận diện.</li>
                            <li>• Đảm bảo tương phản WCAG AA.</li>
                        </ul>
                    </x-x2.card.info>
                </div>
            </div>
        </div>

        {{-- ============ 10 · ACTION DECISION MATRIX ============ --}}
        <div x-show="tab === 'matrix'" x-cloak>
            <div class="grid grid-cols-1 gap-6 xl:grid-cols-4">
                <div class="xl:col-span-3">
                    <x-x2.card.info title="Ma trận quyết định hành động" icon="heroicon-o-table-cells">
                        <x-x2.table.data>
                            <x-slot:head><th class="px-3 py-2 w-8">#</th><th class="px-3 py-2">Context</th><th class="px-3 py-2">Recommended pattern</th><th class="px-3 py-2">Notes</th></x-slot:head>
                            @foreach ([
                                ['1', 'Header action', 'Hành động cấp trang, toàn bộ trang.', '+ Tạo mới (Quick Create dropdown)', 'Tạo bản ghi mới / tác vụ toàn trang.'],
                                ['2', 'Tab-row action', 'Liên quan phạm vi tab hiện tại.', 'Right-aligned actions (Xuất, Cấu hình, Thêm)', 'Thiết lập hiển thị/lọc/xuất của tab.'],
                                ['3', 'Page toolbar', 'Áp cho selection / danh sách đang lọc.', 'Gửi yêu cầu · Gắn nhãn · More', 'Tác vụ nhanh trên danh sách.'],
                                ['4', 'Row action', 'Trên một bản ghi.', 'Icon-only + More', 'Chỉ thao tác trên dòng đó.'],
                                ['5', 'Bulk action', 'Nhiều bản ghi đã chọn.', 'Bulk action bar', 'Chỉ hiện khi có selection.'],
                                ['6', 'Form footer', 'Cuối form chi tiết.', 'Hủy + Lưu/Xác nhận', 'Ưu tiên 1 primary; phụ ở trái.'],
                                ['7', 'Split button', 'Hành động chính + biến thể.', 'Primary ▾ (Tạo mới / Lưu…)', 'Có 1 default rõ ràng.'],
                                ['8', 'Overflow action', 'Nhiều action phụ, ít dùng.', '··· menu', 'Giữ giao diện gọn gàng.'],
                                ['9', 'Danger action', 'Rủi ro, có thể mất dữ liệu.', 'Đỏ + confirm modal', 'Luôn xác nhận + audit log.'],
                                ['10', 'Global quick create', 'Tạo nhanh bất kỳ đâu.', 'FAB / header + dropdown', 'Tùy chỉnh theo vai trò.'],
                            ] as [$n, $ctx, $ctxd, $pattern, $note])
                                <tr class="hover:bg-slate-50 align-top">
                                    <td class="px-3 py-2.5 text-slate-400">{{ $n }}</td>
                                    <td class="px-3 py-2.5"><div class="font-semibold text-slate-800">{{ $ctx }}</div><div class="text-xs text-slate-500">{{ $ctxd }}</div></td>
                                    <td class="px-3 py-2.5 text-slate-600">{{ $pattern }}</td>
                                    <td class="px-3 py-2.5 text-xs text-slate-500">{{ $note }}</td>
                                </tr>
                            @endforeach
                        </x-x2.table.data>
                    </x-x2.card.info>
                </div>
                <x-x2.card.info title="Quy tắc quyết định nhanh" icon="heroicon-o-check-badge">
                    @foreach ([
                        ['Ưu tiên vị trí cao nhất phù hợp', 'Header > Tab-row > Toolbar > Row.'],
                        ['Chỉ 1 hành động chính', 'Mỗi nhóm nên có tối đa 1 primary để tránh gây nhiễu.'],
                        ['Giữ gọn — Dễ quét', 'Hành động thường dùng hiện trước; ít dùng vào overflow.'],
                        ['Phản hồi & trạng thái', 'Mọi hành động có loading / thành công / lỗi.'],
                        ['Nhất quán toàn hệ thống', 'Không tạo vị trí action mới ngoài ma trận nếu không có lý do.'],
                    ] as [$t, $d])
                        <div class="flex gap-2 border-b border-slate-50 py-2">@svg('heroicon-s-check-circle','h-5 w-5 shrink-0 text-x2-green')<div><div class="text-sm font-semibold text-slate-700">{{ $t }}</div><div class="text-xs text-slate-500">{{ $d }}</div></div></div>
                    @endforeach
                </x-x2.card.info>
            </div>
        </div>
    </div>
</x-filament-panels::page>
