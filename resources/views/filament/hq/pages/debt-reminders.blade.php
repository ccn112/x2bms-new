<x-filament-panels::page>
@php
    $ty = fn ($v) => number_format($v / 1_000_000_000, 2).' tỷ';
    $chBadge = ['sms' => ['SMS', 'bg-emerald-50 text-emerald-700'], 'zalo' => ['Zalo', 'bg-blue-50 text-blue-700'], 'email' => ['Email', 'bg-violet-50 text-violet-700'], 'app' => ['App', 'bg-orange-50 text-orange-700'], 'call' => ['Gọi điện', 'bg-slate-100 text-slate-600'], 'mixed' => ['Đa kênh', 'bg-slate-100 text-slate-600']];
    $stBadge = ['running' => ['Đang chạy', 'bg-emerald-50 text-emerald-700'], 'paused' => ['Bị tạm dừng', 'bg-amber-50 text-amber-700'], 'completed' => ['Kết thúc', 'bg-slate-100 text-slate-500'], 'draft' => ['Nháp', 'bg-slate-100 text-slate-500']];
@endphp
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="font-title text-2xl font-bold text-slate-900">Lịch sử nhắc nợ & chiến dịch thu hồi</h1>
            <p class="mt-1 text-sm text-slate-500">Theo dõi hiệu quả nhắc nợ và chiến dịch thu hồi trên toàn công ty.</p>
        </div>
        <button class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">+ Tạo chiến dịch</button>
    </div>

    <div class="grid grid-cols-2 gap-4 lg:grid-cols-5">
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm"><div class="text-xs text-slate-500">Chiến dịch đang chạy</div><div class="mt-1 text-2xl font-bold text-blue-600">{{ (int) ($kpi['running'] ?? 0) }}</div></div>
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm"><div class="text-xs text-slate-500">Tin nhắn đã gửi</div><div class="mt-1 text-2xl font-bold text-emerald-600">{{ number_format($kpi['sent'] ?? 0) }}</div></div>
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm"><div class="text-xs text-slate-500">Phản hồi thành công</div><div class="mt-1 text-2xl font-bold text-slate-900">{{ number_format($kpi['responses'] ?? 0) }}</div></div>
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm"><div class="text-xs text-slate-500">Cam kết thanh toán</div><div class="mt-1 text-lg font-bold text-amber-600">{{ $ty($kpi['committed'] ?? 0) }}</div></div>
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm"><div class="text-xs text-slate-500">Hồ sơ escalated</div><div class="mt-1 text-2xl font-bold text-rose-600">{{ number_format($kpi['escalated'] ?? 0) }}</div></div>
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 px-4 py-3 font-title text-sm font-bold text-slate-900">Lịch sử chiến dịch</div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-100 text-sm">
                <thead class="bg-slate-50/70 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                    <tr><th class="px-4 py-3">Chiến dịch</th><th class="px-4 py-3">Phạm vi</th><th class="px-4 py-3">Kênh</th><th class="px-4 py-3 text-right">Mục tiêu</th><th class="px-4 py-3 text-right">Đã gửi</th><th class="px-4 py-3 text-right">Phản hồi</th><th class="px-4 py-3 text-right">Cam kết</th><th class="px-4 py-3 text-right">Đã thu</th><th class="px-4 py-3">Phụ trách</th><th class="px-4 py-3">Trạng thái</th></tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @foreach ($rows as $r)
                        <tr class="hover:bg-slate-50/60">
                            <td class="whitespace-nowrap px-4 py-3 font-medium text-slate-800">{{ $r['name'] }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-slate-500">{{ $r['scope'] }}</td>
                            <td class="whitespace-nowrap px-4 py-3"><span class="rounded-md px-2 py-0.5 text-xs font-medium {{ $chBadge[$r['channel']][1] ?? '' }}">{{ $chBadge[$r['channel']][0] ?? $r['channel'] }}</span></td>
                            <td class="px-4 py-3 text-right text-slate-600">{{ number_format($r['target']) }}</td>
                            <td class="px-4 py-3 text-right text-slate-600">{{ number_format($r['sent']) }}</td>
                            <td class="px-4 py-3 text-right text-blue-600">{{ $r['response'] }}%</td>
                            <td class="px-4 py-3 text-right text-slate-700">{{ $ty($r['committed']) }}</td>
                            <td class="px-4 py-3 text-right font-semibold text-emerald-600">{{ $ty($r['collected']) }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-slate-500">{{ $r['owner'] }}</td>
                            <td class="whitespace-nowrap px-4 py-3"><span class="rounded-md px-2 py-0.5 text-xs font-medium {{ $stBadge[$r['status']][1] ?? '' }}">{{ $stBadge[$r['status']][0] ?? $r['status'] }}</span></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
</x-filament-panels::page>
