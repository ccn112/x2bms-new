<x-filament-panels::page>
@php
    $statusMeta = [
        'active' => ['Đang hoạt động', 'bg-emerald-50 text-emerald-700 ring-emerald-600/20'],
        'trial' => ['Trial', 'bg-sky-50 text-sky-700 ring-sky-600/20'],
        'suspended' => ['Tạm ngừng', 'bg-slate-100 text-slate-600 ring-slate-500/20'],
    ];
    $planBadge = [
        'violet' => 'bg-violet-50 text-violet-700 ring-violet-600/20',
        'blue' => 'bg-blue-50 text-blue-700 ring-blue-600/20',
        'amber' => 'bg-amber-50 text-amber-700 ring-amber-600/20',
        'gray' => 'bg-slate-100 text-slate-600 ring-slate-500/20',
    ];
    $kpiCards = [
        ['Tổng dự án', $kpi['total'], '+3 so với tháng trước', 'blue', 'M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5'],
        ['Dự án đang hoạt động', $kpi['active'], $kpi['activePct'].'% tổng dự án', 'green', 'M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z'],
        ['Gói sắp gia hạn', $kpi['renewSoon'], 'Trong 30 ngày tới', 'amber', 'M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0V11.25A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5'],
        ['BQL thiếu nhân sự', $kpi['understaffed'], 'Cần bổ sung', 'red', 'M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z'],
    ];
    $accent = [
        'blue' => 'bg-blue-50 text-blue-600', 'green' => 'bg-emerald-50 text-emerald-600',
        'amber' => 'bg-amber-50 text-amber-600', 'red' => 'bg-rose-50 text-rose-600',
    ];
    // Donut geometry
    $C = 2 * M_PI * 54; $offset = 0;
@endphp

<div class="space-y-6">
    <div>
        <h1 class="font-title text-2xl font-bold text-slate-900">Danh mục dự án quản lý</h1>
        <p class="mt-1 text-sm text-slate-500">Quản lý danh mục dự án, gói dịch vụ và hiệu quả vận hành tại các tòa nhà.</p>
    </div>

    {{-- KPI cards --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ($kpiCards as [$label, $value, $sub, $color, $icon])
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-start gap-4">
                    <span class="grid h-12 w-12 shrink-0 place-items-center rounded-xl {{ $accent[$color] }}">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}"/></svg>
                    </span>
                    <div class="min-w-0">
                        <div class="text-sm font-medium text-slate-500">{{ $label }}</div>
                        <div class="mt-0.5 text-3xl font-bold tracking-tight text-slate-900">{{ $value }}</div>
                        <div class="mt-1 text-xs {{ $color === 'amber' || $color === 'red' ? 'text-amber-600' : 'text-slate-400' }}">{{ $sub }}</div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-[1fr_340px]">
        {{-- Main: tabs + search + table --}}
        <div class="space-y-4">
            <div class="flex flex-wrap items-center gap-3">
                <div class="flex flex-wrap items-center gap-1 rounded-xl bg-slate-100 p-1">
                    @foreach (['all' => 'Tất cả', 'active' => 'Đang hoạt động', 'trial' => 'Trial', 'suspended' => 'Tạm ngừng'] as $key => $label)
                        <button wire:click="$set('tab', '{{ $key }}')"
                                @class([
                                    'flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-sm font-medium transition',
                                    'bg-white text-blue-700 shadow-sm' => $tab === $key,
                                    'text-slate-500 hover:text-slate-700' => $tab !== $key,
                                ])>
                            {{ $label }}
                            <span class="rounded-full bg-slate-200/70 px-1.5 text-[11px] font-semibold text-slate-600">{{ $tabs[$key] }}</span>
                        </button>
                    @endforeach
                </div>
                <div class="relative ml-auto min-w-[220px] flex-1">
                    <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/></svg>
                    <input wire:model.live.debounce.300ms="search" type="text" placeholder="Tìm theo tên hoặc mã dự án..."
                           class="w-full rounded-xl border-slate-200 bg-white pl-9 text-sm placeholder:text-slate-400 focus:border-blue-500 focus:ring-blue-500">
                </div>
            </div>

            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-100 text-sm">
                        <thead class="bg-slate-50/70 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                            <tr>
                                <th class="px-4 py-3">Mã dự án</th>
                                <th class="px-4 py-3">Tên dự án</th>
                                <th class="px-4 py-3">Loại hình</th>
                                <th class="px-4 py-3">Trưởng BQL</th>
                                <th class="px-4 py-3">Gói dịch vụ</th>
                                <th class="px-4 py-3">Bắt đầu gói</th>
                                <th class="px-4 py-3">Gia hạn tiếp</th>
                                <th class="px-4 py-3">Trạng thái</th>
                                <th class="px-4 py-3 text-right">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            @forelse ($rows as $r)
                                <tr class="hover:bg-slate-50/60">
                                    <td class="whitespace-nowrap px-4 py-3 font-semibold text-blue-600">
                                        <a href="{{ url('/hq/projects/'.$r['id']) }}">{{ $r['code'] }}</a>
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3 font-medium text-slate-800">{{ $r['name'] }}</td>
                                    <td class="whitespace-nowrap px-4 py-3 text-slate-500">{{ $r['type'] }}</td>
                                    <td class="whitespace-nowrap px-4 py-3 text-slate-600">{{ $r['manager'] }}</td>
                                    <td class="whitespace-nowrap px-4 py-3">
                                        <span class="inline-flex items-center rounded-md px-2 py-0.5 text-xs font-medium ring-1 ring-inset {{ $planBadge[$r['plan_badge']] }}">{{ $r['plan'] }}</span>
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3 text-slate-500">{{ $r['started'] }}</td>
                                    <td class="whitespace-nowrap px-4 py-3 text-slate-500">{{ $r['renew'] }}</td>
                                    <td class="whitespace-nowrap px-4 py-3">
                                        @if ($r['renew_soon'] && $r['status'] === 'active')
                                            <span class="inline-flex items-center gap-1 rounded-md bg-amber-50 px-2 py-0.5 text-xs font-medium text-amber-700 ring-1 ring-inset ring-amber-600/20">⏱ Sắp gia hạn</span>
                                        @else
                                            <span class="inline-flex items-center rounded-md px-2 py-0.5 text-xs font-medium ring-1 ring-inset {{ $statusMeta[$r['status']][1] }}">{{ $statusMeta[$r['status']][0] }}</span>
                                        @endif
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3 text-right">
                                        <a href="{{ url('/hq/projects/'.$r['id']) }}" class="text-slate-400 hover:text-blue-600">Xem</a>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="9" class="px-4 py-10 text-center text-slate-400">Không có dự án phù hợp.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="flex items-center justify-between border-t border-slate-100 px-4 py-3 text-sm text-slate-500">
                    <span>Hiển thị {{ $rows->count() }} / {{ $tabs['all'] }} dự án</span>
                    <a href="{{ url('/hq/projects/create') }}" class="inline-flex items-center gap-1.5 rounded-lg bg-blue-600 px-3 py-1.5 text-sm font-semibold text-white hover:bg-blue-700">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                        Thêm dự án
                    </a>
                </div>
            </div>
        </div>

        {{-- Right panel --}}
        <div class="space-y-4">
            {{-- Donut --}}
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h3 class="font-title text-sm font-bold text-slate-900">Phân bổ gói dịch vụ</h3>
                <div class="mt-4 flex items-center gap-4">
                    <div class="relative h-32 w-32 shrink-0">
                        <svg viewBox="0 0 120 120" class="h-32 w-32 -rotate-90">
                            <circle cx="60" cy="60" r="54" fill="none" stroke="#f1f5f9" stroke-width="12"/>
                            @foreach ($donut as $seg)
                                @php $len = $C * $seg['value'] / max($kpi['total'], 1); @endphp
                                <circle cx="60" cy="60" r="54" fill="none" stroke="{{ $seg['color'] }}" stroke-width="12"
                                        stroke-dasharray="{{ $len }} {{ $C - $len }}" stroke-dashoffset="{{ -$offset }}"/>
                                @php $offset += $len; @endphp
                            @endforeach
                        </svg>
                        <div class="absolute inset-0 grid place-items-center text-center">
                            <div>
                                <div class="text-[11px] font-medium text-slate-400">Tổng</div>
                                <div class="text-2xl font-bold text-slate-900">{{ $kpi['total'] }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="flex-1 space-y-1.5">
                        @foreach ($donut as $seg)
                            <div class="flex items-center gap-2 text-xs">
                                <span class="h-2.5 w-2.5 rounded-full" style="background: {{ $seg['color'] }}"></span>
                                <span class="text-slate-600">{{ $seg['label'] }}</span>
                                <span class="ml-auto font-semibold text-slate-800">{{ $seg['value'] }}</span>
                                <span class="w-10 text-right text-slate-400">({{ $seg['pct'] }}%)</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Quick stats --}}
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h3 class="font-title text-sm font-bold text-slate-900">Tổng quan nhanh</h3>
                <dl class="mt-3 space-y-3 text-sm">
                    <div class="flex items-center justify-between"><dt class="text-slate-500">Tổng BQL</dt><dd class="font-semibold text-slate-900">{{ number_format($quick['bql']) }} người</dd></div>
                    <div class="flex items-center justify-between"><dt class="text-slate-500">Tòa nhà</dt><dd class="font-semibold text-slate-900">{{ number_format($quick['buildings']) }} tòa</dd></div>
                    <div class="flex items-center justify-between"><dt class="text-slate-500">Cư dân</dt><dd class="font-semibold text-slate-900">{{ number_format($quick['residents']) }} người</dd></div>
                    <div class="flex items-center justify-between"><dt class="text-slate-500">Diện tích quản lý</dt><dd class="font-semibold text-slate-900">{{ number_format($quick['area']) }} m²</dd></div>
                </dl>
            </div>
        </div>
    </div>
</div>
</x-filament-panels::page>
