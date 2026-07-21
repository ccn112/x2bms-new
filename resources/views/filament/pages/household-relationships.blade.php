@php
    $roleMeta = [
        'owner' => ['Chủ sở hữu', 'bg-green-50 text-green-600'],
        'tenant' => ['Người thuê', 'bg-blue-50 text-blue-600'],
        'member' => ['Thành viên', 'bg-slate-100 text-slate-500'],
    ];
@endphp

<x-filament-panels::page>
    <x-x2.kpi-row :cols="4">
        @foreach ($kpis as $kpi)
            <x-x2.kpi-card :label="$kpi['label']" :value="$kpi['value']" :accent="$kpi['accent']" />
        @endforeach
    </x-x2.kpi-row>

    {{-- Filter bar --}}
    <div class="flex flex-wrap items-center gap-3">
        <div class="flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-1.5">
            <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.3-4.3M11 19a8 8 0 1 1 0-16 8 8 0 0 1 0 16Z"/></svg>
            <input type="text" wire:model.live.debounce.400ms="search" placeholder="Tìm mã căn hoặc tên cư dân…" class="w-64 border-0 p-0 text-sm focus:ring-0" />
        </div>
        <select wire:model.live="roleFilter" class="rounded-lg border-slate-200 text-sm focus:border-x2-primary focus:ring-x2-primary">
            <option value="all">Tất cả vai trò</option>
            <option value="owner">Chủ sở hữu</option>
            <option value="tenant">Người thuê</option>
            <option value="member">Thành viên</option>
        </select>
        <span class="text-sm text-slate-400">{{ number_format($apartmentsPage->total()) }} hộ</span>
    </div>

    {{-- Household cards --}}
    <div class="grid grid-cols-1 gap-4 lg:grid-cols-2 xl:grid-cols-3">
        @forelse ($households as $h)
            <div class="flex flex-col rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-white/10 dark:bg-gray-900">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3 dark:border-white/10">
                    <div class="flex items-center gap-2.5">
                        <span class="grid h-9 w-9 place-items-center rounded-lg bg-x2-primary/10 text-x2-primary">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7"><path stroke-linecap="round" stroke-linejoin="round" d="M3 9.5 12 3l9 6.5M5 10v9a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-9"/></svg>
                        </span>
                        <div>
                            <a href="{{ url('/admin/apartments/'.$h['apartment_id'].'/profile') }}" class="font-semibold text-slate-900 hover:text-x2-primary dark:text-white">{{ $h['code'] }}</a>
                            <div class="text-xs text-slate-400">{{ $h['building'] }}@if ($h['floor']) · {{ $h['floor'] }}@endif</div>
                        </div>
                    </div>
                    @if (! $h['has_head'])
                        <span class="inline-flex rounded-md bg-amber-50 px-2 py-0.5 text-xs font-medium text-amber-600">Chưa có chủ hộ</span>
                    @else
                        <span class="text-xs text-slate-400">{{ $h['count'] }} thành viên</span>
                    @endif
                </div>

                <ul class="mt-3 space-y-2.5">
                    @foreach ($h['members'] as $m)
                        @php [$rl, $rc] = $roleMeta[$m['role']] ?? ['—', 'bg-slate-100 text-slate-500']; @endphp
                        <li class="flex items-center gap-3">
                            <span class="grid h-8 w-8 shrink-0 place-items-center rounded-full bg-slate-100 text-xs font-bold text-slate-500 dark:bg-white/5">
                                {{ \Illuminate\Support\Str::of($m['name'])->explode(' ')->map(fn ($w) => mb_substr($w, 0, 1))->take(2)->implode('') }}
                            </span>
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-2">
                                    <a href="{{ url('/admin/residents/'.$m['resident_id'].'/detail') }}" class="truncate text-sm font-medium text-slate-800 hover:text-x2-primary dark:text-slate-100">{{ $m['name'] }}</a>
                                    @if ($m['is_primary'])
                                        <span class="inline-flex shrink-0 rounded bg-x2-gold/15 px-1.5 py-0.5 text-[10px] font-semibold text-x2-gold">Chủ hộ</span>
                                    @endif
                                </div>
                                <div class="text-xs text-slate-400">{{ $m['rel_to_head'] ?: '—' }}@if ($m['phone']) · {{ $m['phone'] }}@endif</div>
                            </div>
                            <span class="inline-flex shrink-0 rounded-md px-2 py-0.5 text-xs font-medium {{ $rc }}">{{ $rl }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        @empty
            <div class="col-span-full rounded-2xl border border-dashed border-slate-200 bg-white py-16 text-center dark:border-white/10 dark:bg-gray-900">
                <svg class="mx-auto h-12 w-12 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.4"><path stroke-linecap="round" stroke-linejoin="round" d="M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm-7 8a7 7 0 0 1 14 0"/></svg>
                <p class="mt-3 text-sm font-medium text-slate-500">Chưa có quan hệ hộ gia đình</p>
                <p class="text-xs text-slate-400">Gắn cư dân vào căn hộ để hình thành hộ gia đình.</p>
            </div>
        @endforelse
    </div>

    @if ($apartmentsPage->hasPages())
        <div class="mt-2">{{ $apartmentsPage->links() }}</div>
    @endif
</x-filament-panels::page>
