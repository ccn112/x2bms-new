<x-filament-panels::page>
    <div class="space-y-6">
        <div>
            <h1 class="font-title text-2xl font-bold text-slate-900">Tổng quan HQ</h1>
            <p class="mt-1 text-sm text-slate-500">
                Điều hành đa dự án cấp công ty
                @if ($tenant)
                    — <span class="font-medium text-slate-700">{{ $tenant->name }}</span>
                @endif
                <span class="mx-1 text-slate-300">•</span> Phạm vi: {{ $scopeLabel }}
            </p>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <x-x2.kpi-card label="Tổng dự án" :value="$totalProjects" accent="blue" sub="Trong phạm vi đang chọn" />
            <x-x2.kpi-card label="Dự án đang hoạt động" :value="$activeProjects" accent="green" />
            <x-x2.kpi-card label="Tổng tòa nhà" :value="$totalBuildings" accent="teal" />
            <x-x2.kpi-card label="Tổng căn hộ" :value="number_format($totalApartments)" accent="amber" />
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <p class="text-sm text-slate-500">
                Cổng Công ty (HQ) đã sẵn sàng. Chọn công ty và phạm vi dự án ở thanh trên cùng để tổng hợp dữ liệu.
                Các module HQ-01 → HQ-05 sẽ lần lượt xuất hiện ở menu bên trái.
            </p>
        </div>
    </div>
</x-filament-panels::page>
