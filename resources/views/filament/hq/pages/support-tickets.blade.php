<x-filament-panels::page>
@php
    $pri = ['critical'=>['Nghiêm trọng','bg-rose-50 text-rose-700'],'high'=>['Cao','bg-orange-50 text-orange-700'],'medium'=>['Trung bình','bg-amber-50 text-amber-700'],'low'=>['Thấp','bg-slate-100 text-slate-600']];
    $stB = ['new'=>['Mới','bg-blue-50 text-blue-700'],'open'=>['Đang mở','bg-sky-50 text-sky-700'],'in_progress'=>['Đang xử lý','bg-orange-50 text-orange-700'],'waiting_customer'=>['Chờ phản hồi','bg-violet-50 text-violet-700'],'escalated'=>['Escalated','bg-rose-50 text-rose-700'],'resolved'=>['Đã giải quyết','bg-emerald-50 text-emerald-700'],'closed'=>['Đã đóng','bg-slate-100 text-slate-500'],'reopened'=>['Mở lại','bg-amber-50 text-amber-700']];
    $slaB = ['within_sla'=>['Trong SLA','text-emerald-600'],'near_breach'=>['Sắp trễ','text-amber-600'],'breached'=>['Trễ SLA','text-rose-600'],'resolved'=>['Đã xong','text-slate-400'],'paused_waiting_customer'=>['Tạm dừng','text-slate-400']];
    $tabs = ['all'=>'Tất cả','new'=>'Mới','in_progress'=>'Đang xử lý','waiting_customer'=>'Chờ KH','escalated'=>'Escalated','resolved'=>'Đã giải quyết'];
@endphp
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div><h1 class="font-title text-2xl font-bold text-slate-900">Ticket hỗ trợ</h1>
            <p class="mt-1 text-sm text-slate-500">Quản lý yêu cầu hỗ trợ & phản ánh của cư dân và BQL toàn công ty.</p></div>
        <button class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">+ Tạo ticket</button>
    </div>
    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm"><div class="text-xs text-slate-500">Tổng ticket</div><div class="mt-1 text-2xl font-bold text-blue-600">{{ number_format($kpi['tickets']??0) }}</div></div>
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm"><div class="text-xs text-slate-500">Quá hạn SLA</div><div class="mt-1 text-2xl font-bold text-rose-600">{{ (int)($kpi['sla_overdue']??0) }}</div></div>
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm"><div class="text-xs text-slate-500">CSAT</div><div class="mt-1 text-2xl font-bold text-emerald-600">{{ $kpi['csat']??0 }}/5</div></div>
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm"><div class="text-xs text-slate-500">Vai trò đang dùng</div><div class="mt-1 text-2xl font-bold text-violet-600">{{ (int)($kpi['roles']??0) }}</div></div>
    </div>
    <div class="flex flex-wrap items-center gap-1 rounded-xl bg-slate-100 p-1 w-fit">
        @foreach ($tabs as $k=>$l)<button wire:click="$set('status','{{ $k }}')" @class(['rounded-lg px-3 py-1.5 text-sm font-medium','bg-white text-blue-700 shadow-sm'=>$status===$k,'text-slate-500'=>$status!==$k])>{{ $l }}</button>@endforeach
    </div>
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-100 text-sm">
                <thead class="bg-slate-50/70 text-left text-xs font-semibold uppercase tracking-wide text-slate-500"><tr><th class="px-4 py-3">Mã ticket</th><th class="px-4 py-3">Tiêu đề</th><th class="px-4 py-3">Module</th><th class="px-4 py-3">Ưu tiên</th><th class="px-4 py-3">Người yêu cầu</th><th class="px-4 py-3">SLA</th><th class="px-4 py-3">Trạng thái</th><th class="px-4 py-3">Ngày tạo</th></tr></thead>
                <tbody class="divide-y divide-slate-50">
                    @forelse ($rows as $r)
                        <tr class="cursor-pointer hover:bg-slate-50/60" onclick="window.location='{{ url('/hq/support-tickets/'.$r['id']) }}'">
                            <td class="whitespace-nowrap px-4 py-3 font-semibold text-blue-600">{{ $r['no'] }}</td>
                            <td class="px-4 py-3 font-medium text-slate-800">{{ $r['subject'] }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-slate-500">{{ $r['module'] }}</td>
                            <td class="whitespace-nowrap px-4 py-3"><span class="rounded-md px-2 py-0.5 text-xs font-medium {{ $pri[$r['priority']][1] ?? '' }}">{{ $pri[$r['priority']][0] ?? $r['priority'] }}</span></td>
                            <td class="whitespace-nowrap px-4 py-3 text-slate-500">{{ $r['requester'] }}</td>
                            <td class="whitespace-nowrap px-4 py-3"><span class="text-xs font-medium {{ $slaB[$r['sla']][1] ?? 'text-slate-400' }}">{{ $slaB[$r['sla']][0] ?? $r['sla'] }}</span></td>
                            <td class="whitespace-nowrap px-4 py-3"><span class="rounded-md px-2 py-0.5 text-xs font-medium {{ $stB[$r['status']][1] ?? '' }}">{{ $stB[$r['status']][0] ?? $r['status'] }}</span></td>
                            <td class="whitespace-nowrap px-4 py-3 text-slate-500">{{ $r['at'] }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="px-4 py-8 text-center text-slate-400">Không có ticket.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
</x-filament-panels::page>
