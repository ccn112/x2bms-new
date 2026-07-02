<x-filament-panels::page>
@php
    $roleBadge = [
        'Quản lý' => 'bg-violet-50 text-violet-700 ring-violet-600/20',
        'Kỹ thuật' => 'bg-blue-50 text-blue-700 ring-blue-600/20',
        'Kế toán' => 'bg-amber-50 text-amber-700 ring-amber-600/20',
        'CSKH' => 'bg-teal-50 text-teal-700 ring-teal-600/20',
        'Bảo vệ' => 'bg-slate-100 text-slate-600 ring-slate-500/20',
    ];
    $kpiCards = [
        ['Tổng nhân sự', number_format($kpi['total']), '+6 so với tháng trước', 'blue'],
        ['Đang làm việc', number_format($kpi['working']), $kpi['workingPct'].'% tổng nhân sự', 'green'],
        ['Đa dự án', number_format($kpi['multi']), $kpi['multiPct'].'% nhân sự', 'amber'],
        ['Chờ phân công', number_format($kpi['pending']), $kpi['pendingPct'].'% nhân sự', 'red'],
    ];
    $accent = ['blue'=>'bg-blue-500','green'=>'bg-emerald-500','amber'=>'bg-amber-500','red'=>'bg-rose-500'];
    $C = 2 * M_PI * 54; $offset = 0;
@endphp

<div class="space-y-6">
    <div>
        <h1 class="font-title text-2xl font-bold text-slate-900">Danh sách nhân sự công ty</h1>
        <p class="mt-1 text-sm text-slate-500">Quản lý hồ sơ nhân sự, phân công đa dự án và theo dõi trạng thái làm việc.</p>
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ($kpiCards as [$label, $value, $sub, $color])
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-center gap-4">
                    <span class="grid h-12 w-12 shrink-0 place-items-center rounded-full {{ $accent[$color] }} text-white">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z"/></svg>
                    </span>
                    <div>
                        <div class="text-sm font-medium text-slate-500">{{ $label }}</div>
                        <div class="mt-0.5 text-3xl font-bold tracking-tight text-slate-900">{{ $value }}</div>
                        <div class="mt-0.5 text-xs text-slate-400">{{ $sub }}</div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-[1fr_320px]">
        <div class="space-y-4">
            <div class="flex flex-wrap items-center gap-3">
                <div class="flex flex-wrap items-center gap-1 rounded-xl bg-slate-100 p-1">
                    @foreach ($tabs as $t)
                        <button wire:click="$set('dept', '{{ $t['id'] }}')"
                                @class([
                                    'flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-sm font-medium transition',
                                    'bg-white text-blue-700 shadow-sm' => $dept === $t['id'],
                                    'text-slate-500 hover:text-slate-700' => $dept !== $t['id'],
                                ])>
                            {{ $t['label'] }}
                            <span class="rounded-full bg-slate-200/70 px-1.5 text-[11px] font-semibold text-slate-600">{{ $t['count'] }}</span>
                        </button>
                    @endforeach
                </div>
                <div class="relative ml-auto min-w-[220px] flex-1">
                    <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/></svg>
                    <input wire:model.live.debounce.300ms="search" type="text" placeholder="Tìm theo họ tên, mã nhân sự..."
                           class="w-full rounded-xl border-slate-200 bg-white pl-9 text-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
            </div>

            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-100 text-sm">
                        <thead class="bg-slate-50/70 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                            <tr>
                                <th class="px-4 py-3">Mã NS</th>
                                <th class="px-4 py-3">Họ tên</th>
                                <th class="px-4 py-3">Chức danh</th>
                                <th class="px-4 py-3">Phòng ban</th>
                                <th class="px-4 py-3">Dự án phụ trách</th>
                                <th class="px-4 py-3">Vai trò</th>
                                <th class="px-4 py-3">Trạng thái</th>
                                <th class="px-4 py-3">Ngày vào làm</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            @forelse ($rows as $r)
                                <tr class="hover:bg-slate-50/60">
                                    <td class="whitespace-nowrap px-4 py-3 font-semibold text-blue-600">{{ $r['code'] }}</td>
                                    <td class="whitespace-nowrap px-4 py-3 font-medium text-slate-800">{{ $r['name'] }}</td>
                                    <td class="whitespace-nowrap px-4 py-3 text-slate-500">{{ $r['position'] }}</td>
                                    <td class="whitespace-nowrap px-4 py-3 text-slate-500">{{ $r['dept'] }}</td>
                                    <td class="whitespace-nowrap px-4 py-3">
                                        <span class="inline-flex items-center rounded-md bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-600">{{ $r['projects'] }} dự án</span>
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3">
                                        <span class="inline-flex items-center rounded-md px-2 py-0.5 text-xs font-medium ring-1 ring-inset {{ $roleBadge[$r['dept_label']] ?? 'bg-slate-100 text-slate-600 ring-slate-500/20' }}">{{ $r['dept_label'] }}</span>
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3">
                                        @if ($r['status'] === 'active')
                                            <span class="inline-flex items-center rounded-md bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-700 ring-1 ring-inset ring-emerald-600/20">Đang làm việc</span>
                                        @else
                                            <span class="inline-flex items-center rounded-md bg-amber-50 px-2 py-0.5 text-xs font-medium text-amber-700 ring-1 ring-inset ring-amber-600/20">Chờ phân công</span>
                                        @endif
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3 text-slate-500">{{ $r['hired'] }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="8" class="px-4 py-10 text-center text-slate-400">Không có nhân sự phù hợp.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-slate-100 px-4 py-3 text-sm text-slate-500">Hiển thị {{ $rows->count() }} nhân sự (tối đa 60/màn).</div>
            </div>
        </div>

        <div class="space-y-4">
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h3 class="font-title text-sm font-bold text-slate-900">Thống kê theo phòng ban</h3>
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
                            <div><div class="text-[11px] text-slate-400">Tổng</div><div class="text-2xl font-bold text-slate-900">{{ $kpi['total'] }}</div></div>
                        </div>
                    </div>
                    <div class="flex-1 space-y-1.5">
                        @foreach ($donut as $seg)
                            <div class="flex items-center gap-2 text-xs">
                                <span class="h-2.5 w-2.5 rounded-full" style="background: {{ $seg['color'] }}"></span>
                                <span class="text-slate-600">{{ $seg['name'] }}</span>
                                <span class="ml-auto font-semibold text-slate-800">{{ $seg['value'] }}</span>
                                <span class="w-10 text-right text-slate-400">({{ $seg['pct'] }}%)</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h3 class="font-title text-sm font-bold text-slate-900">Dự án thiếu nhân sự</h3>
                <ul class="mt-3 space-y-3">
                    @foreach ($understaffed as $u)
                        <li class="flex items-center gap-2 text-sm">
                            <span class="grid h-6 w-6 place-items-center rounded-full bg-rose-50 text-rose-600 text-xs font-bold">!</span>
                            <span class="font-medium text-slate-700">{{ $u['name'] }}</span>
                            <span class="ml-auto text-xs font-semibold text-rose-600">Thiếu {{ $u['missing'] }} vị trí</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
</div>
</x-filament-panels::page>
