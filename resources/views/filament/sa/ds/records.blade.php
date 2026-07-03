<div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
        {{-- 1. Tab types --}}
        <x-x2.card.info title="1. Kiểu tab" icon="heroicon-o-rectangle-stack">
            <div class="mb-3 text-xs font-semibold text-slate-500">Tab trang (Page tabs)</div>
            <div class="flex gap-1 border-b border-slate-200 text-sm">
                <span class="font-title border-b-2 border-x2-primary px-2 py-1.5 font-semibold text-x2-primary">Tổng quan</span>
                <span class="px-2 py-1.5 text-slate-500">Cư dân</span>
                <span class="px-2 py-1.5 text-slate-500">Hợp đồng</span>
            </div>
            <div class="mb-3 mt-4 text-xs font-semibold text-slate-500">Tab dạng pill</div>
            <div class="flex flex-wrap gap-1.5 text-sm">
                <span class="rounded-lg bg-x2-primary/10 px-2.5 py-1 font-medium text-x2-primary">Tất cả</span>
                <span class="rounded-lg bg-slate-100 px-2.5 py-1 text-slate-600">Đang hoạt động</span>
                <span class="rounded-lg bg-slate-100 px-2.5 py-1 text-slate-600">Đã hoàn thành</span>
            </div>
        </x-x2.card.info>

        {{-- 2. Record detail --}}
        <x-x2.card.info title="2. Bố cục bản ghi (Record Detail)" icon="heroicon-o-identification" class="xl:col-span-2">
            <div class="flex items-start justify-between">
                <div>
                    <div class="font-title text-lg font-bold text-slate-900">CH-12A.08 · Tháp A · Tầng 12</div>
                    <div class="mt-1 flex flex-wrap gap-2 text-xs text-slate-500">
                        <span>2PN · 2WC</span><span>75.6 m²</span><x-x2.status-badge label="Đang ở" tone="green" />
                    </div>
                </div>
                <div class="flex gap-2"><x-x2.btn size="sm">Chỉnh sửa</x-x2.btn><x-x2.btn size="sm" variant="primary">Tạo yêu cầu</x-x2.btn></div>
            </div>
            {{-- step progress --}}
            <div class="mt-4 flex items-center gap-1 text-[11px]">
                <span class="grid h-6 w-6 place-items-center rounded-full bg-x2-primary text-white">@svg('heroicon-m-check','h-3.5 w-3.5')</span>
                <span class="h-0.5 w-10 bg-x2-primary"></span>
                <span class="grid h-6 w-6 place-items-center rounded-full bg-x2-primary text-white">2</span>
                <span class="h-0.5 w-10 bg-slate-200"></span>
                <span class="grid h-6 w-6 place-items-center rounded-full bg-slate-200 text-slate-500">3</span>
                <span class="ml-2 text-slate-500">Đang ở → Đang xử lý → Chờ duyệt</span>
            </div>
        </x-x2.card.info>
    </div>

    <div class="mt-6 grid grid-cols-1 gap-5 xl:grid-cols-3">
        {{-- 3. Info blocks --}}
        <x-x2.card.info title="3. Khối thông tin (Info blocks)" icon="heroicon-o-squares-2x2" class="xl:col-span-2">
            <div class="grid gap-4 sm:grid-cols-2">
                <dl class="space-y-1.5 text-sm">
                    <div class="flex justify-between"><dt class="text-slate-500">Mã căn hộ</dt><dd class="font-medium text-slate-800">CH-12A.08</dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500">Diện tích</dt><dd class="font-medium text-slate-800">75.6 m²</dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500">Tình trạng</dt><dd><x-x2.status-badge label="Đã bàn giao" tone="green" /></dd></div>
                </dl>
                <dl class="space-y-1.5 text-sm">
                    <div class="flex justify-between"><dt class="text-slate-500">Chủ hộ</dt><dd class="font-medium text-slate-800">Trần Minh Đức</dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500">Hợp đồng</dt><dd class="font-medium text-slate-800">HD-2024-000812</dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500">Trạng thái</dt><dd><x-x2.status-badge label="Còn hiệu lực" tone="green" /></dd></div>
                </dl>
            </div>
            <div class="mt-4 text-xs font-semibold text-slate-500">Danh sách liên quan (Related lists)</div>
            <x-x2.table.data class="mt-2">
                <x-slot:head><th class="px-3 py-2">Mã hóa đơn</th><th class="px-3 py-2">Kỳ</th><th class="px-3 py-2">Số tiền</th><th class="px-3 py-2">Trạng thái</th></x-slot:head>
                <tr class="hover:bg-slate-50"><td class="px-3 py-2 text-x2-primary">HD-2024-0512</td><td class="px-3 py-2">05/2024</td><td class="px-3 py-2">2.650.000</td><td class="px-3 py-2"><x-x2.status-badge label="Đã thanh toán" tone="green" /></td></tr>
                <tr class="hover:bg-slate-50"><td class="px-3 py-2 text-x2-primary">HD-2024-0312</td><td class="px-3 py-2">03/2024</td><td class="px-3 py-2">2.650.000</td><td class="px-3 py-2"><x-x2.status-badge label="Quá hạn" tone="red" /></td></tr>
            </x-x2.table.data>
        </x-x2.card.info>

        {{-- 4. Timeline + AI panel --}}
        <div class="space-y-5">
            <x-x2.card.info title="4. Dòng thời gian (Timeline)" icon="heroicon-o-clock">
                <ol class="relative space-y-4 border-l border-slate-200 pl-4 text-sm">
                    @foreach ([
                        ['green','Yêu cầu đã hoàn thành','Bởi: KTV Lê Văn Nam','10:30'],
                        ['blue','Yêu cầu được xử lý','Bởi: KTV Lê Văn Nam','09:45'],
                        ['amber','Yêu cầu mới được tạo','Bởi: Trần Minh Đức','18:20'],
                    ] as [$tone,$title,$who,$time])
                        <li class="relative">
                            <span class="absolute -left-[21px] top-1 h-2.5 w-2.5 rounded-full bg-x2-{{ $tone }}"></span>
                            <div class="font-medium text-slate-800">{{ $title }}</div>
                            <div class="text-xs text-slate-500">{{ $who }} · {{ $time }}</div>
                        </li>
                    @endforeach
                </ol>
                <p class="mt-2 text-xs text-slate-400">Timeline luôn ghi: người · hành động · đối tượng · thời gian · ngữ cảnh.</p>
            </x-x2.card.info>

            <x-x2.card.info title="5. Gợi ý AI (Side panel)" icon="heroicon-o-sparkles">
                <div class="rounded-lg border border-x2-ai/20 bg-x2-ai/5 p-3 text-sm">
                    <div class="font-medium text-slate-800">Đề xuất vệ sinh điều hòa định kỳ</div>
                    <p class="mt-1 text-xs text-slate-600">Căn hộ đã 3 tháng chưa vệ sinh điều hòa.</p>
                    <a href="#" class="mt-1 inline-block text-xs font-semibold text-x2-ai">Xem chi tiết →</a>
                </div>
            </x-x2.card.info>
        </div>
    </div>
