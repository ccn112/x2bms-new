@php
    $typeMeta = [
        'in' => ['Chuyển đến', 'bg-green-50 text-green-600', 'M12 5v14m0-14 5 5m-5-5-5 5'],
        'out' => ['Chuyển đi', 'bg-red-50 text-red-600', 'M12 19V5m0 14 5-5m-5 5-5-5'],
        'status' => ['Đổi trạng thái', 'bg-slate-100 text-slate-500', 'M4 4v6h6M20 20v-6h-6M20 9a8 8 0 0 0-14.9-2M4 15a8 8 0 0 0 14.9 2'],
    ];
@endphp

<x-filament-panels::page>
    <x-x2.kpi-row :cols="4">
        @foreach ($kpis as $kpi)
            <x-x2.kpi-card :label="$kpi['label']" :value="$kpi['value']" :accent="$kpi['accent']" />
        @endforeach
    </x-x2.kpi-row>

    <div class="flex flex-wrap items-center gap-3">
        <select wire:model.live="typeFilter" class="rounded-lg border-slate-200 text-sm focus:border-x2-primary focus:ring-x2-primary">
            <option value="all">Tất cả sự kiện</option>
            <option value="in">Chuyển đến</option>
            <option value="out">Chuyển đi</option>
            <option value="status">Đổi trạng thái</option>
        </select>
        <span class="text-sm text-slate-400">{{ count($events) }} sự kiện</span>
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-white/10 dark:bg-gray-900">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[720px] text-sm">
                <thead>
                    <tr class="border-b border-slate-100 text-left text-xs uppercase tracking-wide text-slate-400 dark:border-white/10">
                        <th class="px-5 py-3 font-medium">Thời gian</th>
                        <th class="px-5 py-3 font-medium">Loại</th>
                        <th class="px-5 py-3 font-medium">Căn hộ</th>
                        <th class="px-5 py-3 font-medium">Chi tiết</th>
                        <th class="px-5 py-3 font-medium">Vai trò / TT</th>
                        <th class="px-5 py-3 font-medium">Người thực hiện</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50 dark:divide-white/5">
                    @forelse ($events as $e)
                        @php [$tl, $tc, $ti] = $typeMeta[$e['type']] ?? $typeMeta['status']; @endphp
                        <tr class="hover:bg-slate-50/70 dark:hover:bg-white/5">
                            <td class="whitespace-nowrap px-5 py-3 text-slate-600 dark:text-slate-300">{{ $e['date']->format('d/m/Y') }}</td>
                            <td class="px-5 py-3">
                                <span class="inline-flex items-center gap-1.5 rounded-md px-2 py-0.5 text-xs font-semibold {{ $tc }}">
                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $ti }}"/></svg>
                                    {{ $tl }}
                                </span>
                            </td>
                            <td class="px-5 py-3">
                                <div class="font-medium text-slate-800 dark:text-slate-100">{{ $e['apartment'] }}</div>
                                <div class="text-xs text-slate-400">{{ $e['building'] }}</div>
                            </td>
                            <td class="px-5 py-3 text-slate-600 dark:text-slate-300">{{ $e['detail'] }}</td>
                            <td class="px-5 py-3"><span class="text-slate-500">{{ $e['meta'] }}</span></td>
                            <td class="px-5 py-3 text-slate-500">{{ $e['by'] }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-5 py-16 text-center text-sm text-slate-400">Chưa có sự kiện chuyển đến/đi nào.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-filament-panels::page>
