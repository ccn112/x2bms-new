<x-filament-panels::page>
@php
    $st = ['published'=>['Đang áp dụng','bg-emerald-50 text-emerald-700'],'draft'=>['Dự thảo','bg-amber-50 text-amber-700'],'archived'=>['Hết hiệu lực','bg-rose-50 text-rose-700']];
    $tabs = ['all'=>'Tất cả biểu mẫu','published'=>'Đang áp dụng','draft'=>'Dự thảo','archived'=>'Hết hiệu lực'];
    $cards = [['Tổng số biểu mẫu',(int)($kpi['total']??0),'blue'],['Đang áp dụng',(int)($kpi['applied']??0),'green'],['Dự thảo',(int)($kpi['draft']??0),'amber'],['Hết hiệu lực',(int)($kpi['expired']??0),'red']];
    $ac=['blue'=>'bg-blue-500','green'=>'bg-emerald-500','amber'=>'bg-amber-500','red'=>'bg-rose-500'];
@endphp
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div><h1 class="font-title text-2xl font-bold text-slate-900">Danh sách biểu mẫu dùng chung</h1>
            <p class="mt-1 text-sm text-slate-500">Quản lý danh mục biểu mẫu dùng chung được sử dụng bởi nhiều dự án và các BQL.</p></div>
        <button class="rounded-lg bg-amber-500 px-4 py-2 text-sm font-semibold text-white hover:bg-amber-600">+ Thêm biểu mẫu</button>
    </div>
    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
        @foreach ($cards as [$label,$value,$c])
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><div class="flex items-center gap-3"><span class="grid h-11 w-11 place-items-center rounded-full {{ $ac[$c] }} text-white"><svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/></svg></span><div><div class="text-xs text-slate-500">{{ $label }}</div><div class="text-2xl font-bold text-slate-900">{{ $value }}</div></div></div></div>
        @endforeach
    </div>
    <div class="flex flex-wrap items-center gap-1 rounded-xl bg-slate-100 p-1 w-fit">
        @foreach ($tabs as $k=>$l)<button wire:click="$set('status','{{ $k }}')" @class(['rounded-lg px-3 py-1.5 text-sm font-medium','bg-white text-blue-700 shadow-sm'=>$status===$k,'text-slate-500'=>$status!==$k])>{{ $l }}</button>@endforeach
    </div>
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-100 text-sm">
                <thead class="bg-slate-50/70 text-left text-xs font-semibold uppercase tracking-wide text-slate-500"><tr><th class="px-4 py-3">Mã mẫu</th><th class="px-4 py-3">Tên biểu mẫu</th><th class="px-4 py-3">Nhóm</th><th class="px-4 py-3 text-right">Dự án áp dụng</th><th class="px-4 py-3">Phiên bản</th><th class="px-4 py-3">Trạng thái</th></tr></thead>
                <tbody class="divide-y divide-slate-50">
                    @forelse ($rows as $r)
                        <tr class="hover:bg-slate-50/60">
                            <td class="whitespace-nowrap px-4 py-3 font-semibold text-blue-600">{{ $r['code'] }}</td>
                            <td class="whitespace-nowrap px-4 py-3 font-medium text-slate-800">{{ $r['name'] }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-slate-500">{{ $r['category'] }}</td>
                            <td class="px-4 py-3 text-right"><span class="rounded-md bg-slate-100 px-2 py-0.5 text-xs text-slate-600">{{ $r['applied'] }} dự án</span></td>
                            <td class="whitespace-nowrap px-4 py-3 text-slate-500">{{ $r['version'] }}</td>
                            <td class="whitespace-nowrap px-4 py-3"><span class="rounded-md px-2 py-0.5 text-xs font-medium {{ $st[$r['status']][1] ?? '' }}">{{ $st[$r['status']][0] ?? $r['status'] }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-8 text-center text-slate-400">Không có biểu mẫu.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
</x-filament-panels::page>
