@php
    $barColor = ['amber' => 'bg-amber-500', 'red' => 'bg-red-500', 'blue' => 'bg-blue-500', 'green' => 'bg-green-500'];
    // Tag tô theo mức rủi ro (RiskLevel::tone → green|amber|red|slate).
    $toneClass = [
        'red' => 'bg-red-50 text-red-600 dark:bg-red-500/10 dark:text-red-300',
        'amber' => 'bg-amber-50 text-amber-600 dark:bg-amber-500/10 dark:text-amber-300',
        'slate' => 'bg-slate-100 text-slate-500 dark:bg-white/10 dark:text-slate-300',
    ];
@endphp

<x-filament-panels::page>
    <x-x2.kpi-row :cols="6">
        @foreach ($kpis as $kpi)
            <x-x2.kpi-card :label="$kpi['label']" :value="$kpi['value']" :sub="$kpi['sub'] ?? null" :accent="$kpi['accent']" />
        @endforeach
    </x-x2.kpi-row>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-[360px_1fr]">
        {{-- Breakdown bars --}}
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-gray-900">
            <h3 class="text-base font-semibold text-slate-900 dark:text-white">Phân tích thiếu sót</h3>
            <p class="text-sm text-slate-500">Tỷ lệ bản ghi cần bổ sung theo tiêu chí</p>
            <div class="mt-5 space-y-4">
                @foreach ($breakdown as $b)
                    @php $pct = $b['total'] ? round($b['value'] / $b['total'] * 100) : 0; @endphp
                    <div>
                        <div class="mb-1 flex items-center justify-between text-sm">
                            <span class="text-slate-600 dark:text-slate-300">{{ $b['label'] }}</span>
                            <span class="font-semibold text-slate-800 dark:text-slate-100">{{ number_format($b['value']) }} <span class="text-xs font-normal text-slate-400">({{ $pct }}%)</span></span>
                        </div>
                        <div class="h-2 w-full overflow-hidden rounded-full bg-slate-100 dark:bg-white/10">
                            <div class="h-full rounded-full {{ $barColor[$b['color']] ?? 'bg-slate-400' }}" style="width: {{ $pct }}%"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Issue list --}}
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-white/10 dark:bg-gray-900">
            <div class="border-b border-slate-100 px-5 py-3 dark:border-white/10">
                <h3 class="text-base font-semibold text-slate-900 dark:text-white">Bản ghi cần xử lý ({{ count($issues) }})</h3>
                <p class="text-sm text-slate-500">Hồ sơ cư dân có ít nhất một vấn đề dữ liệu</p>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[640px] text-sm">
                    <thead>
                        <tr class="border-b border-slate-100 text-left text-xs uppercase tracking-wide text-slate-400 dark:border-white/10">
                            <th class="px-5 py-2.5 font-medium">Cư dân</th>
                            <th class="px-5 py-2.5 font-medium">Liên hệ</th>
                            <th class="px-5 py-2.5 font-medium">Vấn đề</th>
                            <th class="px-5 py-2.5 text-right font-medium">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50 dark:divide-white/5">
                        @forelse ($issues as $r)
                            <tr class="hover:bg-slate-50/70 dark:hover:bg-white/5">
                                <td class="px-5 py-3">
                                    <div class="font-medium text-slate-800 dark:text-slate-100">{{ $r['name'] }}</div>
                                    <div class="text-xs text-slate-400">{{ $r['code'] }}</div>
                                </td>
                                <td class="px-5 py-3 text-slate-600 dark:text-slate-300">
                                    <div>{{ $r['phone'] ?: '—' }}</div>
                                    <div class="text-xs text-slate-400">{{ $r['email'] ?: '—' }}</div>
                                </td>
                                <td class="px-5 py-3">
                                    <div class="flex flex-wrap gap-1">
                                        @foreach ($r['tags'] as $t)
                                            <span class="inline-flex rounded-md px-2 py-0.5 text-xs font-medium {{ $toneClass[$t['tone']] ?? $toneClass['slate'] }}">{{ $t['label'] }}</span>
                                        @endforeach
                                    </div>
                                </td>
                                <td class="px-5 py-3 text-right">
                                    <a href="{{ url('/admin/residents/'.$r['id'].'/detail') }}" class="inline-flex rounded-md border border-slate-200 px-2.5 py-1 text-xs font-medium text-slate-600 hover:bg-slate-50">Sửa hồ sơ</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-5 py-14 text-center text-sm text-slate-400">🎉 Dữ liệu cư dân đầy đủ — không có vấn đề nào.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-filament-panels::page>
