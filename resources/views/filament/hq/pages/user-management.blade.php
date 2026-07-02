<x-filament-panels::page>
@php
    $roleByDept = ['Ban giám đốc'=>['Quản lý','bg-violet-50 text-violet-700'],'Kỹ thuật'=>['Kỹ thuật','bg-blue-50 text-blue-700'],'Kế toán'=>['Kế toán','bg-amber-50 text-amber-700'],'CSKH'=>['CSKH','bg-teal-50 text-teal-700'],'Bảo vệ'=>['Bảo vệ','bg-slate-100 text-slate-600']];
@endphp
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div><h1 class="font-title text-2xl font-bold text-slate-900">Quản lý người dùng</h1>
            <p class="mt-1 text-sm text-slate-500">Quản lý tài khoản, vai trò và trạng thái hoạt động của người dùng hệ thống.</p></div>
        <button class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">+ Thêm người dùng</button>
    </div>
    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm"><div class="text-xs text-slate-500">Tổng người dùng</div><div class="mt-1 text-2xl font-bold text-blue-600">{{ number_format($kpi['total_users']??0) }}</div></div>
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm"><div class="text-xs text-slate-500">Đang hoạt động</div><div class="mt-1 text-2xl font-bold text-emerald-600">{{ number_format($us['Đang hoạt động']??0) }}</div></div>
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm"><div class="text-xs text-slate-500">Chờ kích hoạt</div><div class="mt-1 text-2xl font-bold text-amber-600">{{ number_format($us['Chờ kích hoạt']??0) }}</div></div>
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm"><div class="text-xs text-slate-500">Tạm khóa</div><div class="mt-1 text-2xl font-bold text-rose-600">{{ number_format($us['Tạm khóa']??0) }}</div></div>
    </div>
    <div class="relative max-w-md">
        <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/></svg>
        <input wire:model.live.debounce.300ms="search" type="text" placeholder="Tìm theo họ tên, mã người dùng..." class="w-full rounded-xl border-slate-200 bg-white pl-9 text-sm focus:border-blue-500 focus:ring-blue-500">
    </div>
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-100 text-sm">
                <thead class="bg-slate-50/70 text-left text-xs font-semibold uppercase tracking-wide text-slate-500"><tr><th class="px-4 py-3">Mã NS</th><th class="px-4 py-3">Họ tên</th><th class="px-4 py-3">Chức danh</th><th class="px-4 py-3">Phòng ban</th><th class="px-4 py-3 text-right">Dự án</th><th class="px-4 py-3">Vai trò</th><th class="px-4 py-3">Trạng thái</th><th class="px-4 py-3">Ngày vào làm</th></tr></thead>
                <tbody class="divide-y divide-slate-50">
                    @foreach ($rows as $r)
                        <tr class="hover:bg-slate-50/60">
                            <td class="whitespace-nowrap px-4 py-3 font-semibold text-blue-600">{{ $r['code'] }}</td>
                            <td class="whitespace-nowrap px-4 py-3 font-medium text-slate-800">{{ $r['name'] }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-slate-500">{{ $r['position'] }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-slate-500">{{ $r['dept'] }}</td>
                            <td class="px-4 py-3 text-right"><span class="rounded-md bg-slate-100 px-2 py-0.5 text-xs text-slate-600">{{ $r['projects'] }}</span></td>
                            <td class="whitespace-nowrap px-4 py-3"><span class="rounded-md px-2 py-0.5 text-xs font-medium {{ $roleByDept[$r['dept']][1] ?? 'bg-slate-100 text-slate-600' }}">{{ $roleByDept[$r['dept']][0] ?? '—' }}</span></td>
                            <td class="whitespace-nowrap px-4 py-3">@if($r['status']==='active')<span class="rounded-md bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-700">Đang làm việc</span>@else<span class="rounded-md bg-amber-50 px-2 py-0.5 text-xs font-medium text-amber-700">Chờ phân công</span>@endif</td>
                            <td class="whitespace-nowrap px-4 py-3 text-slate-500">{{ $r['hired'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="border-t border-slate-100 px-4 py-3 text-sm text-slate-500">Hiển thị {{ $rows->count() }} người dùng (tối đa 50/màn).</div>
    </div>
</div>
</x-filament-panels::page>
