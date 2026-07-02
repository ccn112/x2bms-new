<x-filament-panels::page>
@php
    $modeB = ['inherit'=>['Kế thừa','bg-blue-50 text-blue-700'],'override'=>['Cho phép ghi đè','bg-amber-50 text-amber-700'],'force'=>['Bắt buộc áp dụng','bg-rose-50 text-rose-700'],'block'=>['Chặn','bg-slate-100 text-slate-500']];
    $scope = ['platform'=>'Nền tảng','tenant'=>'Công ty','project'=>'Dự án'];
    $rtype = ['form'=>'Biểu mẫu','sop'=>'SOP','document'=>'Tài liệu','kb'=>'Tri thức'];
@endphp
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div><h1 class="font-title text-2xl font-bold text-slate-900">Quy tắc kế thừa & override</h1>
            <p class="mt-1 text-sm text-slate-500">Thiết lập cách biểu mẫu/SOP/tài liệu được kế thừa và ghi đè giữa các cấp Nền tảng → Công ty → Dự án.</p></div>
        <button class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">+ Thêm quy tắc</button>
    </div>
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-100 text-sm">
                <thead class="bg-slate-50/70 text-left text-xs font-semibold uppercase tracking-wide text-slate-500"><tr><th class="px-4 py-3">Ưu tiên</th><th class="px-4 py-3">Loại tài nguyên</th><th class="px-4 py-3">Từ cấp</th><th class="px-4 py-3">Đến cấp</th><th class="px-4 py-3">Chế độ</th><th class="px-4 py-3">Ghi chú</th><th class="px-4 py-3">Trạng thái</th></tr></thead>
                <tbody class="divide-y divide-slate-50">
                    @forelse ($rows as $r)
                        <tr class="hover:bg-slate-50/60">
                            <td class="px-4 py-3"><span class="grid h-6 w-6 place-items-center rounded-full bg-slate-100 text-xs font-bold text-slate-500">{{ $r['priority'] }}</span></td>
                            <td class="whitespace-nowrap px-4 py-3 font-medium text-slate-800">{{ $rtype[$r['type']] ?? $r['type'] }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-slate-600">{{ $scope[$r['from']] ?? $r['from'] }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-slate-600">{{ $scope[$r['to']] ?? $r['to'] }}</td>
                            <td class="whitespace-nowrap px-4 py-3"><span class="rounded-md px-2 py-0.5 text-xs font-medium {{ $modeB[$r['mode']][1] ?? '' }}">{{ $modeB[$r['mode']][0] ?? $r['mode'] }}</span></td>
                            <td class="px-4 py-3 text-slate-500">{{ $r['note'] }}</td>
                            <td class="whitespace-nowrap px-4 py-3"><span class="rounded-md bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-700">{{ $r['status'] === 'active' ? 'Đang bật' : 'Tắt' }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-4 py-8 text-center text-slate-400">Chưa có quy tắc.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
</x-filament-panels::page>
