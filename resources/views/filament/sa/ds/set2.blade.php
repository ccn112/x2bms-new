<x-filament-panels::page>
    @php
        $tabs = [
            'type' => 'Typography',
            'titles' => 'Phân cấp tiêu đề',
            'colors' => 'Token màu',
            'status' => 'Màu ngữ nghĩa',
            'icon' => 'Icon',
            'spacing' => 'Spacing',
            'density' => 'Mật độ',
            'radius' => 'Radius & Shadow',
            'a11y' => 'Accessibility',
            'showcase' => 'Showcase',
        ];
    @endphp

    <div x-data="{ tab: 'type' }">
        <div class="mb-6 flex flex-wrap items-center gap-1 overflow-x-auto border-b border-slate-200">
            @foreach ($tabs as $key => $label)
                <button type="button" @click="tab='{{ $key }}'"
                    class="font-title whitespace-nowrap border-b-2 px-3.5 py-2.5 text-[15px] font-semibold transition"
                    :class="tab === '{{ $key }}' ? 'border-x2-primary text-x2-primary' : 'border-transparent text-slate-500 hover:text-slate-800'">
                    {{ $label }}
                </button>
            @endforeach
        </div>

        {{-- ============ 01 · TYPOGRAPHY ============ --}}
        <div x-show="tab === 'type'">
            <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
                <x-x2.card.info title="Thang bậc kiểu chữ" icon="heroicon-o-language" class="xl:col-span-2">
                    @php
                        $scale = [
                            ['Page Title', '28 / 700', 'Plus Jakarta', 'font-title text-[28px] font-bold', 'Tiêu đề trang'],
                            ['Section Title', '22 / 700', 'Plus Jakarta', 'font-title text-[22px] font-bold', 'Tiêu đề section'],
                            ['Card Title', '18 / 700', 'Plus Jakarta', 'font-title text-[18px] font-bold', 'Tiêu đề thẻ'],
                            ['Body', '14 / 500', 'Inter', 'text-sm font-medium', 'Nội dung chính'],
                            ['Body Strong', '14 / 600', 'Inter', 'text-sm font-semibold', 'Nhấn mạnh'],
                            ['Table Header', '13 / 600', 'Inter', 'text-[13px] font-semibold', 'Header bảng'],
                            ['Table Text', '13 / 400', 'Inter', 'text-[13px]', 'Nội dung bảng'],
                            ['Label', '12 / 600', 'Inter', 'text-xs font-semibold uppercase', 'Nhãn form, tag'],
                            ['Caption', '12 / 400', 'Inter', 'text-xs', 'Chú thích'],
                        ];
                    @endphp
                    <div class="divide-y divide-slate-100">
                        @foreach ($scale as [$name, $spec, $font, $cls, $use])
                            <div class="flex items-center gap-4 py-2.5">
                                <div class="w-28 shrink-0"><div class="text-sm font-semibold text-slate-700">{{ $name }}</div><div class="text-[11px] text-slate-400">{{ $spec }} · {{ $font }}</div></div>
                                <div class="{{ $cls }} flex-1 text-slate-900">Tiêu đề trang</div>
                                <div class="hidden w-32 shrink-0 text-xs text-slate-400 sm:block">{{ $use }}</div>
                            </div>
                        @endforeach
                        <div class="flex items-center gap-4 py-2.5">
                            <div class="w-28 shrink-0"><div class="text-sm font-semibold text-slate-700">KPI Number</div><div class="text-[11px] text-slate-400">32 / 700 · Plus Jakarta</div></div>
                            <div class="font-title flex-1 text-[32px] font-bold text-x2-primary">1.269</div>
                            <div class="hidden w-32 shrink-0 text-xs text-slate-400 sm:block">Số liệu KPI</div>
                        </div>
                    </div>
                </x-x2.card.info>

                <x-x2.card.info title="Ứng dụng trong giao diện" icon="heroicon-o-eye">
                    <p class="mb-3 text-xs text-slate-500">Ví dụ hàng trong danh sách cư dân:</p>
                    <div class="rounded-xl border border-slate-200 p-3">
                        <div class="flex items-center gap-3">
                            <span class="grid h-9 w-9 place-items-center rounded-full bg-x2-primary/10 text-xs font-bold text-x2-primary">NH</span>
                            <div><div class="text-sm font-semibold text-slate-800">Nguyễn Hoàng Minh</div><div class="text-xs text-slate-500">minh.nguyen@gmail.com</div></div>
                            <x-x2.status-badge label="Hoạt động" tone="green" class="ml-auto" />
                        </div>
                    </div>
                    <div class="mt-4 grid grid-cols-4 gap-3 text-center">
                        @foreach (['28/700' => 34, '20/700' => 26, '16/700' => 20, '14/400' => 16] as $spec => $px)
                            <div><div class="font-title font-bold text-slate-900" style="font-size: {{ $px }}px; line-height: 1">Ag</div><div class="mt-1 text-[10px] text-slate-400">{{ $spec }}</div></div>
                        @endforeach
                    </div>
                </x-x2.card.info>
            </div>
        </div>

        {{-- ============ 02 · TITLE HIERARCHY ============ --}}
        <div x-show="tab === 'titles'" x-cloak>
            <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
                <x-x2.card.info title="Ví dụ phân cấp tiêu đề" icon="heroicon-o-bars-3-bottom-left">
                    <ol class="space-y-3">
                        @foreach ([
                            ['1', 'Header title (Page title)', 'Chỉ dùng ở thanh header trên cùng.', 'H1'],
                            ['2', 'Page tab title', 'Dùng cho tab điều hướng chính (Semibold).', 'Semibold'],
                            ['3', 'Section title', 'Chia các khối lớn trên trang.', 'H2'],
                            ['4', 'Card title', 'Tiêu đề card & panel.', 'H3'],
                            ['5', 'Form section title', 'Tiêu đề trong form & nhóm trường.', 'H4'],
                            ['6', 'Drawer title', 'Tiêu đề side drawer.', 'H4'],
                            ['7', 'Modal title', 'Tiêu đề modal & dialog.', 'H4'],
                        ] as [$n, $title, $desc, $tag])
                            <li class="flex items-start gap-3">
                                <span class="grid h-6 w-6 shrink-0 place-items-center rounded-full bg-x2-primary text-[11px] font-bold text-white">{{ $n }}</span>
                                <div class="flex-1"><div class="text-sm font-semibold text-slate-800">{{ $title }}</div><div class="text-xs text-slate-500">{{ $desc }}</div></div>
                                <span class="rounded bg-slate-100 px-1.5 py-0.5 text-[11px] font-semibold text-slate-500">{{ $tag }}</span>
                            </li>
                        @endforeach
                    </ol>
                </x-x2.card.info>

                <x-x2.card.info title="Quy tắc đã duyệt" icon="heroicon-o-check-badge">
                    <ul class="space-y-2 text-sm text-slate-600">
                        <li class="flex gap-2"><span class="text-x2-gold">●</span> Chỉ dùng page title ở header trên cùng (H1).</li>
                        <li class="flex gap-2"><span class="text-x2-gold">●</span> Page tab title là Semibold, lớn hơn body một chút.</li>
                        <li class="flex gap-2"><span class="text-x2-gold">●</span> Card title dùng Plus Jakarta Sans.</li>
                        <li class="flex gap-2"><span class="text-x2-gold">●</span> Body, form label, table content dùng Inter.</li>
                        <li class="flex gap-2"><span class="text-x2-gold">●</span> Giữ phân cấp rõ ràng và đủ tương phản.</li>
                    </ul>
                    <div class="mt-4 rounded-xl border border-slate-200 p-3">
                        <div class="font-title text-lg font-bold text-slate-900">Tiêu đề trang (H1)</div>
                        <div class="mt-1 font-title text-base font-bold text-slate-800">Tiêu đề section (H2)</div>
                        <div class="mt-1 font-title text-sm font-bold text-slate-700">Tiêu đề thẻ (H3)</div>
                        <p class="mt-1 text-sm text-slate-500">Nội dung thân bài dùng Inter 14.</p>
                    </div>
                </x-x2.card.info>
            </div>

            {{-- Ví dụ minh hoạ trên màn thật — đánh số 1–7 vị trí tiêu đề (bám thiết kế) --}}
            <div class="mt-6">
                <x-x2.card.info title="Ứng dụng phân cấp tiêu đề trên màn thật" icon="heroicon-o-computer-desktop">
                    @php $b = fn ($n) => '<span class="mr-1.5 inline-grid h-5 w-5 place-items-center rounded-full bg-x2-primary text-[11px] font-bold text-white align-middle">'.$n.'</span>'; @endphp
                    <div class="overflow-hidden rounded-xl border border-slate-200">
                        {{-- (1) Header title --}}
                        <div class="flex items-center justify-between border-b border-slate-100 bg-white px-4 py-3">
                            <div>{!! $b(1) !!}<span class="font-title text-lg font-bold text-slate-900">Cư dân</span> <span class="text-[10px] text-slate-400">— Header title (H1)</span></div>
                            <div class="flex gap-1.5"><x-x2.btn size="sm">Nhập</x-x2.btn><x-x2.btn size="sm" variant="gold">+ Thêm mới</x-x2.btn></div>
                        </div>
                        <div class="grid grid-cols-1 lg:grid-cols-3">
                            <div class="lg:col-span-2 border-r border-slate-100 p-4">
                                {{-- (2) Page tab title --}}
                                <div class="mb-3 flex items-center gap-3 border-b border-slate-100 pb-2 text-sm">
                                    {!! $b(2) !!}<span class="font-title font-bold text-x2-primary">Tổng quan</span><span class="text-slate-400">Hợp đồng</span><span class="text-slate-400">Xe & thẻ</span>
                                    <span class="text-[10px] text-slate-400">— Page tab title (Semibold)</span>
                                </div>
                                {{-- (3) Section title --}}
                                <div class="mb-2 text-sm">{!! $b(3) !!}<span class="font-title font-bold text-slate-800">Lọc cư dân</span> <span class="text-[10px] text-slate-400">— Section title (H2)</span></div>
                                {{-- (4) Card title --}}
                                <div class="rounded-lg border border-slate-200 p-3">
                                    <div class="mb-2 text-sm">{!! $b(4) !!}<span class="font-title font-bold text-slate-800">Danh sách cư dân</span> <span class="text-[10px] text-slate-400">— Card title (H3)</span></div>
                                    {{-- (5) Form section title --}}
                                    <div class="text-xs">{!! $b(5) !!}<span class="font-semibold text-slate-700">Thông tin cá nhân</span> <span class="text-[10px] text-slate-400">— Form section title (H4)</span></div>
                                    <div class="mt-2 grid grid-cols-2 gap-2"><input class="rounded-lg border-slate-200 text-xs" placeholder="Họ và tên" /><input class="rounded-lg border-slate-200 text-xs" placeholder="Số điện thoại" /></div>
                                </div>
                            </div>
                            {{-- (6) Drawer title --}}
                            <div class="bg-slate-50/60 p-4">
                                <div class="text-sm">{!! $b(6) !!}<span class="font-title font-bold text-slate-800">Chi tiết cư dân</span> <span class="text-[10px] text-slate-400">— Drawer title (H4)</span></div>
                                <div class="mt-2 rounded-lg border border-slate-200 bg-white p-2 text-xs text-slate-500">Nguyễn Văn An · A12.06</div>
                                {{-- (7) Modal title --}}
                                <div class="mt-4 rounded-lg border border-slate-200 bg-white p-2 shadow-sm">
                                    <div class="text-sm">{!! $b(7) !!}<span class="font-title font-bold text-slate-800">Xác nhận đổi trạng thái</span> <span class="text-[10px] text-slate-400">— Modal title (H4)</span></div>
                                    <div class="mt-2 flex justify-end gap-2"><x-x2.btn size="sm">Hủy</x-x2.btn><x-x2.btn size="sm" variant="danger">Xác nhận</x-x2.btn></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <p class="mt-3 text-xs text-slate-400">Mỗi số ① → ⑦ tương ứng một cấp tiêu đề trong danh sách bên trên, đặt đúng vị trí trong màn nghiệp vụ thật.</p>
                </x-x2.card.info>
            </div>
        </div>

        {{-- ============ 03 · COLOR TOKENS ============ --}}
        <div x-show="tab === 'colors'" x-cloak>
            @php
                $groups = [
                    'Brand Navy' => [['Navy 900', '#0B1533', 'Sidebar bg, surface elevated'], ['Navy 800', '#0F1D3D', 'Sidebar hover, card header'], ['Navy 700', '#1A2B4D', 'Borders/muted text on dark']],
                    'Brand Gold' => [['Gold 500', '#D4A652', 'Accent, active menu, CTA'], ['Gold 400', '#E7BF6D', 'Hover/pressed, highlights']],
                    'Primary Blue' => [['Blue 600', '#2563EB', 'Primary actions, links, focus'], ['Blue 500', '#3B82F6', 'Hover state']],
                ];
                $neutrals = [['Gray 50', '#F8FAFC'], ['Gray 100', '#F1F5F9'], ['Gray 200', '#E2E8F0'], ['Gray 300', '#CBD5E1'], ['Gray 600', '#475569'], ['Gray 900', '#0F172A'], ['White', '#FFFFFF']];
                $semantic = [['Success 500', '#22C55E', 'Trạng thái tích cực'], ['Warning 500', '#F59E0B', 'Cảnh báo'], ['Danger 500', '#EF4444', 'Lỗi, nghiêm trọng'], ['Info 500', '#0EA5E9', 'Thông tin trung tính'], ['AI Purple 500', '#8B5CF6', 'Tính năng AI']];
            @endphp
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                @foreach ($groups as $g => $items)
                    <x-x2.card.info :title="$g" icon="heroicon-o-swatch">
                        <div class="space-y-3">
                            @foreach ($items as [$name, $hex, $use])
                                <div class="flex items-center gap-3">
                                    <span class="h-10 w-10 shrink-0 rounded-xl ring-1 ring-black/5" style="background: {{ $hex }}"></span>
                                    <div><div class="text-sm font-semibold text-slate-700">{{ $name }} <span class="text-[11px] uppercase text-slate-400">{{ $hex }}</span></div><div class="text-xs text-slate-500">{{ $use }}</div></div>
                                </div>
                            @endforeach
                        </div>
                    </x-x2.card.info>
                @endforeach
            </div>
            <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-2">
                <x-x2.card.info title="Neutrals" icon="heroicon-o-squares-2x2">
                    <div class="grid grid-cols-4 gap-3 sm:grid-cols-7">
                        @foreach ($neutrals as [$name, $hex])
                            <div><div class="h-10 w-full rounded-lg ring-1 ring-black/10" style="background: {{ $hex }}"></div><div class="mt-1 text-[11px] font-semibold text-slate-600">{{ $name }}</div><div class="text-[10px] uppercase text-slate-400">{{ $hex }}</div></div>
                        @endforeach
                    </div>
                </x-x2.card.info>
                <x-x2.card.info title="Semantic" icon="heroicon-o-signal">
                    <div class="grid grid-cols-3 gap-3 sm:grid-cols-5">
                        @foreach ($semantic as [$name, $hex, $use])
                            <div><div class="h-10 w-full rounded-lg ring-1 ring-black/5" style="background: {{ $hex }}"></div><div class="mt-1 text-[11px] font-semibold text-slate-600">{{ $name }}</div><div class="text-[10px] uppercase text-slate-400">{{ $hex }}</div></div>
                        @endforeach
                    </div>
                </x-x2.card.info>
            </div>
            <div class="mt-6">
                <x-x2.card.info title="Live UI preview" icon="heroicon-o-computer-desktop">
                    <div class="flex flex-wrap items-center gap-6">
                        <div class="flex items-center gap-2"><x-x2.btn size="sm" variant="primary">Primary</x-x2.btn><x-x2.btn size="sm" variant="gold">Secondary</x-x2.btn><x-x2.btn size="sm" variant="ghost">Ghost</x-x2.btn><x-x2.btn size="sm" :disabled="true">Disabled</x-x2.btn></div>
                        <div class="flex items-center gap-2"><x-x2.status-badge label="Hoạt động" tone="green" /><x-x2.status-badge label="Trung bình" tone="amber" /><x-x2.status-badge label="Đã khóa" tone="red" /><x-x2.status-badge label="Thông tin" tone="blue" /></div>
                    </div>
                </x-x2.card.info>
            </div>
        </div>

        {{-- ============ 04 · SEMANTIC STATUS ============ --}}
        <div x-show="tab === 'status'" x-cloak>
            <x-x2.card.info title="Tổng quan trạng thái" icon="heroicon-o-flag">
                <div class="flex flex-wrap gap-2">
                    <x-x2.status-badge label="Hoạt động" tone="green" />
                    <x-x2.status-badge label="Chờ duyệt" tone="amber" />
                    <x-x2.status-badge label="Tạm khóa" tone="slate" />
                    <x-x2.status-badge label="Quá hạn" tone="red" />
                    <x-x2.status-badge label="Đã đối soát" tone="teal" />
                    <x-x2.status-badge label="Cần xác minh" tone="blue" />
                    <span class="inline-flex items-center gap-1 rounded-full bg-x2-ai/10 px-2 py-0.5 text-[11px] font-medium text-x2-ai ring-1 ring-inset ring-x2-ai/20"><span class="h-1.5 w-1.5 rounded-full bg-current"></span>AI đề xuất</span>
                </div>
                <p class="mt-3 text-xs text-slate-500">Bộ màu trạng thái dùng nhất quán toàn hệ thống để truyền đạt trạng thái, ưu tiên và hành động cần thiết.</p>
            </x-x2.card.info>

            <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-2">
                <x-x2.card.info title="Alert banners" icon="heroicon-o-megaphone">
                    <div class="space-y-2 text-sm">
                        <div class="flex items-center gap-2 rounded-lg border border-x2-green/30 bg-x2-green/5 px-3 py-2 text-x2-green">@svg('heroicon-o-check-circle','h-4 w-4') Thành công! Dữ liệu đã được lưu và cập nhật.</div>
                        <div class="flex items-center gap-2 rounded-lg border border-x2-amber/30 bg-x2-amber/5 px-3 py-2 text-x2-amber">@svg('heroicon-o-exclamation-triangle','h-4 w-4') Cảnh báo: Một số thông tin cần kiểm tra.</div>
                        <div class="flex items-center gap-2 rounded-lg border border-x2-red/30 bg-x2-red/5 px-3 py-2 text-x2-red">@svg('heroicon-o-x-circle','h-4 w-4') Lỗi: Không thể hoàn tất yêu cầu.</div>
                        <div class="flex items-center gap-2 rounded-lg border border-x2-primary/30 bg-x2-primary/5 px-3 py-2 text-x2-primary">@svg('heroicon-o-information-circle','h-4 w-4') Thông tin: Đây là thông tin cần bạn lưu ý.</div>
                        <div class="flex items-center gap-2 rounded-lg border border-x2-ai/30 bg-x2-ai/5 px-3 py-2 text-x2-ai">@svg('heroicon-o-sparkles','h-4 w-4') Gợi ý (AI): Hệ thống đề xuất hành động tối ưu.</div>
                    </div>
                </x-x2.card.info>

                <x-x2.card.info title="Warning / Notice cards" icon="heroicon-o-bell-alert">
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <div class="rounded-xl border border-x2-amber/30 bg-x2-amber/5 p-3"><div class="flex items-center gap-1.5 text-x2-amber">@svg('heroicon-o-exclamation-triangle','h-4 w-4')<span class="text-sm font-semibold">Hợp đồng sắp hết hạn</span></div><p class="mt-1 text-xs text-slate-600">Căn hộ A-0205 sẽ hết hạn sau 7 ngày.</p></div>
                        <div class="rounded-xl border border-x2-primary/30 bg-x2-primary/5 p-3"><div class="flex items-center gap-1.5 text-x2-primary">@svg('heroicon-o-information-circle','h-4 w-4')<span class="text-sm font-semibold">Cần xác minh</span></div><p class="mt-1 text-xs text-slate-600">CMND của cư dân chưa thanh rõ nét.</p></div>
                        <div class="rounded-xl border border-x2-red/30 bg-x2-red/5 p-3"><div class="flex items-center gap-1.5 text-x2-red">@svg('heroicon-o-exclamation-circle','h-4 w-4')<span class="text-sm font-semibold">Công nợ quá hạn</span></div><p class="mt-1 text-xs text-slate-600">Căn hộ B-0504 có 2 khoản chưa thanh toán.</p></div>
                        <div class="rounded-xl border border-x2-green/30 bg-x2-green/5 p-3"><div class="flex items-center gap-1.5 text-x2-green">@svg('heroicon-o-check-circle','h-4 w-4')<span class="text-sm font-semibold">Đối soát thành công</span></div><p class="mt-1 text-xs text-slate-600">Đã đối soát 12 khoản thu kỳ 07/2026.</p></div>
                    </div>
                </x-x2.card.info>
            </div>

            <div class="mt-6">
                <x-x2.card.info title="Bảng đặc tả màu trạng thái" icon="heroicon-o-table-cells">
                    <x-x2.table.data>
                        <x-slot:head><th class="px-4 py-2">Trạng thái</th><th class="px-4 py-2">Background</th><th class="px-4 py-2">Text</th><th class="px-4 py-2">Border</th><th class="px-4 py-2">Hướng dẫn dùng</th></x-slot:head>
                        @foreach ([
                            ['Hoạt động', 'green', '#ECFDF5', '#047857', '#A7F3D0', 'Trạng thái tích cực, đang hoạt động.'],
                            ['Chờ duyệt', 'amber', '#FFFBEB', '#B45309', '#FDE68A', 'Đang chờ phê duyệt/xác nhận.'],
                            ['Tạm khóa', 'slate', '#F3F4F6', '#374151', '#E5E7EB', 'Tạm khóa/giới hạn, tạm ngưng.'],
                            ['Quá hạn', 'red', '#FEF2F2', '#B91C1C', '#FECACA', 'Trạng thái tiêu cực, rủi ro cao.'],
                            ['Đã đối soát', 'teal', '#F0FDFA', '#0F766E', '#99F6E4', 'Đã hoàn tất đối soát/khớp dữ liệu.'],
                            ['Cần xác minh', 'blue', '#EFF6FF', '#1D4ED8', '#BFDBFE', 'Cần kiểm tra/bổ sung.'],
                        ] as [$st, $tone, $bg, $tx, $bd, $use])
                            <tr class="hover:bg-slate-50">
                                <td class="px-4 py-2"><x-x2.status-badge :label="$st" :tone="$tone" /></td>
                                <td class="px-4 py-2 text-xs"><span class="inline-block h-3 w-3 rounded ring-1 ring-black/10 align-middle" style="background: {{ $bg }}"></span> {{ $bg }}</td>
                                <td class="px-4 py-2 text-xs"><span class="inline-block h-3 w-3 rounded align-middle" style="background: {{ $tx }}"></span> {{ $tx }}</td>
                                <td class="px-4 py-2 text-xs"><span class="inline-block h-3 w-3 rounded ring-1 align-middle" style="background: {{ $bd }}"></span> {{ $bd }}</td>
                                <td class="px-4 py-2 text-xs text-slate-500">{{ $use }}</td>
                            </tr>
                        @endforeach
                    </x-x2.table.data>
                </x-x2.card.info>
            </div>

            <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-2">
                <x-x2.card.info title="Mức độ công nợ (Debt severity)" icon="heroicon-o-banknotes">
                    <div class="flex flex-wrap gap-2">
                        <x-x2.status-badge label="Không nợ" tone="green" /><x-x2.status-badge label="Dưới 30 ngày" tone="teal" /><x-x2.status-badge label="30 – 60 ngày" tone="amber" /><x-x2.status-badge label="60 – 90 ngày" tone="amber" /><x-x2.status-badge label="Trên 90 ngày" tone="red" />
                    </div>
                </x-x2.card.info>
                <x-x2.card.info title="Mức độ khẩn (Bảo trì)" icon="heroicon-o-wrench-screwdriver">
                    <div class="flex flex-wrap gap-2">
                        <x-x2.status-badge label="Thấp" tone="green" /><x-x2.status-badge label="Trung bình" tone="amber" /><x-x2.status-badge label="Cao" tone="amber" /><x-x2.status-badge label="Khẩn cấp" tone="red" />
                    </div>
                </x-x2.card.info>
            </div>
        </div>

        {{-- ============ 05 · ICON SYSTEM ============ --}}
        <div x-show="tab === 'icon'" x-cloak>
            @php
                $iconGroups = [
                    'Điều hướng & sidebar' => ['heroicon-o-squares-2x2','heroicon-o-users','heroicon-o-home-modern','heroicon-o-building-office-2','heroicon-o-truck','heroicon-o-identification','heroicon-o-check-badge','heroicon-o-cog-6-tooth'],
                    'Header & hệ thống' => ['heroicon-o-magnifying-glass','heroicon-o-bell','heroicon-o-chat-bubble-left-right','heroicon-o-question-mark-circle','heroicon-o-user-circle','heroicon-o-building-library','heroicon-o-shield-check','heroicon-o-language'],
                    'Thao tác (Actions)' => ['heroicon-o-plus','heroicon-o-pencil-square','heroicon-o-trash','heroicon-o-eye','heroicon-o-arrow-down-tray','heroicon-o-arrow-up-tray','heroicon-o-document-duplicate','heroicon-o-funnel'],
                    'Trạng thái (Status)' => ['heroicon-o-check-circle','heroicon-o-pause-circle','heroicon-o-clock','heroicon-o-x-circle','heroicon-o-exclamation-triangle','heroicon-o-information-circle','heroicon-o-lock-closed'],
                    'Tài chính (Finance)' => ['heroicon-o-banknotes','heroicon-o-document-text','heroicon-o-credit-card','heroicon-o-arrow-uturn-left','heroicon-o-chart-bar','heroicon-o-building-library','heroicon-o-wallet'],
                    'Bảo mật (Security)' => ['heroicon-o-lock-closed','heroicon-o-lock-open','heroicon-o-user','heroicon-o-user-group','heroicon-o-clipboard-document-list','heroicon-o-shield-check'],
                    'Kỹ thuật / IOC' => ['heroicon-o-computer-desktop','heroicon-o-video-camera','heroicon-o-bolt','heroicon-o-fire','heroicon-o-wrench-screwdriver','heroicon-o-cpu-chip'],
                    'AI / Thông minh' => ['heroicon-o-sparkles','heroicon-o-chart-bar-square','heroicon-o-light-bulb','heroicon-o-cube-transparent','heroicon-o-bell-alert'],
                ];
            @endphp
            <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
                <div class="space-y-6 xl:col-span-2">
                    @foreach ($iconGroups as $g => $icons)
                        <x-x2.card.info :title="$g" icon="heroicon-o-cube">
                            <div class="flex flex-wrap gap-3">
                                @foreach ($icons as $ic)
                                    <span class="grid h-10 w-10 place-items-center rounded-lg border border-slate-200 text-slate-600">@svg($ic, 'h-5 w-5')</span>
                                @endforeach
                            </div>
                        </x-x2.card.info>
                    @endforeach
                </div>
                <x-x2.card.info title="Preview — dùng icon trong hệ thống" icon="heroicon-o-eye">
                    <div class="text-xs font-semibold text-slate-500">Action buttons</div>
                    <div class="mt-2 flex flex-wrap gap-2">
                        <x-x2.btn size="sm" variant="gold" icon="heroicon-m-plus">Thêm mới</x-x2.btn>
                        <x-x2.btn size="sm" icon="heroicon-m-pencil-square">Chỉnh sửa</x-x2.btn>
                        <x-x2.btn size="sm" variant="danger" icon="heroicon-m-trash">Xóa</x-x2.btn>
                    </div>
                    <div class="mt-4 text-xs font-semibold text-slate-500">Status indicators</div>
                    <div class="mt-2 flex flex-wrap gap-2"><x-x2.status-badge label="Hoạt động" tone="green" /><x-x2.status-badge label="Đang chờ" tone="amber" /><x-x2.status-badge label="Quá hạn" tone="red" /></div>
                    <div class="mt-4 text-xs font-semibold text-slate-500">Row actions</div>
                    <div class="mt-2 flex gap-2 text-slate-400">@svg('heroicon-m-eye','h-5 w-5')@svg('heroicon-m-pencil-square','h-5 w-5')@svg('heroicon-m-document-duplicate','h-5 w-5')@svg('heroicon-m-ellipsis-vertical','h-5 w-5')</div>
                    <p class="mt-4 text-xs text-slate-400">Ưu tiên outline; nav 20px · action 18px · inline 16px · nhấn mạnh 24px.</p>
                </x-x2.card.info>
            </div>
        </div>

        {{-- ============ 06 · SPACING ============ --}}
        <div x-show="tab === 'spacing'" x-cloak>
            <x-x2.card.info title="Thang khoảng cách (bội số 4px)" icon="heroicon-o-view-columns">
                <div class="flex flex-wrap items-end gap-4">
                    @foreach ([4, 8, 12, 16, 20, 24, 32, 40, 48] as $px)
                        <div class="text-center"><div class="flex h-14 items-end justify-center"><span class="rounded bg-x2-primary/70" style="width: {{ min($px, 48) }}px; height: {{ min($px, 48) }}px;"></span></div><div class="mt-1 text-xs font-semibold text-slate-700">{{ $px }}</div><div class="text-[11px] text-slate-400">{{ $px }}px</div></div>
                    @endforeach
                </div>
            </x-x2.card.info>
            <div class="mt-6">
                <x-x2.card.info title="Ứng dụng khoảng cách trong giao diện" icon="heroicon-o-rectangle-group">
                    <dl class="grid grid-cols-1 gap-x-8 gap-y-2 text-sm sm:grid-cols-2">
                        @foreach ([
                            'Padding trang' => '24px', 'Khoảng cách giữa section' => '24px',
                            'Padding trong card' => '20–24px', 'Khoảng cách trường form' => '16px',
                            'Khoảng cách hàng bảng' => '52–56px (dense 44–48)', 'Header → nội dung' => '16–24px',
                            'Padding trong sidebar' => '12–16px', 'Gap giữa các card' => '16–24px',
                        ] as $k => $v)
                            <div class="flex items-center justify-between border-b border-slate-50 pb-1.5"><dt class="text-slate-500">{{ $k }}</dt><dd class="font-semibold text-slate-800">{{ $v }}</dd></div>
                        @endforeach
                    </dl>
                    <p class="mt-3 rounded-lg bg-x2-primary/5 px-3 py-2 text-xs text-slate-500">Luôn dùng giá trị trong thang spacing để đảm bảo nhất quán và khả năng mở rộng.</p>
                </x-x2.card.info>
            </div>
        </div>

        {{-- ============ 07 · DENSITY ============ --}}
        <div x-show="tab === 'density'" x-cloak>
            <div class="mb-4 flex items-start gap-2 rounded-xl border border-x2-primary/20 bg-x2-primary/5 px-4 py-3 text-sm text-slate-600">
                @svg('heroicon-o-information-circle', 'h-5 w-5 text-x2-primary')
                <p><b>Default</b> là baseline khuyến nghị cho hầu hết trường hợp. Dùng <b>Comfortable</b> cho cảm ứng, <b>Compact</b> cho màn dày dữ liệu.</p>
            </div>
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                @foreach ([
                    ['Comfortable', 'Roomier', 'blue', '24px', '56px', '64px', 'Tốt cho thiết bị cảm ứng, mật độ thấp.'],
                    ['Default', 'Khuyến nghị', 'green', '16px', '48px', '56px', 'Cân bằng cho hầu hết ứng dụng desktop.'],
                    ['Compact', 'High density', 'amber', '12px', '40px', '48px', 'Khi màn hình hạn chế hoặc dữ liệu rất dày.'],
                ] as [$name, $tag, $tone, $pad, $filter, $row, $desc])
                    <x-x2.card.info :title="$name">
                        <x-slot:actions><x-x2.status-badge :label="$tag" :tone="$tone" /></x-slot:actions>
                        <dl class="space-y-1.5 text-sm">
                            <div class="flex justify-between"><dt class="text-slate-500">Card padding</dt><dd class="font-semibold text-slate-800">{{ $pad }}</dd></div>
                            <div class="flex justify-between"><dt class="text-slate-500">Filter bar height</dt><dd class="font-semibold text-slate-800">{{ $filter }}</dd></div>
                            <div class="flex justify-between"><dt class="text-slate-500">Row height</dt><dd class="font-semibold text-slate-800">{{ $row }}</dd></div>
                        </dl>
                        <p class="mt-3 border-t border-slate-100 pt-2 text-xs text-slate-500">{{ $desc }}</p>
                    </x-x2.card.info>
                @endforeach
            </div>
        </div>

        {{-- ============ 08 · RADIUS & SHADOW ============ --}}
        <div x-show="tab === 'radius'" x-cloak>
            <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
                <x-x2.card.info title="Thang bo góc (Border radius)" icon="heroicon-o-square-3-stack-3d">
                    <div class="space-y-3">
                        @foreach ([['xs', 2], ['sm', 4], ['md', 8], ['lg', 12], ['xl', 16]] as [$name, $px])
                            <div class="flex items-center gap-3"><span class="inline-block h-9 w-9 border-2 border-x2-primary/60 bg-x2-primary/5" style="border-radius: {{ $px }}px"></span><span class="text-sm text-slate-600"><b>{{ $name }}</b> · {{ $px }}px</span></div>
                        @endforeach
                    </div>
                </x-x2.card.info>
                <x-x2.card.info title="Nơi dùng radius" icon="heroicon-o-map-pin">
                    <ul class="space-y-2 text-sm text-slate-600">
                        <li><b>xs (2px)</b> — nút nhỏ, checkbox</li>
                        <li><b>sm (4px)</b> — input, dropdown, badge</li>
                        <li><b>md (8px)</b> — card, hàng bảng, modal</li>
                        <li><b>lg (12px)</b> — drawer, popover</li>
                        <li><b>xl (16px)</b> — container lớn, hero card</li>
                    </ul>
                    <p class="mt-3 text-xs text-slate-500">Radius nhất quán tạo cảm giác điềm tĩnh, hiện đại, tin cậy.</p>
                </x-x2.card.info>
                <x-x2.card.info title="Thang đổ bóng (Elevation)" icon="heroicon-o-sun">
                    <div class="space-y-2 text-xs">
                        @foreach ([
                            ['0', 'None', ''],
                            ['1', 'Card', '0 1px 2px rgba(16,24,40,.06)'],
                            ['2', 'Popover', '0 4px 12px rgba(16,24,40,.08)'],
                            ['3', 'Drawer', '0 8px 24px rgba(16,24,40,.12)'],
                            ['4', 'Modal', '0 16px 40px rgba(16,24,40,.16)'],
                            ['5', 'Max', '0 24px 64px rgba(16,24,40,.20)'],
                        ] as [$lv, $use, $shadow])
                            <div class="flex items-center gap-3">
                                <span class="grid h-9 w-9 shrink-0 place-items-center rounded-lg bg-white text-[11px] font-bold text-slate-500" @if($shadow) style="box-shadow: {{ $shadow }}" @else style="border:1px solid #E2E8F0" @endif>{{ $lv }}</span>
                                <span class="text-slate-600">Level {{ $lv }} · {{ $use }}</span>
                            </div>
                        @endforeach
                    </div>
                </x-x2.card.info>
            </div>
            <div class="mt-6">
                <x-x2.card.info title="Radius áp trong component" icon="heroicon-o-cube">
                    <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-6 text-center text-xs text-slate-500">
                        <div><div class="mx-auto mb-1 h-10 w-full bg-x2-primary/10" style="border-radius:4px"></div>Input · sm</div>
                        <div><div class="mx-auto mb-1 h-10 w-full bg-x2-primary/10" style="border-radius:4px"></div>Badge · sm</div>
                        <div><div class="mx-auto mb-1 h-10 w-full bg-x2-primary/10" style="border-radius:8px"></div>Card · md</div>
                        <div><div class="mx-auto mb-1 h-10 w-full bg-x2-primary/10" style="border-radius:8px"></div>Modal · md</div>
                        <div><div class="mx-auto mb-1 h-10 w-full bg-x2-primary/10" style="border-radius:12px"></div>Drawer · lg</div>
                        <div><div class="mx-auto mb-1 h-10 w-full bg-x2-primary/10" style="border-radius:16px"></div>Hero · xl</div>
                    </div>
                    <ul class="mt-4 space-y-1 text-xs text-slate-500">
                        <li>• Dùng bóng nhẹ để thể hiện độ nổi; tăng level theo mức quan trọng.</li>
                        <li>• Không dùng quá level 5; giữ bóng mềm để hiện đại, sạch sẽ.</li>
                    </ul>
                </x-x2.card.info>
            </div>
        </div>

        {{-- ============ 09 · ACCESSIBILITY & STATES ============ --}}
        <div x-show="tab === 'a11y'" x-cloak>
            <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
                <div class="space-y-6 xl:col-span-2">
                    <x-x2.card.info title="Trạng thái tương tác" icon="heroicon-o-cursor-arrow-ripple">
                        <div class="space-y-4">
                            <div><div class="mb-1.5 text-xs font-semibold text-slate-500">Focus (bàn phím)</div>
                                <div class="flex flex-wrap gap-2">
                                    <x-x2.btn size="sm" variant="primary" class="ring-2 ring-x2-primary/40 ring-offset-2">Primary button</x-x2.btn>
                                    <x-x2.btn size="sm" class="ring-2 ring-x2-primary/40 ring-offset-2">Secondary button</x-x2.btn>
                                    <input class="rounded-xl border-x2-primary text-sm ring-2 ring-x2-primary/20" placeholder="Input field" />
                                </div>
                            </div>
                            <div><div class="mb-1.5 text-xs font-semibold text-slate-500">Hover</div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="rounded-lg bg-x2-primary-600 px-3 py-2 text-sm font-semibold text-white">Primary button</span>
                                    <span class="rounded-lg bg-x2-primary/10 px-4 py-2 text-sm">Row hover</span>
                                    <a href="#" class="text-sm text-x2-primary underline">Link hover</a>
                                </div>
                            </div>
                            <div class="grid gap-3 sm:grid-cols-3">
                                <div><div class="mb-1.5 text-xs font-semibold text-slate-500">Disabled</div><x-x2.btn size="sm" :disabled="true">Disabled</x-x2.btn></div>
                                <div><div class="mb-1.5 text-xs font-semibold text-slate-500">Readonly</div><input class="w-full rounded-xl border-slate-200 bg-slate-50 text-sm" value="Readonly value" readonly /></div>
                                <div><div class="mb-1.5 text-xs font-semibold text-slate-500">Empty</div><div class="rounded-xl border border-dashed border-slate-200 py-3 text-center text-xs text-slate-400">Không có dữ liệu</div></div>
                            </div>
                            <div class="rounded-xl border border-dashed border-x2-red/40 bg-x2-red/5 p-4 text-center">
                                <span class="mx-auto mb-1 grid h-9 w-9 place-items-center rounded-full bg-x2-red/10 text-x2-red">@svg('heroicon-o-lock-closed','h-5 w-5')</span>
                                <div class="text-sm font-semibold text-x2-red">Bạn không có quyền truy cập</div>
                                <p class="text-xs text-slate-500">Liên hệ quản trị viên để được cấp quyền.</p>
                            </div>
                        </div>
                    </x-x2.card.info>

                    <x-x2.card.info title="Hướng dẫn tương phản (Contrast)" icon="heroicon-o-eye">
                        <div class="mb-2 flex gap-3 text-xs"><span class="rounded bg-x2-primary/10 px-1.5 font-semibold text-x2-primary">AA ≥ 4.5:1</span><span class="rounded bg-x2-ai/10 px-1.5 font-semibold text-x2-ai">AAA ≥ 7:1</span></div>
                        <div class="grid grid-cols-2 gap-3 sm:grid-cols-5 text-center text-xs">
                            <div class="rounded-lg border border-x2-green/30 bg-white p-3"><div class="font-semibold text-x2-green">Tốt</div><div class="text-slate-900">7.21:1</div><div class="text-[10px] text-slate-400">Text/nền trắng</div></div>
                            <div class="rounded-lg border border-x2-green/30 bg-white p-3"><div class="font-semibold text-x2-green">Tốt</div><div class="text-slate-500">4.68:1</div><div class="text-[10px] text-slate-400">Text phụ</div></div>
                            <div class="rounded-lg bg-x2-primary p-3 text-white"><div class="font-semibold">Tốt</div><div>12.63:1</div><div class="text-[10px] opacity-80">Trắng/nền xanh</div></div>
                            <div class="rounded-lg border border-x2-red/30 bg-x2-red/5 p-3"><div class="font-semibold text-x2-red">Kém</div><div class="text-slate-300">2.12:1</div><div class="text-[10px] text-slate-400">Xám nhạt</div></div>
                            <div class="rounded-lg border border-x2-red/30 bg-x2-red/5 p-3"><div class="font-semibold text-x2-red">Kém</div><div class="text-white">1.43:1</div><div class="text-[10px] text-slate-400">Trắng/xám nhạt</div></div>
                        </div>
                    </x-x2.card.info>
                </div>

                <x-x2.card.info title="Accessibility checklist" icon="heroicon-o-clipboard-document-check">
                    @foreach ([
                        'Tương phản' => ['Text đủ tương phản (WCAG AA ≥ 4.5:1)', 'Component UI đủ tương phản', 'Không chỉ dựa vào màu — kèm icon/nhãn'],
                        'Điều hướng bàn phím' => ['Mọi phần tử focus được bằng Tab', 'Chỉ báo focus luôn hiển thị', 'Thứ tự tab hợp lý', 'Không bẫy focus'],
                        'Chung' => ['Cung cấp text hỗ trợ (label, helper)', 'Tránh nội dung nhấp nháy > 3 lần'],
                    ] as $group => $items)
                        <div class="mb-3">
                            <div class="mb-1 flex items-center justify-between"><span class="text-sm font-semibold text-slate-700">{{ $group }}</span><span class="rounded bg-x2-green/10 px-1.5 text-[11px] font-semibold text-x2-green">{{ count($items) }}/{{ count($items) }}</span></div>
                            <ul class="space-y-1">
                                @foreach ($items as $it)
                                    <li class="flex gap-1.5 text-xs text-slate-600">@svg('heroicon-s-check-circle','h-4 w-4 shrink-0 text-x2-green') {{ $it }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endforeach
                </x-x2.card.info>
            </div>
        </div>

        {{-- ============ 10 · SHOWCASE ============ --}}
        <div x-show="tab === 'showcase'" x-cloak>
            <x-x2.kpi-row :cols="6">
                <x-x2.card.kpi label="Typography" value="18" sub="Type styles" accent="blue" icon="heroicon-o-language" />
                <x-x2.card.kpi label="Color" value="96" sub="Color tokens" accent="violet" icon="heroicon-o-paint-brush" />
                <x-x2.card.kpi label="Icon" value="156" sub="Icon tokens" accent="teal" icon="heroicon-o-cube" />
                <x-x2.card.kpi label="Spacing" value="10" sub="Spacing scale" accent="green" icon="heroicon-o-view-columns" />
                <x-x2.card.kpi label="Radius" value="6" sub="Radius scale" accent="amber" icon="heroicon-o-square-3-stack-3d" />
                <x-x2.card.kpi label="States" value="7" sub="State types" accent="blue" icon="heroicon-o-check-badge" />
            </x-x2.kpi-row>

            <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-3">
                <x-x2.card.info title="Tổng quan hệ thống token" icon="heroicon-o-rectangle-stack">
                    <div class="divide-y divide-slate-100 text-sm">
                        @foreach ([
                            ['Typography', 'Hệ thống chữ Plus Jakarta & Inter', '18 styles'],
                            ['Color', 'Bảng màu semantic & functional', '96 tokens'],
                            ['Icon', 'Bộ icon outline nhất quán', '156 icons'],
                            ['Spacing', 'Thang khoảng cách 4px grid', '10 steps'],
                            ['Radius', 'Bo góc nhất quán', '6 steps'],
                            ['Shadow', 'Đổ bóng theo chiều sâu', '5 styles'],
                            ['States', 'Trạng thái giao diện chuẩn hóa', '7 states'],
                        ] as [$name, $desc, $count])
                            <div class="flex items-center justify-between py-2.5"><div><div class="font-semibold text-slate-800">{{ $name }}</div><div class="text-xs text-slate-500">{{ $desc }}</div></div><span class="rounded bg-slate-100 px-2 py-0.5 text-[11px] font-semibold text-slate-500">{{ $count }}</span></div>
                        @endforeach
                    </div>
                    <div class="mt-4 flex items-center gap-2 rounded-xl border border-x2-green/30 bg-x2-green/5 px-3 py-2.5">
                        @svg('heroicon-o-check-circle','h-5 w-5 text-x2-green')
                        <div><div class="text-sm font-semibold text-x2-green">DS-02 Foundation Tokens hoàn chỉnh</div><div class="text-xs text-slate-500">Sẵn sàng dùng trong toàn bộ hệ thống X2-BMS.</div></div>
                    </div>
                </x-x2.card.info>

                <x-x2.card.info title="Áp dụng thực tế — Chất lượng dữ liệu cư dân" icon="heroicon-o-computer-desktop" class="lg:col-span-2">
                    <x-x2.kpi-row :cols="6">
                        <x-x2.card.kpi label="Tổng hồ sơ" value="1.269" sub="100%" accent="blue" icon="heroicon-o-users" />
                        <x-x2.card.kpi label="Thiếu thông tin" value="142" sub="11,2%" accent="amber" icon="heroicon-o-exclamation-triangle" />
                        <x-x2.card.kpi label="Trùng lặp" value="26" sub="2,1%" accent="violet" icon="heroicon-o-document-duplicate" />
                        <x-x2.card.kpi label="Sai định dạng" value="18" sub="1,4%" accent="red" icon="heroicon-o-x-circle" />
                        <x-x2.card.kpi label="Cần xác minh" value="37" sub="2,9%" accent="teal" icon="heroicon-o-shield-check" />
                        <x-x2.card.kpi label="Chất lượng tốt" value="1.046" sub="82,4%" accent="green" icon="heroicon-o-check-badge" />
                    </x-x2.kpi-row>
                    <div class="mt-4">
                        <x-x2.table.data>
                            <x-slot:head><th class="px-4 py-2">Mã cư dân</th><th class="px-4 py-2">Họ tên</th><th class="px-4 py-2">Vấn đề</th><th class="px-4 py-2">Mức độ</th><th class="px-4 py-2">Trạng thái</th></x-slot:head>
                            @foreach ([
                                ['CD-0001256', 'Nguyễn Văn Hùng', 'Thiếu ngày cấp CCCD', 'Trung bình', 'amber'],
                                ['CD-0001248', 'Trần Thị Lan', 'Email sai định dạng', 'Thấp', 'green'],
                                ['CD-0001245', 'Lê Văn Cường', 'SĐT trùng lặp', 'Cao', 'red'],
                            ] as [$code, $name, $issue, $sev, $tone])
                                <tr class="hover:bg-slate-50"><td class="px-4 py-2 text-x2-primary">{{ $code }}</td><td class="px-4 py-2">{{ $name }}</td><td class="px-4 py-2 text-slate-600">{{ $issue }}</td><td class="px-4 py-2"><x-x2.status-badge :label="$sev" :tone="$tone" /></td><td class="px-4 py-2"><x-x2.status-badge label="Đang hoạt động" tone="green" /></td></tr>
                            @endforeach
                        </x-x2.table.data>
                    </div>
                </x-x2.card.info>
            </div>
        </div>
    </div>
</x-filament-panels::page>
