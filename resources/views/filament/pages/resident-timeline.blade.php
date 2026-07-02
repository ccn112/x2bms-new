@php
    $icons = [
        'user' => 'M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm-7 8a7 7 0 0 1 14 0',
        'home' => 'M3 9.5 12 3l9 6.5M5 10v9a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-9',
        'chat' => 'M8 10h8M8 14h5m-9 6 3.5-2.1A2 2 0 0 1 11 18h6a3 3 0 0 0 3-3V8a3 3 0 0 0-3-3H7a3 3 0 0 0-3 3v12Z',
    ];
    $iconBg = ['blue' => 'bg-blue-50 text-blue-500', 'green' => 'bg-green-50 text-green-500', 'amber' => 'bg-amber-50 text-amber-500'];
@endphp

<x-filament-panels::page>
    <x-x2.kpi-row :cols="4">
        @foreach ($kpis as $kpi)
            <x-x2.kpi-card :label="$kpi['label']" :value="$kpi['value']" :accent="$kpi['accent']" />
        @endforeach
    </x-x2.kpi-row>

    <div class="flex flex-wrap items-center gap-3">
        <div class="flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-1.5">
            <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.3-4.3M11 19a8 8 0 1 1 0-16 8 8 0 0 1 0 16Z"/></svg>
            <input type="text" wire:model.live.debounce.400ms="search" placeholder="Tìm theo tên cư dân…" class="w-56 border-0 p-0 text-sm focus:ring-0" />
        </div>
        <select wire:model.live="typeFilter" class="rounded-lg border-slate-200 text-sm focus:border-x2-primary focus:ring-x2-primary">
            <option value="all">Tất cả hoạt động</option>
            <option value="profile">Tạo hồ sơ</option>
            <option value="binding">Gắn căn hộ</option>
            <option value="feedback">Phản ánh</option>
        </select>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-gray-900">
        @forelse ($grouped as $day => $events)
            <div class="mb-6 last:mb-0">
                <div class="mb-3 flex items-center gap-2">
                    <span class="rounded-lg bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-500 dark:bg-white/10">{{ \Illuminate\Support\Carbon::parse($day)->format('d/m/Y') }}</span>
                    <span class="text-xs text-slate-400">{{ count($events) }} hoạt động</span>
                </div>
                <ol class="relative ml-3 space-y-4 border-l border-slate-100 pl-6 dark:border-white/10">
                    @foreach ($events as $e)
                        <li class="relative">
                            <span class="absolute -left-[34px] grid h-7 w-7 place-items-center rounded-full ring-4 ring-white dark:ring-gray-900 {{ $iconBg[$e['color']] ?? 'bg-slate-100 text-slate-500' }}">
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icons[$e['icon']] ?? $icons['user'] }}"/></svg>
                            </span>
                            <div class="flex flex-wrap items-baseline justify-between gap-x-3">
                                <div>
                                    <a href="{{ url('/admin/residents/'.$e['resident_id'].'/detail') }}" class="text-sm font-medium text-slate-800 hover:text-x2-primary dark:text-slate-100">{{ $e['resident'] }}</a>
                                    <span class="text-sm text-slate-500"> — {{ $e['title'] }}</span>
                                </div>
                                <span class="text-xs text-slate-400">{{ $e['date']->format('H:i') }}</span>
                            </div>
                            @if ($e['meta'])
                                <div class="text-xs text-slate-400">{{ $e['meta'] }}</div>
                            @endif
                        </li>
                    @endforeach
                </ol>
            </div>
        @empty
            <div class="py-16 text-center">
                <svg class="mx-auto h-12 w-12 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.4"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 2m6-2a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                <p class="mt-3 text-sm font-medium text-slate-500">Chưa có hoạt động</p>
                <p class="text-xs text-slate-400">Không có hoạt động cư dân phù hợp bộ lọc.</p>
            </div>
        @endforelse
    </div>
</x-filament-panels::page>
