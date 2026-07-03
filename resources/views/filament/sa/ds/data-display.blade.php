<div class="mb-4 flex items-start gap-2 rounded-xl border border-x2-primary/20 bg-x2-primary/5 px-4 py-3 text-sm text-slate-600">
        <span class="text-x2-primary">@svg('heroicon-o-information-circle', 'h-5 w-5')</span>
        <p><b>Lưu ý:</b> KPI hiển thị tổng hợp theo ngữ cảnh hiện tại và <b>không bị ảnh hưởng bởi bộ lọc</b>. Bộ lọc chỉ áp dụng cho bảng dữ liệu bên dưới.</p>
    </div>

    {{-- 1. KPI aggregation --}}
    <h3 class="font-title mb-3 text-sm font-bold text-slate-700">1. KPI tổng hợp (theo ngữ cảnh)</h3>
    <x-x2.kpi-row :cols="6">
        <x-x2.card.kpi label="Tổng tòa nhà" value="12" sub="Tòa nhà" accent="blue" icon="heroicon-o-building-office-2" />
        <x-x2.card.kpi label="Hoạt động" value="1.248" accent="green" icon="heroicon-o-home-modern" trend="8,2%" />
        <x-x2.card.kpi label="Chờ xử lý" value="84" accent="amber" icon="heroicon-o-clock" trend="5,1%" />
        <x-x2.card.kpi label="Quá hạn" value="23" accent="red" icon="heroicon-o-exclamation-triangle" trend="2,3%" :trendUp="false" />
        <x-x2.card.kpi label="Cư dân" value="2.867" accent="blue" icon="heroicon-o-users" trend="12,4%" />
        <x-x2.card.kpi label="Khoản nợ" value="358.450.000" sub="VND" accent="teal" icon="heroicon-o-banknotes" />
    </x-x2.kpi-row>

    {{-- 2. Card types --}}
    <h3 class="font-title mb-3 mt-6 text-sm font-bold text-slate-700">2. Các loại thẻ (Cards)</h3>
    <div class="grid grid-cols-1 gap-4 lg:grid-cols-4">
        <div class="rounded-xl border border-slate-200 bg-slate-50/60 p-4">
            <div class="flex items-center gap-2 text-slate-600">@svg('heroicon-o-information-circle', 'h-5 w-5 text-x2-primary')<span class="text-sm font-semibold">Thẻ thông tin (Info)</span></div>
            <p class="mt-2 text-xs text-slate-500">Dữ liệu cập nhật lúc 09:30 24/05. Nguồn: Hệ thống X2-BMS.</p>
        </div>
        <div class="rounded-xl border border-x2-amber/30 bg-x2-amber/5 p-4">
            <div class="flex items-center gap-2 text-x2-amber">@svg('heroicon-o-exclamation-triangle', 'h-5 w-5')<span class="text-sm font-semibold">Thẻ cảnh báo (Warning)</span></div>
            <p class="mt-2 text-xs text-slate-600">15 yêu cầu quá hạn xử lý. Vui lòng kiểm tra sớm.</p>
            <x-x2.btn size="sm" class="mt-2">Xem chi tiết</x-x2.btn>
        </div>
        <div class="rounded-xl border border-x2-ai/30 bg-x2-ai/5 p-4">
            <div class="flex items-center gap-2 text-x2-ai">@svg('heroicon-o-sparkles', 'h-5 w-5')<span class="text-sm font-semibold">Thẻ gợi ý AI</span></div>
            <p class="mt-2 text-xs text-slate-600">Tỷ lệ thanh toán kỳ này thấp hơn 8% so với cùng kỳ.</p>
            <div class="mt-2 flex gap-2"><x-x2.btn size="sm" variant="primary">Gửi thông báo</x-x2.btn><x-x2.btn size="sm">Bỏ qua</x-x2.btn></div>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-4">
            <div class="text-sm font-semibold text-slate-700">Thẻ tóm tắt nhỏ</div>
            <dl class="mt-2 space-y-1 text-xs">
                <div class="flex justify-between"><dt class="text-slate-500">Yêu cầu hôm nay</dt><dd class="font-semibold text-slate-800">18</dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">Đã xử lý</dt><dd class="font-semibold text-slate-800">12</dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">Quá hạn</dt><dd class="font-semibold text-x2-red">2</dd></div>
            </dl>
        </div>
    </div>

    {{-- 3. Data table --}}
    <h3 class="font-title mb-3 mt-6 text-sm font-bold text-slate-700">3. Bảng dữ liệu (Data Table)</h3>
    <x-x2.table.data>
        <x-slot:head>
            <th class="px-4 py-3">ID yêu cầu</th>
            <th class="px-4 py-3">Loại</th>
            <th class="px-4 py-3">Người yêu cầu</th>
            <th class="px-4 py-3">Trạng thái</th>
            <th class="px-4 py-3">Ưu tiên</th>
            <th class="px-4 py-3">Ngày tạo</th>
        </x-slot:head>
        @php
            $rows = [
                ['REQ-2025-1023', 'Sửa chữa thiết bị', 'Nguyễn Văn A', 'Đã xử lý', 'green', 'Cao', 'red', '23/05 09:12'],
                ['REQ-2025-1022', 'Vệ sinh căn hộ', 'Trần Thị B', 'Đang xử lý', 'blue', 'Trung bình', 'amber', '23/05 08:45'],
                ['REQ-2025-1021', 'Thẻ từ', 'Lê Minh C', 'Chờ xử lý', 'amber', 'Thấp', 'green', '22/05 16:20'],
                ['REQ-2025-1020', 'Khiếu nại', 'Phạm Thị D', 'Quá hạn', 'red', 'Cao', 'red', '20/05 11:05'],
            ];
        @endphp
        @foreach ($rows as $r)
            <tr class="hover:bg-slate-50">
                <td class="px-4 py-3 font-medium text-x2-primary">{{ $r[0] }}</td>
                <td class="px-4 py-3">{{ $r[1] }}</td>
                <td class="px-4 py-3">{{ $r[2] }}</td>
                <td class="px-4 py-3"><x-x2.status-badge :label="$r[3]" :tone="$r[4]" /></td>
                <td class="px-4 py-3"><x-x2.status-badge :label="$r[5]" :tone="$r[6]" /></td>
                <td class="px-4 py-3 text-slate-500">{{ $r[7] }}</td>
            </tr>
        @endforeach
        <x-slot:footer>
            <span>1 – 4 của 248 dòng</span>
            <span class="flex items-center gap-1">
                <span class="grid h-7 w-7 place-items-center rounded-lg bg-x2-primary text-xs font-semibold text-white">1</span>
                <span class="grid h-7 w-7 place-items-center rounded-lg text-xs text-slate-500">2</span>
                <span class="grid h-7 w-7 place-items-center rounded-lg text-xs text-slate-500">3</span>
            </span>
        </x-slot:footer>
    </x-x2.table.data>

    {{-- 4. Table states --}}
    <h3 class="font-title mb-3 mt-6 text-sm font-bold text-slate-700">4. Trạng thái bảng</h3>
    <div class="grid grid-cols-1 gap-4 lg:grid-cols-4">
        <div class="rounded-xl border border-slate-200 bg-white p-6 text-center">
            <span class="mx-auto mb-2 grid h-10 w-10 place-items-center rounded-full bg-slate-100 text-slate-400">@svg('heroicon-o-inbox', 'h-5 w-5')</span>
            <div class="text-sm font-semibold text-slate-700">Trạng thái trống</div>
            <p class="text-xs text-slate-500">Không có dữ liệu. Thử đổi bộ lọc.</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-6 text-center">
            <svg class="mx-auto mb-2 h-8 w-8 animate-spin text-x2-primary" viewBox="0 0 24 24" fill="none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/></svg>
            <div class="text-sm font-semibold text-slate-700">Đang tải</div>
            <p class="text-xs text-slate-500">Vui lòng chờ giây lát.</p>
        </div>
        <div class="rounded-xl border border-x2-green/30 bg-x2-green/5 p-6 text-center">
            <span class="mx-auto mb-2 grid h-10 w-10 place-items-center rounded-full bg-x2-green/10 text-x2-green">@svg('heroicon-o-check-circle', 'h-5 w-5')</span>
            <div class="text-sm font-semibold text-x2-green">Thành công</div>
            <p class="text-xs text-slate-500">Dữ liệu đã được lưu.</p>
        </div>
        <div class="rounded-xl border border-x2-red/30 bg-x2-red/5 p-6 text-center">
            <span class="mx-auto mb-2 grid h-10 w-10 place-items-center rounded-full bg-x2-red/10 text-x2-red">@svg('heroicon-o-x-circle', 'h-5 w-5')</span>
            <div class="text-sm font-semibold text-x2-red">Lỗi</div>
            <p class="text-xs text-slate-500">Không thể tải dữ liệu. Thử lại.</p>
        </div>
    </div>
