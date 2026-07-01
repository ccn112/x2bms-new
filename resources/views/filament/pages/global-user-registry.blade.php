<x-filament-panels::page>
    <x-x2.action-bar
        title="Sổ đăng ký tài khoản gốc"
        subtitle="Danh tính toàn hệ thống — tồn tại trước khi trở thành cư dân/nhân viên/nhà thầu. Xác thực, khoá, gộp trùng, gắn căn." />

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
        @foreach ($kpis as $kpi)
            <x-x2.kpi-card :label="$kpi['label']" :value="$kpi['value']" :accent="$kpi['accent']" />
        @endforeach
    </div>

    <div class="rounded-2xl border border-slate-100 bg-white p-1 shadow-sm">
        {{ $this->table }}
    </div>
</x-filament-panels::page>
