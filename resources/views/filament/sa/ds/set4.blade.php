<x-filament-panels::page>
    @php
        $tabs = [
            'anatomy' => 'Giải phẫu trang',
            'actionbar' => 'Header & Action bar',
            'kpi' => 'KPI theo bộ lọc',
            'filter' => 'Bộ lọc & Cột',
            'table' => 'Bảng & Freeze cột',
            'pagination' => 'Phân trang',
            'contract' => 'Contract & Checklist',
        ];
    @endphp

    <div x-data="{ tab: 'anatomy' }">
        <div class="mb-4 flex items-start gap-2 rounded-xl border border-x2-primary/20 bg-x2-primary/5 px-4 py-3 text-sm text-slate-600">
            <span class="text-x2-primary">@svg('heroicon-o-information-circle', 'h-5 w-5')</span>
            <p><b>DS-04 · Trang danh sách chuẩn.</b> Khuôn dùng chung cho MỌI trang <code>/admin</code>, <code>/hq</code>, <code>/sa</code> dạng danh sách.
                Hiện thực mẫu: <b>ResidentDirectory</b> (<code>/admin/residents</code>) &amp; <b>ApartmentDirectory</b> (<code>/admin/apartments</code>).
                Khi dựng trang list mới, dùng skill <code>x2bms-admin-listing-page</code> và bám đúng contract ở tab cuối.</p>
        </div>

        <div class="mb-6 flex flex-wrap items-center gap-1 overflow-x-auto border-b border-slate-200">
            @foreach ($tabs as $key => $label)
                <button type="button" @click="tab='{{ $key }}'"
                    class="font-title whitespace-nowrap border-b-2 px-3.5 py-2.5 text-[15px] font-semibold transition"
                    :class="tab === '{{ $key }}' ? 'border-x2-primary text-x2-primary' : 'border-transparent text-slate-500 hover:text-slate-800'">
                    {{ $label }}
                </button>
            @endforeach
        </div>

        {{-- ============ 01 · ANATOMY ============ --}}
        <div x-show="tab === 'anatomy'">
            <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
                <div class="space-y-4 xl:col-span-2">
                    <x-x2.card.info title="Giải phẫu từ trên xuống" icon="heroicon-o-view-columns">
                        {{-- Mockup xếp lớp: mỗi khối là 1 vùng của trang list chuẩn. --}}
                        <div class="space-y-2 rounded-xl border border-dashed border-slate-300 bg-slate-50/60 p-3 text-xs">
                            <div class="rounded-lg border border-slate-200 bg-white px-3 py-2">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <div class="text-[11px] text-slate-400">① Breadcrumb có icon · Title ẩn ở topbar</div>
                                        <div class="mt-0.5 flex items-center gap-1 text-slate-500">@svg('heroicon-m-home','h-3.5 w-3.5') Tổng quan <span class="text-slate-300">›</span> Cư dân &amp; Căn hộ <span class="text-slate-300">›</span> <b class="text-slate-700">Hồ sơ căn hộ</b></div>
                                    </div>
                                    <div class="flex gap-1">
                                        <span class="rounded border border-slate-200 px-2 py-1 text-slate-500">Nhập</span>
                                        <span class="rounded border border-slate-200 px-2 py-1 text-slate-500">Xuất</span>
                                        <span class="rounded bg-x2-gold px-2 py-1 font-semibold text-white">+ Thêm</span>
                                        <span class="grid place-items-center rounded border border-slate-200 px-1.5 text-slate-400">···</span>
                                    </div>
                                </div>
                                <div class="mt-1 text-[10px] text-x2-primary">② Action bar canh phải · thứ tự Chính→Phụ→Hỗ trợ→Khác</div>
                            </div>
                            <div class="rounded-lg border border-slate-200 bg-white px-3 py-2">
                                <div class="mb-1 text-[10px] text-x2-primary">③ KPI 5 card — cập nhật THEO bộ lọc</div>
                                <div class="grid grid-cols-5 gap-1.5">
                                    @foreach (['Tổng','Đã ở','Trống','Chờ gắn','Nợ phí'] as $k)
                                        <div class="rounded border border-slate-100 bg-slate-50 px-2 py-1.5 text-center text-[10px] text-slate-500">{{ $k }}</div>
                                    @endforeach
                                </div>
                            </div>
                            <div class="rounded-lg border border-slate-200 bg-white px-3 py-2">
                                <div class="mb-1 text-[10px] text-x2-primary">④ Filter bar: select inline · tìm kiếm · Bộ lọc nâng cao (badge) · Cột</div>
                                <div class="flex flex-wrap gap-1.5">
                                    @foreach (['Tòa','Tầng','Loại căn','Trạng thái','Chủ thể'] as $k)
                                        <span class="rounded border border-slate-200 px-2 py-1 text-slate-500">{{ $k }} ▾</span>
                                    @endforeach
                                    <span class="ml-auto rounded border border-slate-200 px-2 py-1 text-slate-500">🔍 Tìm…</span>
                                    <span class="rounded border border-slate-200 px-2 py-1 text-slate-500">Nâng cao <b class="text-x2-primary">2</b></span>
                                    <span class="rounded border border-slate-200 px-2 py-1 text-slate-500">Cột</span>
                                </div>
                                <div class="mt-1.5 flex gap-1.5 text-[10px]"><span class="rounded-full bg-x2-primary/10 px-2 py-0.5 text-x2-primary">Tòa: A ✕</span><span class="rounded-full bg-x2-primary/10 px-2 py-0.5 text-x2-primary">Trạng thái: Đã ở ✕</span><span class="text-x2-primary">Xóa tất cả</span></div>
                            </div>
                            <div class="rounded-lg border border-slate-200 bg-white px-3 py-2">
                                <div class="mb-1 text-[10px] text-x2-primary">⑤ Bảng: hyperlink · FREEZE cột chọn+Mã (trái) &amp; thao tác (phải) · row action + More</div>
                                <div class="grid grid-cols-6 gap-px overflow-hidden rounded bg-slate-200 text-[10px]">
                                    <div class="bg-slate-100 px-1.5 py-1 font-semibold text-slate-600">☐ Mã</div>
                                    <div class="bg-white px-1.5 py-1 text-slate-500">Tòa</div>
                                    <div class="bg-white px-1.5 py-1 text-slate-500">Trạng thái</div>
                                    <div class="bg-white px-1.5 py-1 text-slate-500">Chủ thể</div>
                                    <div class="bg-white px-1.5 py-1 text-slate-500">Công nợ</div>
                                    <div class="bg-slate-100 px-1.5 py-1 text-right font-semibold text-slate-600">Thao tác</div>
                                </div>
                            </div>
                            <div class="rounded-lg border border-slate-200 bg-white px-3 py-2">
                                <div class="flex items-center justify-between text-[10px] text-slate-500">
                                    <span>⑥ <b>1 – 10 của 1.314 dòng</b></span>
                                    <span class="flex items-center gap-1"><span class="rounded bg-x2-primary px-1.5 py-0.5 text-white">1</span><span>2</span><span>3</span> · <span>10 / trang ▾</span></span>
                                </div>
                            </div>
                        </div>
                    </x-x2.card.info>
                </div>
                <x-x2.card.info title="6 vùng bắt buộc" icon="heroicon-o-check-badge">
                    @foreach ([
                        ['① Header', 'Breadcrumb có icon (2 cấp link + trang hiện tại). Title trang ẩn ở topbar theo lớp density.'],
                        ['② Action bar', 'Canh phải, cùng hàng breadcrumb. Thứ tự Chính→Phụ→Hỗ trợ→Khác. >5 action gom vào "···".'],
                        ['③ KPI', '5 card compact, số liệu tính THEO bộ lọc hiện tại (luôn khớp bảng).'],
                        ['④ Bộ lọc', 'Select inline + tìm kiếm + chip đang bật + drawer nâng cao (badge đếm) + ẩn/hiện cột.'],
                        ['⑤ Bảng', 'Hyperlink cột khóa, freeze cột chọn+Mã & cột thao tác, row action + More, bulk bar khi chọn.'],
                        ['⑥ Phân trang', 'Bộ đếm "X–Y của Z" + số trang + chọn số dòng/trang (mặc định 10).'],
                    ] as [$n, $d])
                        <div class="flex gap-2 border-b border-slate-50 py-2">
                            <span class="mt-1 h-2 w-2 shrink-0 rounded-full bg-x2-primary"></span>
                            <div><div class="text-sm font-semibold text-slate-700">{{ $n }}</div><div class="text-xs text-slate-500">{{ $d }}</div></div>
                        </div>
                    @endforeach
                </x-x2.card.info>
            </div>
        </div>

        {{-- ============ 02 · HEADER & ACTION BAR ============ --}}
        <div x-show="tab === 'actionbar'" x-cloak>
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                <x-x2.card.info title="Breadcrumb (header)" icon="heroicon-o-bars-3-bottom-left">
                    <div class="flex items-center gap-1 text-sm text-slate-500">
                        @svg('heroicon-m-home','h-4 w-4 opacity-70') <span>Tổng quan</span>
                        <span class="text-slate-300">›</span>
                        @svg('heroicon-m-user-group','h-4 w-4 opacity-70') <span>Cư dân &amp; Căn hộ</span>
                        <span class="text-slate-300">›</span>
                        @svg('heroicon-m-home-modern','h-4 w-4 opacity-70') <b class="text-slate-700">Hồ sơ căn hộ</b>
                    </div>
                    <ul class="mt-3 space-y-1 text-xs text-slate-500">
                        <li>• Mỗi mục kèm icon; 2 mục đầu click được, mục cuối = trang hiện tại (không link).</li>
                        <li>• Title trang render bằng <code>getBreadcrumbs()</code>; topbar ẩn title mặc định để gọn.</li>
                    </ul>
                </x-x2.card.info>
                <x-x2.card.info title="Action bar — thứ tự & overflow" icon="heroicon-o-rectangle-group">
                    <div class="flex flex-wrap items-center gap-2">
                        <x-x2.btn size="sm" icon="heroicon-m-arrow-up-tray">Nhập dữ liệu</x-x2.btn>
                        <x-x2.btn size="sm" icon="heroicon-m-arrow-down-tray">Xuất dữ liệu</x-x2.btn>
                        <x-x2.btn size="sm" variant="gold" icon="heroicon-m-plus">Thêm căn hộ</x-x2.btn>
                        <span class="grid h-8 w-8 place-items-center rounded-lg border border-slate-200 text-slate-400">@svg('heroicon-m-ellipsis-horizontal','h-4 w-4')</span>
                    </div>
                    <ul class="mt-3 space-y-1.5 text-xs text-slate-600">
                        <li class="flex gap-1.5">@svg('heroicon-s-check-circle','h-4 w-4 text-x2-green') Tối đa 3–5 action trực tiếp; CTA (gold) đặt cuối/phải.</li>
                        <li class="flex gap-1.5">@svg('heroicon-s-check-circle','h-4 w-4 text-x2-green') Nhiều hơn hoặc màn hẹp (≤1023px) → gom "···".</li>
                        <li class="flex gap-1.5">@svg('heroicon-s-check-circle','h-4 w-4 text-x2-green') Bulk action KHÔNG để trên action bar — nằm ở bulk bar của bảng.</li>
                    </ul>
                    <p class="mt-3 rounded-lg bg-x2-primary/5 px-3 py-2 text-xs text-slate-500">Xem chi tiết cấp bậc nút ở <b>DS-03 · Button · Action</b>.</p>
                </x-x2.card.info>
            </div>
        </div>

        {{-- ============ 03 · KPI ============ --}}
        <div x-show="tab === 'kpi'" x-cloak>
            <x-x2.card.info title="KPI cập nhật theo bộ lọc" icon="heroicon-o-chart-bar">
                <div class="mb-3 flex items-start gap-2 rounded-lg border border-x2-amber/30 bg-x2-amber/5 px-3 py-2 text-xs text-slate-600">
                    @svg('heroicon-o-exclamation-triangle','h-4 w-4 shrink-0 text-x2-amber')
                    <p><b>Chốt owner 2026-07-17:</b> KPI phản ánh KẾT QUẢ LỌC hiện tại, không còn "bất biến theo ngữ cảnh". Số trên thẻ phải luôn khớp tổng bảng bên dưới.</p>
                </div>
                <x-x2.kpi-row :cols="5">
                    <x-x2.card.kpi label="Tổng căn" value="1.314" sub="100% kết quả lọc" accent="blue" icon="heroicon-o-building-office-2" />
                    <x-x2.card.kpi label="Đã ở" value="1.190" sub="90,6%" accent="green" icon="heroicon-o-user-group" />
                    <x-x2.card.kpi label="Trống" value="84" sub="6,4%" accent="teal" icon="heroicon-o-home" />
                    <x-x2.card.kpi label="Đang duyệt gắn" value="27" sub="2,1%" accent="amber" icon="heroicon-o-clock" />
                    <x-x2.card.kpi label="Nợ phí" value="213" sub="16,2% căn có nợ" accent="red" icon="heroicon-o-banknotes" />
                </x-x2.kpi-row>
                <ul class="mt-4 space-y-1.5 text-xs text-slate-600">
                    <li class="flex gap-1.5">@svg('heroicon-s-check-circle','h-4 w-4 text-x2-green') KPI + bảng + export dùng CHUNG một hàm query đã áp filter (vd <code>filteredQuery()</code>).</li>
                    <li class="flex gap-1.5">@svg('heroicon-s-check-circle','h-4 w-4 text-x2-green') Gộp breakdown bằng 1 query <code>groupBy(status)</code>, tránh nhiều lần <code>count()</code>.</li>
                    <li class="flex gap-1.5">@svg('heroicon-s-check-circle','h-4 w-4 text-x2-green') Card đầu để tổng = 100% kết quả lọc; các card sau kèm % trên tổng.</li>
                </ul>
            </x-x2.card.info>
        </div>

        {{-- ============ 04 · FILTER & COLUMNS ============ --}}
        <div x-show="tab === 'filter'" x-cloak>
            <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
                <div class="space-y-6 xl:col-span-2">
                    <x-x2.card.info title="Filter bar + Chip" icon="heroicon-o-funnel">
                        <div class="flex flex-wrap items-center gap-2 rounded-xl border border-slate-200 p-2">
                            @foreach (['Tất cả tòa','Tất cả tầng','Tất cả loại căn','Tất cả trạng thái','Chủ thể'] as $s)
                                <span class="inline-flex h-9 items-center rounded-lg border border-slate-200 bg-white px-3 text-sm text-slate-500">{{ $s }} ▾</span>
                            @endforeach
                            <span class="ml-auto inline-flex h-9 items-center rounded-lg border border-slate-200 bg-white px-3 text-sm text-slate-400">🔍 Tìm mã căn, chủ sở hữu, SĐT…</span>
                            <span class="inline-flex h-9 items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 text-sm text-slate-600">Bộ lọc nâng cao <span class="grid h-5 min-w-5 place-items-center rounded-full bg-x2-primary px-1 text-[11px] font-bold text-white">2</span></span>
                            <span class="inline-flex h-9 items-center rounded-lg border border-slate-200 bg-white px-3 text-sm text-slate-600">Cột</span>
                        </div>
                        <div class="mt-2 flex flex-wrap items-center gap-2">
                            <span class="inline-flex items-center gap-1 rounded-full bg-x2-primary/10 px-2.5 py-1 text-xs text-x2-primary">Tòa: A <span class="cursor-pointer">✕</span></span>
                            <span class="inline-flex items-center gap-1 rounded-full bg-x2-primary/10 px-2.5 py-1 text-xs text-x2-primary">Trạng thái: Đã ở <span class="cursor-pointer">✕</span></span>
                            <span class="text-xs font-semibold text-x2-primary">Xóa tất cả</span>
                        </div>
                        <ul class="mt-3 space-y-1 text-xs text-slate-500">
                            <li>• Filter là state Livewire (<code>$f*</code>) — tác động THẲNG query/KPI/pagination/export, không dùng panel filter mặc định của Filament.</li>
                            <li>• Đổi bất kỳ filter nào: về trang 1 + <code>flushCachedTableRecords()</code> (nếu thiếu, bảng còn hiện kết quả cũ).</li>
                        </ul>
                    </x-x2.card.info>
                    <x-x2.card.info title="Drawer bộ lọc nâng cao" icon="heroicon-o-adjustments-horizontal">
                        <div class="max-w-sm rounded-xl border border-slate-200">
                            <div class="flex items-center justify-between border-b border-slate-200 px-4 py-3"><span class="font-title text-sm font-bold text-x2-primary">Bộ lọc nâng cao</span><span class="text-slate-400">✕</span></div>
                            <div class="space-y-3 px-4 py-3 text-xs">
                                <div><div class="mb-1 font-semibold text-slate-500">Diện tích (m²)</div><div class="flex gap-2"><span class="h-8 flex-1 rounded-lg border border-slate-200 px-2 py-1.5 text-slate-400">Từ</span><span class="h-8 flex-1 rounded-lg border border-slate-200 px-2 py-1.5 text-slate-400">Đến</span></div></div>
                                <div><div class="mb-1 font-semibold text-slate-500">Công nợ</div><span class="block h-8 rounded-lg border border-slate-200 px-2 py-1.5 text-slate-400">Tất cả ▾</span></div>
                            </div>
                            <div class="flex items-center justify-between border-t border-slate-200 px-4 py-3 text-xs"><span class="font-medium text-slate-500">Đặt lại</span><span class="rounded-lg bg-x2-primary px-3 py-1.5 font-semibold text-white">Áp dụng</span></div>
                        </div>
                        <p class="mt-3 text-xs text-slate-500">Drawer trượt phải; nút mở có badge đếm số filter nâng cao đang bật; "Đặt lại" chỉ xóa cụm nâng cao.</p>
                    </x-x2.card.info>
                </div>
                <x-x2.card.info title="Ẩn/hiện cột" icon="heroicon-o-view-columns">
                    <div class="w-full max-w-xs rounded-xl border border-slate-200 p-3">
                        <div class="mb-2 flex items-center justify-between"><span class="text-xs font-semibold text-slate-500">Cột hiển thị</span><span class="text-xs font-medium text-x2-red">Đặt lại</span></div>
                        <div class="space-y-0.5 text-sm text-slate-700">
                            @foreach (['Mã căn','Tòa','Tầng','Diện tích','Loại căn','Trạng thái','Chủ thể','Số cư dân','Công nợ','Cập nhật'] as $c)
                                <label class="flex items-center gap-2 rounded-md px-1.5 py-1 hover:bg-slate-50"><input type="checkbox" checked class="rounded text-x2-primary" /> {{ $c }}</label>
                            @endforeach
                        </div>
                        <div class="mt-3 rounded-lg bg-x2-primary py-1.5 text-center text-sm font-semibold text-white">Áp dụng</div>
                    </div>
                    <p class="mt-3 text-xs text-slate-500">Mỗi cột có <code>->visible(fn () => $this->colShown('key'))</code>. Cột <b>Mã</b> nên luôn bật (khóa freeze).</p>
                </x-x2.card.info>
            </div>
        </div>

        {{-- ============ 05 · TABLE & FREEZE ============ --}}
        <div x-show="tab === 'table'" x-cloak>
            <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
                <div class="space-y-6 xl:col-span-2">
                    <x-x2.card.info title="Bảng — hyperlink + row action + More" icon="heroicon-o-table-cells">
                        <x-x2.table.data>
                            <x-slot:head><th class="px-4 py-2 w-8"><input type="checkbox" class="rounded text-x2-primary" /></th><th class="px-4 py-2">Mã căn</th><th class="px-4 py-2">Tòa</th><th class="px-4 py-2">Trạng thái</th><th class="px-4 py-2">Chủ thể</th><th class="px-4 py-2 text-right">Công nợ</th><th class="px-4 py-2 text-right">Thao tác</th></x-slot:head>
                            @foreach ([
                                ['A-1305','Tháp A','Đã ở','green','Nguyễn Văn Anh','0'],
                                ['A-1808','Tháp A','Chờ gắn cư dân','amber','—','1.500.000'],
                                ['B-0504','Tháp B','Trống','slate','—','0'],
                            ] as [$code,$toa,$st,$tone,$holder,$debt])
                                <tr class="hover:bg-slate-50">
                                    <td class="px-4 py-2.5"><input type="checkbox" class="rounded text-x2-primary" /></td>
                                    <td class="px-4 py-2.5 font-medium text-x2-primary">{{ $code }}</td>
                                    <td class="px-4 py-2.5"><x-x2.status-badge :label="$toa" tone="slate" /></td>
                                    <td class="px-4 py-2.5"><x-x2.status-badge :label="$st" :tone="$tone" /></td>
                                    <td class="px-4 py-2.5 {{ $holder === '—' ? 'text-slate-400' : 'text-x2-primary' }}">{{ $holder }}</td>
                                    <td class="px-4 py-2.5 text-right {{ $debt === '0' ? 'text-emerald-600' : 'text-x2-red' }}">{{ $debt }}</td>
                                    <td class="px-4 py-2.5"><div class="flex justify-end gap-1 text-slate-400">@svg('heroicon-m-eye','h-5 w-5')@svg('heroicon-m-pencil-square','h-5 w-5')@svg('heroicon-m-ellipsis-vertical','h-5 w-5')</div></td>
                                </tr>
                            @endforeach
                        </x-x2.table.data>
                        <ul class="mt-3 space-y-1 text-xs text-slate-500">
                            <li>• Cột khóa (Mã căn, Chủ thể) là <b>hyperlink</b> màu <code>primary</code> → hồ sơ chi tiết.</li>
                            <li>• Row action: 2–3 icon-only hay dùng (Xem, Sửa) + <b>More (···)</b> cho hành động hiếm. Xem thứ tự ở DS-03 · Row Actions.</li>
                            <li>• Chọn ≥1 dòng → hiện <b>Bulk action bar</b> (sticky) với số bản ghi đang chọn (DS-03 · Bulk Action Bar).</li>
                            <li>• <code>->query(fn () => …)</code> dạng closure (KHÔNG Builder tĩnh) để đọc đúng filter mỗi lần lấy records.</li>
                            <li>• Mặc định <code>-></code> sort theo cột Mã; luôn khai empty/forbidden state.</li>
                        </ul>
                    </x-x2.card.info>
                </div>
                <x-x2.card.info title="Freeze cột (sticky)" icon="heroicon-o-lock-closed">
                    <div class="rounded-xl border border-slate-200 bg-slate-50/60 p-3 text-xs">
                        <div class="mb-2 font-semibold text-slate-600">Khi bảng cuộn ngang, cố định:</div>
                        <div class="flex items-center gap-1 overflow-hidden rounded border border-slate-200 bg-white text-[10px]">
                            <div class="bg-x2-primary/10 px-2 py-2 font-semibold text-x2-primary">☐</div>
                            <div class="bg-x2-primary/10 px-2 py-2 font-semibold text-x2-primary">Mã</div>
                            <div class="flex-1 px-2 py-2 text-center text-slate-300">… cuộn ngang …</div>
                            <div class="bg-x2-primary/10 px-2 py-2 font-semibold text-x2-primary">Thao tác</div>
                        </div>
                    </div>
                    <div class="mt-3 text-xs text-slate-600">
                        <div class="mb-1 font-semibold text-slate-700">Cơ chế — CSS scoped <code>.x2-bql-page</code></div>
                        <ul class="space-y-1 text-slate-500">
                            <li>• Wrapper blade <b>phải</b> bọc <code>&lt;div class="x2-bql-page"&gt;</code>.</li>
                            <li>• Cột đầu tiên phải là <code>TextColumn::make('code')</code> → sinh class <code>.fi-ta-cell-code</code> (sticky trái, sau ô chọn 3rem).</li>
                            <li>• Ô chọn <code>.fi-ta-selection-cell</code> sticky <code>left:0</code>.</li>
                            <li>• Cột cuối (thao tác) sticky <code>right:0</code>; header cột freeze nâng z-index nổi trên.</li>
                            <li>• Đổ bóng 2 mép để tách phần cuộn; nền trắng đặc chống lộ chữ.</li>
                        </ul>
                        <p class="mt-2 rounded-lg bg-x2-primary/5 px-2.5 py-1.5 text-slate-500">Không cần viết CSS mới cho trang list mới — chỉ cần đúng 2 điều kiện: <b>wrapper</b> + <b>cột tên <code>code</code></b>.</p>
                    </div>
                </x-x2.card.info>
            </div>
        </div>

        {{-- ============ 06 · PAGINATION ============ --}}
        <div x-show="tab === 'pagination'" x-cloak>
            <x-x2.card.info title="Chân bảng — bộ đếm · số dòng/trang · số trang" icon="heroicon-o-numbered-list">
                <div class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-slate-200 px-4 py-3 text-sm">
                    <span class="text-slate-500"><b class="text-slate-700">1 – 10</b> của <b class="text-slate-700">1.314</b> dòng</span>
                    <div class="flex items-center gap-3">
                        <span class="inline-flex items-center gap-1 text-slate-500">Mỗi trang <span class="rounded-lg border border-slate-200 px-2 py-1">10 ▾</span></span>
                        <span class="flex items-center gap-1">
                            <span class="grid h-7 w-7 place-items-center rounded-lg bg-x2-primary text-xs font-semibold text-white">1</span>
                            <span class="grid h-7 w-7 place-items-center rounded-lg text-xs text-slate-500">2</span>
                            <span class="grid h-7 w-7 place-items-center rounded-lg text-xs text-slate-500">3</span>
                            <span class="text-slate-400">…</span>
                            <span class="grid h-7 w-7 place-items-center rounded-lg text-xs text-slate-500">132</span>
                        </span>
                    </div>
                </div>
                <ul class="mt-4 space-y-1.5 text-xs text-slate-600">
                    <li class="flex gap-1.5">@svg('heroicon-s-check-circle','h-4 w-4 text-x2-green') Mặc định <b>10 dòng/trang</b>; tùy chọn <code>[10, 25, 50, 100]</code>.</li>
                    <li class="flex gap-1.5">@svg('heroicon-s-check-circle','h-4 w-4 text-x2-green') Bộ đếm + số trang + chọn số dòng do Filament <code>.fi-pagination</code> render — giữ hiển thị cả trên mobile.</li>
                    <li class="flex gap-1.5">@svg('heroicon-s-check-circle','h-4 w-4 text-x2-green') Đổi filter phải reset về trang 1.</li>
                </ul>
            </x-x2.card.info>
        </div>

        {{-- ============ 07 · CONTRACT & CHECKLIST ============ --}}
        <div x-show="tab === 'contract'" x-cloak>
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                <x-x2.card.info title="Contract kỹ thuật (bất biến)" icon="heroicon-o-shield-check">
                    <ul class="space-y-2 text-xs text-slate-600">
                        @foreach ([
                            'Wrapper blade bọc .x2-bql-page (freeze + density + mobile card).',
                            'Query bảng dạng closure ->query(fn () => $this->tableQuery()); KHÔNG Builder tĩnh.',
                            'Đổi filter: resetPage() + flushCachedTableRecords().',
                            'KPI + bảng + export dùng chung một filteredQuery() → KPI ăn theo bộ lọc.',
                            'Cột đầu tên code (hyperlink → chi tiết) để nhận freeze; cột cuối là recordActions.',
                            'Phân trang paginated([10,25,50,100]); mặc định 10.',
                            'Scope theo CurrentContext (buildingIds) — KHÔNG bỏ tenant/context scope.',
                            'Có empty state + forbidden state; số/tiền định dạng VN (số nguyên đồng).',
                        ] as $c)
                            <li class="flex gap-2">@svg('heroicon-s-check-circle','h-4 w-4 shrink-0 text-x2-green') {{ $c }}</li>
                        @endforeach
                    </ul>
                    <p class="mt-3 rounded-lg bg-x2-primary/5 px-3 py-2 text-xs text-slate-500">Dựng trang list mới bằng skill <code>x2bms-admin-listing-page</code> (LISTING_PAGE_STANDARD). Bản mẫu: <code>ResidentDirectory</code>, <code>ApartmentDirectory</code>.</p>
                </x-x2.card.info>
                <x-x2.card.info title="Checklist nghiệm thu trang list" icon="heroicon-o-clipboard-document-check">
                    @foreach ([
                        ['Header', 'Breadcrumb icon 2 cấp + trang hiện tại; action bar canh phải đúng thứ bậc.'],
                        ['KPI', '5 card, số khớp tổng bảng khi đổi bộ lọc.'],
                        ['Bộ lọc', 'Inline + tìm kiếm + chip + Xóa tất cả + drawer nâng cao (badge) + ẩn/hiện cột.'],
                        ['Bảng', 'Hyperlink cột khóa; freeze cột chọn+Mã & thao tác; row action + More; bulk bar khi chọn.'],
                        ['Phân trang', 'Bộ đếm + 10/trang + số trang; reset trang khi lọc.'],
                        ['Trạng thái', 'Empty + forbidden + loading; mobile chuyển card list.'],
                    ] as [$area, $d])
                        <div class="flex gap-2 border-b border-slate-50 py-2">
                            <span class="mt-0.5 grid h-5 w-5 shrink-0 place-items-center rounded border border-slate-300 text-[10px] text-slate-400">✓</span>
                            <div><div class="text-sm font-semibold text-slate-700">{{ $area }}</div><div class="text-xs text-slate-500">{{ $d }}</div></div>
                        </div>
                    @endforeach
                </x-x2.card.info>
            </div>
        </div>
    </div>
</x-filament-panels::page>
