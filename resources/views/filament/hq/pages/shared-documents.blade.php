<x-filament-panels::page>
@php
    $typeBadge = ['sop'=>['SOP','bg-blue-50 text-blue-700'],'policy'=>['Chính sách','bg-violet-50 text-violet-700'],'guide'=>['HDSD','bg-teal-50 text-teal-700'],'contract'=>['Hợp đồng mẫu','bg-amber-50 text-amber-700'],'appendix'=>['Phụ lục','bg-slate-100 text-slate-600'],'form_attachment'=>['Biểu mẫu','bg-emerald-50 text-emerald-700']];
    $sync = ['synced'=>['Đã đồng bộ','bg-emerald-50 text-emerald-700'],'pending'=>['Chờ đồng bộ','bg-amber-50 text-amber-700'],'error'=>['Lỗi đồng bộ','bg-rose-50 text-rose-700']];
    $tabs = ['all'=>'Tất cả','sop'=>'SOP','guide'=>'HDSD','policy'=>'Chính sách','contract'=>'Hợp đồng mẫu','appendix'=>'Phụ lục','form_attachment'=>'Biểu mẫu đính kèm'];
@endphp
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="font-title text-2xl font-bold text-slate-900">Thư viện tài liệu, SOP & chính sách dùng chung</h1>
            <p class="mt-1 text-sm text-slate-500">Quản lý, lưu trữ và chia sẻ tài liệu dùng chung toàn công ty.</p>
        </div>
        <button class="rounded-lg bg-amber-500 px-4 py-2 text-sm font-semibold text-white hover:bg-amber-600">↑ Tải tài liệu lên</button>
    </div>
    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm"><div class="text-xs text-slate-500">Tổng tài liệu</div><div class="mt-1 text-2xl font-bold text-blue-600">{{ number_format($kpi['total_docs']??0) }}</div><div class="text-xs text-slate-400">Dung lượng: {{ $kpi['storage_gb']??0 }} GB</div></div>
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm"><div class="text-xs text-slate-500">SOP đang hiệu lực</div><div class="mt-1 text-2xl font-bold text-emerald-600">{{ number_format($kpi['active_sop']??0) }}</div></div>
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm"><div class="text-xs text-slate-500">Chính sách chờ duyệt</div><div class="mt-1 text-2xl font-bold text-amber-600">{{ (int)($kpi['policy_pending']??0) }}</div></div>
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm"><div class="text-xs text-slate-500">Sắp hết hiệu lực</div><div class="mt-1 text-2xl font-bold text-rose-600">{{ (int)($kpi['expiring']??0) }}</div><div class="text-xs text-slate-400">Trong 30 ngày tới</div></div>
    </div>
    <div class="grid grid-cols-1 gap-6 xl:grid-cols-[240px_1fr]">
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <h3 class="font-title text-xs font-bold uppercase tracking-wide text-slate-400">Danh mục thư mục</h3>
            <ul class="mt-3 space-y-1 text-sm">
                @foreach ($folders as $f)
                    <li class="flex items-center justify-between rounded-lg px-2 py-1.5 hover:bg-slate-50"><span class="flex items-center gap-2 text-slate-700"><svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.75V12A2.25 2.25 0 0 1 4.5 9.75h15A2.25 2.25 0 0 1 21.75 12v.75m-8.69-6.44-2.12-2.12a1.5 1.5 0 0 0-1.061-.44H4.5A2.25 2.25 0 0 0 2.25 6v12a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9a2.25 2.25 0 0 0-2.25-2.25h-5.379a1.5 1.5 0 0 1-1.06-.44Z"/></svg>{{ $f->name }}</span><span class="text-xs text-slate-400">{{ $f->doc_count }}</span></li>
                @endforeach
            </ul>
        </div>
        <div class="space-y-4">
            <div class="flex flex-wrap items-center gap-1 rounded-xl bg-slate-100 p-1">
                @foreach ($tabs as $k => $l)
                    <button wire:click="$set('tab','{{ $k }}')" @class(['rounded-lg px-3 py-1.5 text-sm font-medium','bg-white text-blue-700 shadow-sm'=>$tab===$k,'text-slate-500'=>$tab!==$k])>{{ $l }}</button>
                @endforeach
            </div>
            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-100 text-sm">
                        <thead class="bg-slate-50/70 text-left text-xs font-semibold uppercase tracking-wide text-slate-500"><tr><th class="px-4 py-3">Mã</th><th class="px-4 py-3">Tên tài liệu</th><th class="px-4 py-3">Loại</th><th class="px-4 py-3">Phiên bản</th><th class="px-4 py-3">Hiệu lực</th><th class="px-4 py-3">Chủ sở hữu</th><th class="px-4 py-3">AI Sync</th></tr></thead>
                        <tbody class="divide-y divide-slate-50">
                            @forelse ($rows as $r)
                                <tr class="hover:bg-slate-50/60">
                                    <td class="whitespace-nowrap px-4 py-3 font-semibold text-blue-600">{{ $r['code'] }}</td>
                                    <td class="whitespace-nowrap px-4 py-3 font-medium text-slate-800">{{ $r['name'] }}</td>
                                    <td class="whitespace-nowrap px-4 py-3"><span class="rounded-md px-2 py-0.5 text-xs font-medium {{ $typeBadge[$r['type']][1] ?? '' }}">{{ $typeBadge[$r['type']][0] ?? $r['type'] }}</span></td>
                                    <td class="whitespace-nowrap px-4 py-3 text-slate-500">{{ $r['version'] }}</td>
                                    <td class="whitespace-nowrap px-4 py-3 text-slate-500">{{ $r['effective'] }}</td>
                                    <td class="whitespace-nowrap px-4 py-3 text-slate-500">{{ $r['owner'] }}</td>
                                    <td class="whitespace-nowrap px-4 py-3"><span class="rounded-md px-2 py-0.5 text-xs font-medium {{ $sync[$r['sync']][1] ?? '' }}">{{ $sync[$r['sync']][0] ?? $r['sync'] }}</span></td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="px-4 py-8 text-center text-slate-400">Không có tài liệu.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
</x-filament-panels::page>
