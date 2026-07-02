<x-filament-panels::page>
@php
    $st = [
        'effective' => ['Đã hiệu lực', 'bg-emerald-50 text-emerald-700'],
        'approved' => ['Đã duyệt', 'bg-blue-50 text-blue-700'],
        'pending_approval' => ['Chờ duyệt', 'bg-amber-50 text-amber-700'],
        'ended' => ['Kết thúc', 'bg-slate-100 text-slate-500'],
        'rejected' => ['Từ chối', 'bg-rose-50 text-rose-700'],
    ];
    $cards = [['Tổng điều chuyển',$kpi['total'],'blue'],['Đã hiệu lực',$kpi['effective'],'green'],['Đã duyệt',$kpi['approved'],'blue'],['Chờ duyệt',$kpi['pending'],'amber']];
    $mc = ['blue'=>'text-blue-600','green'=>'text-emerald-600','amber'=>'text-amber-600'];
@endphp
<div class="space-y-6">
    <div>
        <h1 class="font-title text-2xl font-bold text-slate-900">Lịch sử luân chuyển nhân sự</h1>
        <p class="mt-1 text-sm text-slate-500">Theo dõi điều chuyển, luân chuyển nhân sự giữa các dự án và trạng thái phê duyệt.</p>
    </div>

    <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
        @foreach ($cards as [$label, $value, $c])
            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <div class="text-sm text-slate-500">{{ $label }}</div>
                <div class="mt-1 text-2xl font-bold {{ $mc[$c] }}">{{ $value }}</div>
            </div>
        @endforeach
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-100 text-sm">
                <thead class="bg-slate-50/70 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                    <tr><th class="px-4 py-3">Mã</th><th class="px-4 py-3">Nhân sự</th><th class="px-4 py-3">Từ dự án</th><th class="px-4 py-3">Đến dự án</th><th class="px-4 py-3">Lý do</th><th class="px-4 py-3">Hiệu lực</th><th class="px-4 py-3">Trạng thái</th></tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @forelse ($rows as $r)
                        <tr class="hover:bg-slate-50/60">
                            <td class="whitespace-nowrap px-4 py-3 font-semibold text-blue-600">{{ $r['code'] }}</td>
                            <td class="whitespace-nowrap px-4 py-3 font-medium text-slate-800">{{ $r['name'] }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-slate-500">{{ $r['from'] }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-slate-500">{{ $r['to'] }}</td>
                            <td class="px-4 py-3 text-slate-500">{{ $r['reason'] }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-slate-500">{{ $r['at'] }}</td>
                            <td class="whitespace-nowrap px-4 py-3"><span class="rounded-md px-2 py-0.5 text-xs font-medium {{ $st[$r['status']][1] ?? '' }}">{{ $st[$r['status']][0] ?? $r['status'] }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-4 py-8 text-center text-slate-400">Chưa có lịch sử luân chuyển.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
</x-filament-panels::page>
