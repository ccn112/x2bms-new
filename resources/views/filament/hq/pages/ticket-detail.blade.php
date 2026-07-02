<x-filament-panels::page>
@php
    $pri = ['critical'=>['Nghiêm trọng','bg-rose-50 text-rose-700'],'high'=>['Cao','bg-orange-50 text-orange-700'],'medium'=>['Trung bình','bg-amber-50 text-amber-700'],'low'=>['Thấp','bg-slate-100 text-slate-600']];
    $stB = ['new'=>['Mới','bg-blue-50 text-blue-700'],'open'=>['Đang mở','bg-sky-50 text-sky-700'],'in_progress'=>['Đang xử lý','bg-orange-50 text-orange-700'],'waiting_customer'=>['Chờ phản hồi','bg-violet-50 text-violet-700'],'escalated'=>['Escalated','bg-rose-50 text-rose-700'],'resolved'=>['Đã giải quyết','bg-emerald-50 text-emerald-700'],'closed'=>['Đã đóng','bg-slate-100 text-slate-500']];
@endphp
<div class="space-y-6">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <div class="flex items-center gap-3">
                <a href="{{ url('/hq/support-tickets') }}" class="text-slate-400 hover:text-slate-600">←</a>
                <h1 class="font-title text-2xl font-bold text-slate-900">{{ $t->ticket_no }}</h1>
                <span class="rounded-md px-2 py-0.5 text-xs font-medium {{ $stB[$t->status][1] ?? '' }}">{{ $stB[$t->status][0] ?? $t->status }}</span>
                <span class="rounded-md px-2 py-0.5 text-xs font-medium {{ $pri[$t->priority][1] ?? '' }}">{{ $pri[$t->priority][0] ?? $t->priority }}</span>
            </div>
            <p class="mt-1 text-lg text-slate-700">{{ $t->subject }}</p>
        </div>
        <div class="flex gap-2">
            <button class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-600">Giao xử lý</button>
            <button class="rounded-lg bg-blue-600 px-3 py-2 text-sm font-semibold text-white hover:bg-blue-700">Trả lời</button>
        </div>
    </div>
    <div class="grid grid-cols-1 gap-6 xl:grid-cols-[1fr_320px]">
        <div class="space-y-4">
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h3 class="font-title text-sm font-bold text-slate-900">Nội dung</h3>
                <p class="mt-2 text-sm text-slate-600">{{ $t->description }}</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h3 class="font-title text-sm font-bold text-slate-900">Trao đổi</h3>
                <ul class="mt-4 space-y-4">
                    @foreach ($messages as $m)
                        <li class="flex gap-3">
                            <span class="grid h-8 w-8 shrink-0 place-items-center rounded-full {{ $m->type === 'internal' ? 'bg-violet-100 text-violet-600' : 'bg-blue-100 text-blue-600' }} text-xs font-bold">{{ mb_substr($m->author_name ?? '?', 0, 1) }}</span>
                            <div class="flex-1 rounded-xl {{ $m->type === 'internal' ? 'bg-violet-50/50' : 'bg-slate-50' }} p-3">
                                <div class="flex items-center justify-between"><span class="text-sm font-semibold text-slate-800">{{ $m->author_name }}</span><span class="rounded px-1.5 text-[10px] {{ $m->type === 'internal' ? 'bg-violet-100 text-violet-600' : 'bg-blue-100 text-blue-600' }}">{{ $m->type === 'internal' ? 'Nội bộ' : 'Khách hàng' }}</span></div>
                                <p class="mt-1 text-sm text-slate-600">{{ $m->body }}</p>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
        <div class="space-y-4">
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h3 class="font-title text-sm font-bold text-slate-900">Thông tin ticket</h3>
                <dl class="mt-3 space-y-2 text-sm">
                    <div class="flex justify-between"><dt class="text-slate-500">Module</dt><dd class="font-medium text-slate-800">{{ $t->module }}</dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500">Người yêu cầu</dt><dd class="font-medium text-slate-800">{{ $t->requester_name }}</dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500">Liên hệ</dt><dd class="font-medium text-slate-800">{{ $t->requester_contact }}</dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500">Kênh</dt><dd class="font-medium text-slate-800">{{ $t->channel }}</dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500">Trạng thái SLA</dt><dd class="font-medium text-slate-800">{{ $t->sla_state }}</dd></div>
                    @if($t->csat_score)<div class="flex justify-between"><dt class="text-slate-500">CSAT</dt><dd class="font-medium text-emerald-600">{{ $t->csat_score }}/5</dd></div>@endif
                </dl>
            </div>
        </div>
    </div>
</div>
</x-filament-panels::page>
