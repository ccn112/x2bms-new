<x-filament-panels::page>
    <div class="rounded-2xl border border-slate-200 bg-white p-4 text-sm text-slate-600 shadow-sm">
        Kéo (⠿) để sắp lại thứ tự phân bổ tiền khi cư dân trả một phần nợ — dòng ở
        <strong>trên</strong> được trả trước. Chỉ áp dụng cho dự án
        <strong>{{ $projectName }}</strong>; các dự án khác của công ty vẫn dùng mặc định
        (Phí quản lý → Nước → Điện → Phương tiện → Khác), trừ khi tự sắp riêng.
    </div>

    <div class="rounded-2xl border border-slate-100 bg-white p-1 shadow-sm">
        {{ $this->table }}
    </div>
</x-filament-panels::page>
