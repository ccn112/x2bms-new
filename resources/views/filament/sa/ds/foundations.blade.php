<div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
        {{-- 1. Typography --}}
        <x-x2.card.info title="1. Hệ thống Typography" icon="heroicon-o-language" class="xl:col-span-2">
            <div class="grid gap-6 sm:grid-cols-2">
                <div class="space-y-3">
                    <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">Heading · Plus Jakarta Sans</div>
                    <div>
                        <div class="text-[11px] text-slate-400">H1 · 32/40 · 700</div>
                        <div class="font-title text-3xl font-bold text-slate-900">Quản lý tòa nhà dễ dàng, thông minh</div>
                    </div>
                    <div>
                        <div class="text-[11px] text-slate-400">H2 · 24/32 · 700</div>
                        <div class="font-title text-2xl font-bold text-slate-900">Tiêu đề cấp 2</div>
                    </div>
                    <div>
                        <div class="text-[11px] text-slate-400">H3 · 20/28 · 700</div>
                        <div class="font-title text-xl font-bold text-slate-900">Tiêu đề cấp 3</div>
                    </div>
                </div>
                <div class="space-y-3">
                    <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">Body · Inter</div>
                    <div><div class="text-[11px] text-slate-400">Regular 400</div><p class="text-sm text-slate-700">Nội dung văn bản thông thường.</p></div>
                    <div><div class="text-[11px] text-slate-400">Medium 500</div><p class="text-sm font-medium text-slate-700">Nội dung nhấn vừa.</p></div>
                    <div><div class="text-[11px] text-slate-400">Semibold 600</div><p class="text-sm font-semibold text-slate-800">Nội dung nhấn mạnh.</p></div>
                    <div><div class="text-[11px] text-slate-400">Caption · 12/16</div><p class="text-xs text-slate-500">Chú thích, ghi chú phụ.</p></div>
                </div>
            </div>
        </x-x2.card.info>

        {{-- 2. Colors --}}
        <x-x2.card.info title="2. Hệ thống màu sắc" icon="heroicon-o-paint-brush">
            @php
                $colors = [
                    ['Navy', '#0B2146'], ['Gold', '#D5A331'], ['Blue', '#2563EB'],
                    ['Success', '#16A34A'], ['Warning', '#F59E0B'], ['Danger', '#EF4444'],
                    ['AI Purple', '#7C3AED'],
                ];
            @endphp
            <div class="grid grid-cols-3 gap-3">
                @foreach ($colors as [$name, $hex])
                    <div>
                        <div class="h-12 w-full rounded-lg shadow-sm ring-1 ring-black/5" style="background: {{ $hex }}"></div>
                        <div class="mt-1 text-xs font-semibold text-slate-700">{{ $name }}</div>
                        <div class="text-[11px] uppercase text-slate-400">{{ $hex }}</div>
                    </div>
                @endforeach
            </div>
        </x-x2.card.info>
    </div>

    <div class="mt-6 grid grid-cols-1 gap-5 xl:grid-cols-3">
        {{-- 3. Spacing & Radius --}}
        <x-x2.card.info title="3. Khoảng cách & Bo góc" icon="heroicon-o-view-columns">
            <div class="grid grid-cols-2 gap-6">
                <div>
                    <div class="mb-2 text-xs font-semibold text-slate-500">Spacing</div>
                    <div class="space-y-2">
                        @foreach ([4 => 'Siêu nhỏ', 8 => 'Nhỏ', 12 => 'Cơ bản', 16 => 'Tiêu chuẩn', 24 => 'Khối', 32 => 'Khối lớn', 40 => 'Siêu lớn'] as $px => $name)
                            <div class="flex items-center gap-2">
                                <span class="inline-block rounded bg-x2-primary/70" style="width: {{ min($px, 40) }}px; height: 10px;"></span>
                                <span class="text-xs text-slate-600">{{ $px }}px · {{ $name }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
                <div>
                    <div class="mb-2 text-xs font-semibold text-slate-500">Radius</div>
                    <div class="space-y-2">
                        @foreach ([4 => 'Rất nhỏ', 8 => 'Nhỏ', 12 => 'Tiêu chuẩn', 16 => 'Lớn', 20 => 'Rất lớn', 24 => 'Siêu lớn'] as $px => $name)
                            <div class="flex items-center gap-2">
                                <span class="inline-block h-6 w-6 border-2 border-x2-primary/60 bg-x2-primary/5" style="border-radius: {{ $px }}px;"></span>
                                <span class="text-xs text-slate-600">{{ $px }}px · {{ $name }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </x-x2.card.info>

        {{-- 4. Navigation usage --}}
        <x-x2.card.info title="4. Cách dùng điều hướng" icon="heroicon-o-bars-3">
            <ul class="space-y-3 text-sm">
                <li class="flex gap-2"><span class="mt-0.5 text-x2-gold">▸</span><div><b class="text-slate-800">Sidebar</b> — nhóm theo chức năng, phân cấp rõ ràng; item active nền gold.</div></li>
                <li class="flex gap-2"><span class="mt-0.5 text-x2-gold">▸</span><div><b class="text-slate-800">Header</b> — truy cập nhanh, tìm kiếm toàn hệ thống (Ctrl+K), tạo mới & thông báo.</div></li>
                <li class="flex gap-2"><span class="mt-0.5 text-x2-gold">▸</span><div><b class="text-slate-800">Context Switcher</b> — chuyển Công ty / Dự án / Vai trò linh hoạt.</div></li>
            </ul>
            <div class="mt-3 rounded-lg bg-x2-navy p-3">
                <div class="font-title text-sm font-bold text-white">X2-BMS</div>
                <div class="mt-2 rounded-md bg-x2-gold px-2 py-1 text-xs font-semibold text-white">Tổng quan</div>
                <div class="mt-1 px-2 py-1 text-xs text-slate-300">Cư dân</div>
                <div class="px-2 py-1 text-xs text-slate-300">Hồ sơ căn hộ</div>
            </div>
        </x-x2.card.info>

        {{-- 5. Layout rules --}}
        <x-x2.card.info title="5. Quy tắc bố cục trang" icon="heroicon-o-rectangle-group">
            <div class="space-y-2">
                <div class="rounded-lg bg-x2-primary/10 px-3 py-2 text-sm font-medium text-x2-primary">Tabs — phân khu chức năng</div>
                <div class="rounded-lg bg-x2-green/10 px-3 py-2 text-sm font-medium text-x2-green">KPI &amp; Actions — tổng quan &amp; hành động</div>
                <div class="rounded-lg bg-x2-amber/10 px-3 py-2 text-sm font-medium text-x2-amber">Filters — bộ lọc &amp; tìm kiếm (chỉ ảnh hưởng bảng)</div>
                <div class="rounded-lg bg-x2-ai/10 px-3 py-2 text-sm font-medium text-x2-ai">Table / Content — bảng dữ liệu / nội dung</div>
            </div>
        </x-x2.card.info>
    </div>

    {{-- 6. Design principles --}}
    <div class="mt-5">
        <x-x2.card.info title="6. Nguyên tắc thiết kế" icon="heroicon-o-check-badge">
            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                @foreach ([
                    ['Nhất quán & Đồng bộ', 'Thống nhất màu sắc, typography, khoảng cách, icon.'],
                    ['Rõ ràng & Dễ hiểu', 'Ưu tiên tính rõ ràng, giảm tải nhận thức.'],
                    ['Hiệu quả & Linh hoạt', 'Tối ưu quy trình, hỗ trợ tùy biến.'],
                    ['Tin cậy & An toàn', 'Bảo mật dữ liệu, kiểm soát truy cập.'],
                ] as [$title, $desc])
                    <div class="flex gap-2">
                        <span class="mt-0.5 text-x2-green">@svg('heroicon-s-check-circle', 'h-5 w-5')</span>
                        <div><div class="text-sm font-semibold text-slate-800">{{ $title }}</div><div class="text-xs text-slate-500">{{ $desc }}</div></div>
                    </div>
                @endforeach
            </div>
        </x-x2.card.info>
    </div>
