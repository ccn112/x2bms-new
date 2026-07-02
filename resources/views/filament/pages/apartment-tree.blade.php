@php
    $statusMeta = \App\Filament\Pages\ApartmentTree::STATUS;
    $dot = ['green' => 'bg-x2-green', 'slate' => 'bg-slate-300', 'amber' => 'bg-x2-amber', 'blue' => 'bg-x2-blue'];
@endphp

<x-filament-panels::page>
    <x-x2.kpi-row :cols="4">
        @foreach ($kpis as $kpi)
            <x-x2.kpi-card :label="$kpi['label']" :value="$kpi['value']" :accent="$kpi['accent']" />
        @endforeach
    </x-x2.kpi-row>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-[280px_1fr]">
        {{-- Tree --}}
        <div class="rounded-2xl border border-slate-200 bg-white p-3 shadow-sm dark:border-white/10 dark:bg-gray-900">
            <div class="px-2 py-1.5 text-xs font-semibold uppercase tracking-wide text-slate-400">Tòa / Tầng</div>
            <ul class="space-y-0.5">
                @foreach ($tree as $b)
                    <li>
                        <button type="button" wire:click="selectBuilding({{ $b['id'] }})"
                            class="flex w-full items-center justify-between rounded-lg px-2.5 py-2 text-left text-sm font-medium transition hover:bg-slate-50 dark:hover:bg-white/5 {{ $buildingId === $b['id'] ? 'bg-x2-primary/5 text-x2-primary' : 'text-slate-700 dark:text-slate-200' }}">
                            <span class="flex items-center gap-2">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h6M9 12h6"/></svg>
                                {{ $b['name'] }}
                            </span>
                            <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs text-slate-500 dark:bg-white/10">{{ $b['apartments'] }}</span>
                        </button>
                        @if ($buildingId === $b['id'])
                            <ul class="ml-4 mt-0.5 space-y-0.5 border-l border-slate-100 pl-2 dark:border-white/10">
                                @foreach ($b['floors'] as $f)
                                    <li>
                                        <button type="button" wire:click="selectFloor({{ $b['id'] }}, {{ $f['id'] }})"
                                            class="flex w-full items-center justify-between rounded-lg px-2.5 py-1.5 text-left text-sm transition hover:bg-slate-50 dark:hover:bg-white/5 {{ $floorId === $f['id'] ? 'bg-x2-primary/5 font-medium text-x2-primary' : 'text-slate-600 dark:text-slate-300' }}">
                                            <span>{{ $f['name'] }}</span>
                                            <span class="text-xs text-slate-400">{{ $f['apartments'] }}</span>
                                        </button>
                                    </li>
                                @endforeach
                                @if (empty($b['floors']))
                                    <li class="px-2.5 py-1.5 text-xs text-slate-400">Chưa có tầng</li>
                                @endif
                            </ul>
                        @endif
                    </li>
                @endforeach
            </ul>
        </div>

        {{-- Apartment grid --}}
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-gray-900">
            <div class="mb-4 flex items-center justify-between">
                <h3 class="text-base font-semibold text-slate-900 dark:text-white">{{ $scopeLabel }}</h3>
                <span class="text-sm text-slate-400">{{ count($apartments) }} căn</span>
            </div>
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4 xl:grid-cols-6">
                @forelse ($apartments as $a)
                    @php [$sl, $st] = $statusMeta[$a['status']] ?? [$a['status'] ?? '—', 'slate']; @endphp
                    <a href="{{ url('/admin/apartments/'.$a['id'].'/profile') }}"
                        class="group rounded-xl border border-slate-200 bg-white p-3 transition hover:border-x2-primary hover:shadow-sm dark:border-white/10 dark:bg-gray-900">
                        <div class="flex items-center justify-between">
                            <span class="font-semibold text-slate-800 group-hover:text-x2-primary dark:text-slate-100">{{ $a['code'] }}</span>
                            <span class="h-2.5 w-2.5 rounded-full {{ $dot[$statusMeta[$a['status']][1] ?? 'slate'] ?? 'bg-slate-300' }}"></span>
                        </div>
                        <div class="mt-1 text-xs text-slate-400">{{ $a['type'] ?: '—' }}@if ($a['area']) · {{ rtrim(rtrim(number_format((float) $a['area'], 1), '0'), '.') }} m²@endif</div>
                        <div class="mt-1.5 text-xs font-medium text-slate-500">{{ $sl }}</div>
                    </a>
                @empty
                    <div class="col-span-full py-14 text-center text-sm text-slate-400">Không có căn hộ trong phạm vi đã chọn.</div>
                @endforelse
            </div>
        </div>
    </div>
</x-filament-panels::page>
