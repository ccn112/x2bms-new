<x-filament-panels::page>
@php
    $tone = ['popular'=>['Phổ biến','border-blue-200','text-blue-600'],'full'=>['Đầy đủ','border-violet-300','text-violet-600'],'intelligent'=>['Thông minh','border-amber-300','text-amber-600']];
@endphp
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="font-title text-2xl font-bold text-slate-900">Chọn gói dịch vụ — {{ $project->name }}</h1>
            <p class="mt-1 text-sm text-slate-500">So sánh và cấu hình gói dịch vụ áp dụng cho dự án.</p>
        </div>
        <a href="{{ url('/hq/projects/'.$project->id) }}" class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50">← Chi tiết dự án</a>
    </div>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-[1fr_320px]">
        <div class="space-y-6">
            {{-- Package cards --}}
            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                @foreach ($plans as $plan)
                    @php $isCurrent = $plan->id === $currentPlanId; $t = $tone[$plan->code] ?? ['',$plan->name,'']; @endphp
                    <div @class(['rounded-2xl border-2 bg-white p-5 shadow-sm', $t[1], 'ring-2 ring-blue-500/30' => $isCurrent])>
                        <div class="flex items-center justify-between">
                            <h3 class="font-title text-lg font-bold text-slate-900">{{ $plan->name }}</h3>
                            @if ($isCurrent)<span class="rounded-full bg-blue-600 px-2 py-0.5 text-xs font-semibold text-white">Đang dùng</span>@endif
                        </div>
                        <div class="mt-2 text-2xl font-bold {{ $t[2] }}">{{ number_format((int) ($plan->monthly_base_price ?? 0)) }}<span class="text-sm font-normal text-slate-400"> đ/tháng</span></div>
                        <div class="mt-1 text-xs text-slate-400">{{ number_format((int) ($plan->yearly_base_price ?? 0)) }} đ/năm</div>
                        <button class="mt-4 w-full rounded-lg px-3 py-2 text-sm font-semibold {{ $isCurrent ? 'bg-slate-100 text-slate-400' : 'bg-blue-600 text-white hover:bg-blue-700' }}" @disabled($isCurrent)>
                            {{ $isCurrent ? 'Gói hiện tại' : 'Chọn gói này' }}
                        </button>
                    </div>
                @endforeach
            </div>

            {{-- Feature matrix --}}
            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-100 px-4 py-3 font-title text-sm font-bold text-slate-900">So sánh tính năng</div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-100 text-sm">
                        <thead class="bg-slate-50/70 text-xs font-semibold uppercase tracking-wide text-slate-500">
                            <tr>
                                <th class="px-4 py-2 text-left">Tính năng</th>
                                @foreach ($plans as $plan)<th class="px-4 py-2 text-center">{{ $plan->name }}</th>@endforeach
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            @foreach ($features as $f)
                                <tr>
                                    <td class="px-4 py-2 text-slate-700">{{ $f->name }}</td>
                                    @foreach ($plans as $plan)
                                        <td class="px-4 py-2 text-center">
                                            @if (in_array($f->id, $planFeatureMap[$plan->id] ?? []))
                                                <svg class="mx-auto h-4 w-4 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke-width="2.4" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                                            @else
                                                <span class="text-slate-300">—</span>
                                            @endif
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Config + summary --}}
        <div class="space-y-4">
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h3 class="font-title text-sm font-bold text-slate-900">Cấu hình thuê bao</h3>
                <div class="mt-3 space-y-3 text-sm">
                    <label class="block"><span class="text-slate-500">Ngày bắt đầu</span>
                        <input type="date" value="{{ optional($period?->started_at)->format('Y-m-d') }}" class="mt-1 w-full rounded-lg border-slate-200 text-sm"></label>
                    <label class="block"><span class="text-slate-500">Chu kỳ thanh toán</span>
                        <select class="mt-1 w-full rounded-lg border-slate-200 text-sm"><option>Hàng tháng</option><option>Hàng quý</option><option>Hàng năm</option></select></label>
                    <label class="flex items-center gap-2"><input type="checkbox" @checked($period?->auto_renew) class="rounded border-slate-300 text-blue-600"><span class="text-slate-600">Tự động gia hạn</span></label>
                </div>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h3 class="font-title text-sm font-bold text-slate-900">Tóm tắt</h3>
                <dl class="mt-3 space-y-2 text-sm">
                    <div class="flex justify-between"><dt class="text-slate-500">Kỳ hiện tại đến</dt><dd class="font-medium text-slate-800">{{ optional($period?->current_period_end)->format('d/m/Y') ?? '—' }}</dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500">Trạng thái duyệt</dt><dd><span class="rounded-md bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-700">{{ $period?->approved_by_platform_at ? 'Đã duyệt' : 'Chờ platform' }}</span></dd></div>
                </dl>
            </div>
        </div>
    </div>
</div>
</x-filament-panels::page>
