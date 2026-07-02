<x-filament-panels::page>
    <x-x2.kpi-row :cols="6">
        @foreach ($kpis as $kpi)
            <x-x2.kpi-card :label="$kpi['label']" :value="$kpi['value']" :accent="$kpi['accent']" />
        @endforeach
    </x-x2.kpi-row>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-[1fr_360px]">
        {{-- Recent access events --}}
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-white/10 dark:bg-gray-900">
            <div class="flex items-center justify-between border-b border-slate-100 px-5 py-3 dark:border-white/10">
                <h3 class="text-base font-semibold text-slate-900 dark:text-white">Sự kiện ra/vào gần đây</h3>
                <span class="text-xs text-slate-400">{{ count($recent) }} mục</span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[560px] text-sm">
                    <thead>
                        <tr class="border-b border-slate-100 text-left text-xs uppercase tracking-wide text-slate-400 dark:border-white/10">
                            <th class="px-5 py-2.5 font-medium">Thời gian</th>
                            <th class="px-5 py-2.5 font-medium">Cổng / Thiết bị</th>
                            <th class="px-5 py-2.5 font-medium">Chiều</th>
                            <th class="px-5 py-2.5 font-medium">Phương thức</th>
                            <th class="px-5 py-2.5 font-medium">Trạng thái</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50 dark:divide-white/5">
                        @forelse ($recent as $r)
                            <tr class="hover:bg-slate-50/70 dark:hover:bg-white/5">
                                <td class="whitespace-nowrap px-5 py-3 text-slate-600 dark:text-slate-300">{{ $r['time'] }}</td>
                                <td class="px-5 py-3 text-slate-700 dark:text-slate-200">{{ $r['gate'] }}</td>
                                <td class="px-5 py-3">
                                    <x-x2.status-badge :label="$r['direction'][0]" :tone="$r['direction'][1]" />
                                </td>
                                <td class="px-5 py-3 text-slate-500">{{ $r['method'] }}</td>
                                <td class="px-5 py-3"><span class="text-slate-500">{{ $r['status'] }}</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-5 py-12 text-center text-sm text-slate-400">Chưa có sự kiện ra/vào.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Expiring cards --}}
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-gray-900">
            <h3 class="text-base font-semibold text-slate-900 dark:text-white">Thẻ sắp hết hạn</h3>
            <p class="text-sm text-slate-500">Trong 30 ngày tới</p>
            <ul class="mt-4 space-y-3">
                @forelse ($expiring as $c)
                    <li class="flex items-center justify-between gap-2">
                        <div class="min-w-0">
                            <div class="truncate text-sm font-medium text-slate-800 dark:text-slate-100">{{ $c['card_no'] }}</div>
                            <div class="truncate text-xs text-slate-400">{{ $c['resident'] }}</div>
                        </div>
                        <div class="text-right">
                            <div class="text-xs text-slate-500">{{ $c['valid_to'] }}</div>
                            @if (! is_null($c['days']))
                                <span class="inline-flex rounded bg-amber-50 px-1.5 py-0.5 text-[11px] font-semibold text-amber-600">còn {{ $c['days'] }} ngày</span>
                            @endif
                        </div>
                    </li>
                @empty
                    <li class="py-6 text-center text-sm text-slate-400">Không có thẻ sắp hết hạn.</li>
                @endforelse
            </ul>
            <div class="mt-4 flex flex-col gap-2 border-t border-slate-100 pt-4 dark:border-white/10">
                <a href="{{ url('/admin/access/vehicle-requests') }}" class="flex items-center justify-between rounded-lg bg-slate-50 px-3 py-2 text-sm font-medium text-x2-primary hover:bg-slate-100 dark:bg-white/5">Duyệt đăng ký xe <span aria-hidden>→</span></a>
                <a href="{{ url('/admin/access/cards') }}" class="flex items-center justify-between rounded-lg bg-slate-50 px-3 py-2 text-sm font-medium text-x2-primary hover:bg-slate-100 dark:bg-white/5">Quản lý thẻ ra vào <span aria-hidden>→</span></a>
            </div>
        </div>
    </div>
</x-filament-panels::page>
