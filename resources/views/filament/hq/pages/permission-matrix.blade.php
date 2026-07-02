<x-filament-panels::page>
@php
    // Deterministic demo check state: admin = all; others by role/module heuristic.
    $chk = function($rIdx, $mIdx, $aIdx) {
        if ($rIdx === 0) return true;                 // Quản trị HQ: full
        if ($aIdx >= 3) return $rIdx === 0;           // Xóa/Duyệt chỉ admin
        return ($rIdx + $mIdx) % 2 === 0 && $aIdx <= ($rIdx % 3);
    };
    $roleColor = ['#7c3aed','#2563eb','#f59e0b','#f97316','#8b5cf6','#14b8a6'];
@endphp
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div><h1 class="font-title text-2xl font-bold text-slate-900">Ma trận phân quyền</h1>
            <p class="mt-1 text-sm text-slate-500">Thiết lập quyền thao tác chi tiết theo vai trò và module.</p></div>
        <div class="flex gap-2"><button class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-600">Lưu nháp</button><button class="rounded-lg bg-blue-600 px-3 py-2 text-sm font-semibold text-white hover:bg-blue-700">Lưu thay đổi</button></div>
    </div>
    <div class="overflow-x-auto rounded-2xl border border-slate-200 bg-white shadow-sm">
        <table class="min-w-full border-collapse text-sm">
            <thead>
                <tr class="border-b border-slate-100">
                    <th class="sticky left-0 z-10 bg-white px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Module / Chức năng</th>
                    @foreach ($roles as $ri => $role)
                        <th class="border-l border-slate-100 px-3 py-3 text-center" style="min-width: 200px">
                            <div class="text-sm font-bold" style="color: {{ $roleColor[$ri % 6] }}">{{ $role[0] }}</div>
                            <div class="mt-1 flex justify-center gap-2 text-[10px] font-medium text-slate-400"><span>Xem</span><span>Thêm</span><span>Sửa</span><span>Xóa</span><span>Duyệt</span></div>
                        </th>
                    @endforeach
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
                @foreach ($modules as $mi => $module)
                    <tr class="hover:bg-slate-50/40">
                        <td class="sticky left-0 z-10 bg-white px-4 py-3 font-medium text-slate-800">{{ $module }}</td>
                        @foreach ($roles as $ri => $role)
                            <td class="border-l border-slate-100 px-3 py-3">
                                <div class="flex justify-center gap-2">
                                    @foreach ($actions as $ai => $act)
                                        <input type="checkbox" @checked($chk($ri,$mi,$ai)) class="h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                                    @endforeach
                                </div>
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="flex flex-wrap gap-4 text-xs text-slate-500">
        <span class="flex items-center gap-1"><span class="h-3 w-3 rounded border border-blue-500 bg-blue-500"></span> Xem — truy cập dữ liệu</span>
        <span class="flex items-center gap-1"><span class="h-3 w-3 rounded border border-emerald-500 bg-emerald-500"></span> Thêm — tạo mới</span>
        <span class="flex items-center gap-1"><span class="h-3 w-3 rounded border border-amber-500 bg-amber-500"></span> Sửa — chỉnh sửa</span>
        <span class="flex items-center gap-1"><span class="h-3 w-3 rounded border border-rose-500 bg-rose-500"></span> Xóa — xóa dữ liệu</span>
        <span class="flex items-center gap-1"><span class="h-3 w-3 rounded border border-violet-500 bg-violet-500"></span> Duyệt — phê duyệt</span>
    </div>
</div>
</x-filament-panels::page>
