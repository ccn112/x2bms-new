<x-filament-panels::page>
@php
    $freq = ['daily' => 'Hàng ngày', 'weekly' => 'Hàng tuần', 'monthly' => 'Hàng tháng', 'quarterly' => 'Hàng quý'];
    $jobSt = ['completed' => ['Hoàn tất', 'bg-emerald-50 text-emerald-700'], 'processing' => ['Đang xử lý', 'bg-sky-50 text-sky-700'], 'queued' => ['Chờ xử lý', 'bg-amber-50 text-amber-700'], 'failed' => ['Lỗi', 'bg-rose-50 text-rose-700']];
    $typeLabel = ['debt_summary' => 'Công nợ tổng hợp', 'cashflow' => 'Dòng tiền', 'aging' => 'Tuổi nợ', 'collection' => 'Tỷ lệ thu'];
@endphp
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="font-title text-2xl font-bold text-slate-900">Xuất báo cáo & lịch gửi</h1>
            <p class="mt-1 text-sm text-slate-500">Quản lý lịch gửi báo cáo tự động và các bản xuất báo cáo tài chính.</p>
        </div>
        <button class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">+ Tạo lịch gửi</button>
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 px-4 py-3 font-title text-sm font-bold text-slate-900">Lịch gửi báo cáo</div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-100 text-sm">
                <thead class="bg-slate-50/70 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                    <tr><th class="px-4 py-3">Tên báo cáo</th><th class="px-4 py-3">Tần suất</th><th class="px-4 py-3">Định dạng</th><th class="px-4 py-3">Người nhận</th><th class="px-4 py-3">Lần gửi kế</th><th class="px-4 py-3">Gửi gần nhất</th><th class="px-4 py-3">Trạng thái</th></tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @foreach ($schedules as $s)
                        <tr class="hover:bg-slate-50/60">
                            <td class="whitespace-nowrap px-4 py-3 font-medium text-slate-800">{{ $s['name'] }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-slate-500">{{ $freq[$s['freq']] ?? $s['freq'] }}</td>
                            <td class="whitespace-nowrap px-4 py-3 uppercase text-slate-500">{{ $s['format'] }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-slate-500">{{ $s['recipients'] }} người</td>
                            <td class="whitespace-nowrap px-4 py-3 text-slate-500">{{ $s['next'] }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-slate-500">{{ $s['last'] }}</td>
                            <td class="whitespace-nowrap px-4 py-3"><span class="rounded-md px-2 py-0.5 text-xs font-medium {{ $s['status'] === 'active' ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500' }}">{{ $s['status'] === 'active' ? 'Đang bật' : 'Tạm dừng' }}</span></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 px-4 py-3 font-title text-sm font-bold text-slate-900">Bản xuất gần đây</div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-100 text-sm">
                <thead class="bg-slate-50/70 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                    <tr><th class="px-4 py-3">Loại báo cáo</th><th class="px-4 py-3">Định dạng</th><th class="px-4 py-3">Hoàn tất</th><th class="px-4 py-3">Trạng thái</th><th class="px-4 py-3 text-right">Tải xuống</th></tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @foreach ($jobs as $j)
                        <tr class="hover:bg-slate-50/60">
                            <td class="whitespace-nowrap px-4 py-3 font-medium text-slate-800">{{ $typeLabel[$j['type']] ?? $j['type'] }}</td>
                            <td class="whitespace-nowrap px-4 py-3 uppercase text-slate-500">{{ $j['format'] }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-slate-500">{{ $j['completed'] ?? '—' }}</td>
                            <td class="whitespace-nowrap px-4 py-3"><span class="rounded-md px-2 py-0.5 text-xs font-medium {{ $jobSt[$j['status']][1] ?? '' }}">{{ $jobSt[$j['status']][0] ?? $j['status'] }}</span></td>
                            <td class="whitespace-nowrap px-4 py-3 text-right">@if ($j['file'])<a href="#" class="text-blue-600 hover:underline">Tải về</a>@else<span class="text-slate-300">—</span>@endif</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
</x-filament-panels::page>
