<x-filament-panels::page>
@php
    $typeB = ['form'=>['Biểu mẫu','bg-blue-50 text-blue-700'],'sop'=>['SOP','bg-violet-50 text-violet-700'],'document'=>['Tài liệu','bg-teal-50 text-teal-700'],'checklist'=>['Checklist','bg-amber-50 text-amber-700']];
    $modeB = ['apply'=>['Áp dụng','bg-emerald-50 text-emerald-700'],'inherit'=>['Kế thừa','bg-slate-100 text-slate-600'],'override'=>['Ghi đè','bg-amber-50 text-amber-700'],'force'=>['Bắt buộc','bg-rose-50 text-rose-700']];
@endphp
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div><h1 class="font-title text-2xl font-bold text-slate-900">Áp biểu mẫu / SOP xuống dự án</h1>
            <p class="mt-1 text-sm text-slate-500">Gán biểu mẫu, SOP và tài liệu chuẩn từ công ty xuống từng dự án/BQL.</p></div>
        <button class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">+ Gán tài nguyên</button>
    </div>
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-100 text-sm">
                <thead class="bg-slate-50/70 text-left text-xs font-semibold uppercase tracking-wide text-slate-500"><tr><th class="px-4 py-3">Loại</th><th class="px-4 py-3">Tài nguyên</th><th class="px-4 py-3">Dự án áp dụng</th><th class="px-4 py-3">Chế độ</th><th class="px-4 py-3">Ngày gán</th><th class="px-4 py-3">Trạng thái</th></tr></thead>
                <tbody class="divide-y divide-slate-50">
                    @forelse ($rows as $r)
                        <tr class="hover:bg-slate-50/60">
                            <td class="whitespace-nowrap px-4 py-3"><span class="rounded-md px-2 py-0.5 text-xs font-medium {{ $typeB[$r['type']][1] ?? '' }}">{{ $typeB[$r['type']][0] ?? $r['type'] }}</span></td>
                            <td class="whitespace-nowrap px-4 py-3 font-medium text-slate-800">{{ $r['name'] }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-slate-600">{{ $r['project'] }}</td>
                            <td class="whitespace-nowrap px-4 py-3"><span class="rounded-md px-2 py-0.5 text-xs font-medium {{ $modeB[$r['mode']][1] ?? '' }}">{{ $modeB[$r['mode']][0] ?? $r['mode'] }}</span></td>
                            <td class="whitespace-nowrap px-4 py-3 text-slate-500">{{ $r['at'] }}</td>
                            <td class="whitespace-nowrap px-4 py-3"><span class="rounded-md bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-700">{{ $r['status'] === 'active' ? 'Hiệu lực' : $r['status'] }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-8 text-center text-slate-400">Chưa có gán tài nguyên.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
</x-filament-panels::page>
