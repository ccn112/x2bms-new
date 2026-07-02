<x-filament-panels::page>
@php
    $cards = [['Tổng số yêu cầu',$kpi['total'],'blue'],['Đang chờ xử lý',$kpi['processing'],'amber'],['Chờ duyệt',$kpi['pending'],'violet'],['Hoàn tất',$kpi['completed'],'green']];
    $ac = ['blue'=>'bg-blue-500','amber'=>'bg-amber-500','violet'=>'bg-violet-500','green'=>'bg-emerald-500'];
    $typeBadge = ['upgrade'=>['Nâng cấp','bg-blue-50 text-blue-700'],'downgrade'=>['Hạ gói','bg-amber-50 text-amber-700'],'renew'=>['Gia hạn','bg-emerald-50 text-emerald-700']];
    $stBadge = ['processing'=>['Đang xử lý','text-blue-600','bg-blue-500'],'pending_approval'=>['Chờ duyệt','text-amber-600','bg-amber-500'],'completed'=>['Hoàn tất','text-emerald-600','bg-emerald-500'],'rejected'=>['Từ chối','text-rose-600','bg-rose-500'],'draft'=>['Nháp','text-slate-500','bg-slate-400']];
@endphp
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="font-title text-2xl font-bold text-slate-900">Yêu cầu nâng cấp / hạ gói / gia hạn</h1>
            <p class="mt-1 text-sm text-slate-500">Quản lý các yêu cầu nâng cấp, hạ gói hoặc gia hạn dịch vụ của các dự án.</p>
        </div>
        <button class="rounded-lg bg-amber-500 px-4 py-2 text-sm font-semibold text-white hover:bg-amber-600">+ Tạo yêu cầu</button>
    </div>

    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
        @foreach ($cards as [$label, $value, $c])
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-center gap-3">
                    <span class="grid h-11 w-11 place-items-center rounded-full {{ $ac[$c] }} text-white">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 8.25H7.5a2.25 2.25 0 0 0-2.25 2.25v9a2.25 2.25 0 0 0 2.25 2.25h9a2.25 2.25 0 0 0 2.25-2.25v-9a2.25 2.25 0 0 0-2.25-2.25H15m0-3-3-3m0 0-3 3m3-3V15"/></svg>
                    </span>
                    <div><div class="text-sm text-slate-500">{{ $label }}</div><div class="text-2xl font-bold text-slate-900">{{ $value }}</div></div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="flex flex-wrap items-center gap-3">
        <div class="flex flex-wrap items-center gap-1 rounded-xl bg-slate-100 p-1">
            @foreach ($tabs as $key => [$label, $count])
                <button wire:click="$set('type', '{{ $key }}')" @class(['flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-sm font-medium transition','bg-white text-blue-700 shadow-sm'=>$type===$key,'text-slate-500 hover:text-slate-700'=>$type!==$key])>
                    {{ $label }} <span class="rounded-full bg-slate-200/70 px-1.5 text-[11px] font-semibold text-slate-600">{{ $count }}</span>
                </button>
            @endforeach
        </div>
        <div class="relative ml-auto min-w-[220px] flex-1">
            <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/></svg>
            <input wire:model.live.debounce.300ms="search" type="text" placeholder="Tìm theo mã, dự án, nội dung..." class="w-full rounded-xl border-slate-200 bg-white pl-9 text-sm focus:border-blue-500 focus:ring-blue-500">
        </div>
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-100 text-sm">
                <thead class="bg-slate-50/70 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                    <tr><th class="px-4 py-3">Mã yêu cầu</th><th class="px-4 py-3">Dự án</th><th class="px-4 py-3">Loại</th><th class="px-4 py-3">Nội dung</th><th class="px-4 py-3">Ngày tạo</th><th class="px-4 py-3">Trạng thái</th></tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @forelse ($rows as $r)
                        <tr class="hover:bg-slate-50/60">
                            <td class="whitespace-nowrap px-4 py-3 font-semibold text-slate-700">{{ $r['no'] }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-slate-600">{{ $r['project'] }}</td>
                            <td class="whitespace-nowrap px-4 py-3"><span class="rounded-md px-2 py-0.5 text-xs font-medium {{ $typeBadge[$r['type']][1] ?? '' }}">{{ $typeBadge[$r['type']][0] ?? $r['type'] }}</span></td>
                            <td class="px-4 py-3 text-slate-600">{{ $r['content'] }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-slate-500">{{ $r['date'] }}</td>
                            <td class="whitespace-nowrap px-4 py-3">
                                <span class="inline-flex items-center gap-1.5 text-xs font-medium {{ $stBadge[$r['status']][1] ?? '' }}">
                                    <span class="h-1.5 w-1.5 rounded-full {{ $stBadge[$r['status']][2] ?? '' }}"></span>{{ $stBadge[$r['status']][0] ?? $r['status'] }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-8 text-center text-slate-400">Không có yêu cầu phù hợp.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-slate-100 px-4 py-3 text-sm text-slate-500">Hiển thị {{ $rows->count() }} / {{ $kpi['total'] }} kết quả</div>
    </div>
</div>
</x-filament-panels::page>
