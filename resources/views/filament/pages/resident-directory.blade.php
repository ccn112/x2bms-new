<x-filament-panels::page>
    {{-- Title sits in the header; here we show the subtitle + primary actions. --}}
    <x-x2.action-bar subtitle="Quản lý thông tin cư dân, chủ hộ, người thuê và tài khoản trong tòa">
        <a href="#" class="flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-sm font-medium text-slate-600 hover:bg-slate-50">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 16V4m0 0L8 8m4-4 4 4M4 20h16"/></svg>
            Nhập dữ liệu
        </a>
        <a href="#" class="flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-sm font-medium text-slate-600 hover:bg-slate-50">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v12m0 0 4-4m-4 4-4-4M4 20h16"/></svg>
            Xuất dữ liệu
        </a>
        <a href="{{ url('/fila/residents/create') }}" class="flex items-center gap-1.5 rounded-lg bg-x2-gold px-3.5 py-1.5 text-sm font-semibold text-white shadow-sm hover:bg-x2-gold-600">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14"/></svg>
            Thêm mới
        </a>
    </x-x2.action-bar>

    {{-- KPI row --}}
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ($kpis as $kpi)
            <x-x2.kpi-card :label="$kpi['label']" :value="$kpi['value']" :sub="$kpi['sub'] ?? null" :accent="$kpi['accent']" />
        @endforeach
    </div>

    {{-- Filament table: search / filters / row + bulk actions / pagination --}}
    <div class="rounded-2xl border border-slate-100 bg-white p-1 shadow-sm">
        {{ $this->table }}
    </div>
</x-filament-panels::page>
