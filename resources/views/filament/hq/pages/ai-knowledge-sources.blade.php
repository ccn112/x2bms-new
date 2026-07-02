<x-filament-panels::page>
@php $st = ['synced'=>['Đã đồng bộ','bg-emerald-50 text-emerald-700'],'syncing'=>['Đang đồng bộ','bg-sky-50 text-sky-700'],'error'=>['Lỗi','bg-rose-50 text-rose-700'],'pending'=>['Chờ','bg-amber-50 text-amber-700']]; @endphp
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div><h1 class="font-title text-2xl font-bold text-slate-900">Cấu hình tri thức cho X2AI</h1>
            <p class="mt-1 text-sm text-slate-500">Cấu hình nguồn dữ liệu, lịch đồng bộ và chính sách re-index cho hệ thống AI.</p></div>
        <div class="flex gap-2">
            <button class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-600">Tạo lịch</button>
            <button class="rounded-lg bg-blue-600 px-3 py-2 text-sm font-semibold text-white hover:bg-blue-700">Đồng bộ ngay</button>
        </div>
    </div>
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm"><div class="text-xs text-slate-500">Nguồn dữ liệu</div><div class="mt-1 text-2xl font-bold text-slate-900">{{ $sources->count() }}</div></div>
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm"><div class="text-xs text-slate-500">Dung lượng đã lập chỉ mục</div><div class="mt-1 text-2xl font-bold text-blue-600">{{ number_format($totalGb, 1) }} GB</div></div>
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm"><div class="text-xs text-slate-500">Mục đã index</div><div class="mt-1 text-2xl font-bold text-violet-600">{{ number_format($totalItems) }}</div></div>
    </div>
    <div class="grid grid-cols-1 gap-6 xl:grid-cols-[1fr_320px]">
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 px-4 py-3 font-title text-sm font-bold text-slate-900">Nguồn dữ liệu kết nối</div>
            <table class="min-w-full divide-y divide-slate-100 text-sm">
                <thead class="bg-slate-50/70 text-left text-xs font-semibold uppercase tracking-wide text-slate-500"><tr><th class="px-4 py-2">Nguồn</th><th class="px-4 py-2">Trạng thái</th><th class="px-4 py-2 text-right">Dung lượng</th><th class="px-4 py-2 text-right">Mục chỉ mục</th><th class="px-4 py-2">Đồng bộ</th></tr></thead>
                <tbody class="divide-y divide-slate-50">
                    @foreach ($sources as $s)
                        <tr class="hover:bg-slate-50/60">
                            <td class="px-4 py-2"><div class="font-medium text-slate-800">{{ $s['name'] }}</div><div class="text-xs text-slate-400">{{ $s['provider'] }}</div></td>
                            <td class="px-4 py-2"><span class="rounded-md px-2 py-0.5 text-xs font-medium {{ $st[$s['status']][1] ?? '' }}">{{ $st[$s['status']][0] ?? $s['status'] }}</span></td>
                            <td class="px-4 py-2 text-right text-slate-600">{{ number_format($s['gb'], 1) }} GB</td>
                            <td class="px-4 py-2 text-right text-slate-600">{{ number_format($s['items']) }}</td>
                            <td class="px-4 py-2 text-slate-400">{{ $s['synced'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h3 class="font-title text-sm font-bold text-slate-900">Nhật ký đồng bộ gần đây</h3>
            <ul class="mt-3 space-y-3 text-sm">
                @foreach ($logs as $l)
                    <li class="border-l-2 border-slate-100 pl-3">
                        <div class="flex items-center justify-between"><span class="font-medium text-slate-700">{{ $l['event'] }}</span><span class="rounded bg-emerald-50 px-1.5 text-xs text-emerald-700">{{ $l['status'] }}</span></div>
                        <div class="text-xs text-slate-400">{{ $l['new'] }} mục mới · {{ $l['updated'] }} cập nhật · {{ $l['errors'] }} lỗi · {{ $l['at'] }}</div>
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
</div>
</x-filament-panels::page>
