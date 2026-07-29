@php($stats = $this->getStats())

<x-filament-panels::page>
    <x-x2.action-bar
        title="Nối dự án vận hành với danh mục công khai"
        subtitle="Dự án vận hành (có toà, căn hộ, cư dân) và bản ghi danh mục công khai là hai bảng riêng. Nối đúng thì cư dân và khách quan tâm cùng một dự án mới được coi là cùng một dự án." />

    <div class="grid gap-4 sm:grid-cols-3">
        <x-x2.kpi-card label="Tổng dự án vận hành" :value="$stats['total']" accent="slate" />
        <x-x2.kpi-card label="Đã nối danh mục" :value="$stats['linked']" accent="emerald" />
        <x-x2.kpi-card label="Chưa nối" :value="$stats['pending']" accent="amber" />
    </div>

    {{-- Cảnh báo đặt ngay trên bảng chứ không giấu trong tooltip: nối nhầm dự án
         không tạo ra lỗi nào nhìn thấy được, nên người nối phải đọc trước khi bấm. --}}
    <div class="rounded-2xl border border-amber-200 bg-amber-50 p-4">
        <div class="flex gap-3">
            <x-heroicon-o-exclamation-triangle class="mt-0.5 h-5 w-5 flex-none text-amber-600" />
            <div class="text-sm text-amber-900">
                <p class="font-semibold">Đối chiếu tỉnh/quận trước khi nối</p>
                <p class="mt-1 leading-relaxed">
                    Nhiều dự án trùng tên ở các tỉnh khác nhau — “Sunshine Garden” là một ví dụ.
                    Nối nhầm sẽ gắn cư dân dự án này vào danh mục dự án khác, và
                    <strong>không có thông báo lỗi nào</strong>: hệ thống vẫn chạy bình thường,
                    chỉ là người theo dõi nhận nội dung của dự án không phải của họ.
                </p>
            </div>
        </div>
    </div>

    <div class="rounded-2xl border border-slate-100 bg-white p-1 shadow-sm">
        {{ $this->table }}
    </div>
</x-filament-panels::page>
