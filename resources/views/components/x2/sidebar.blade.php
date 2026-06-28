@props([
    'brand' => 'X2-BMS',
    'tagline' => null,
    'groups' => [], // [['label' => 'VẬN HÀNH', 'items' => [['label'=>..,'route'=>..,'active'=>bool,'badge'=>?int]]]]
])

{{-- X2Sidebar — branded left nav. Items passed in; no hardcoded menu data in markup. --}}
<div class="flex h-full flex-col bg-x2-navy text-slate-200">
    <div class="flex items-center gap-2 px-5 py-4">
        <span class="grid h-8 w-8 place-items-center rounded-lg bg-x2-primary text-sm font-bold text-white">X2</span>
        <div class="leading-tight">
            <div class="text-base font-bold text-white">{{ $brand }}</div>
            @if ($tagline)
                <div class="text-[10px] uppercase tracking-wide text-slate-400">{{ $tagline }}</div>
            @endif
        </div>
    </div>

    <nav class="flex-1 overflow-y-auto px-3 pb-6">
        @foreach ($groups as $group)
            @if (!empty($group['label']))
                <div class="px-2 pb-1 pt-4 text-[10px] font-semibold uppercase tracking-wider text-slate-400">
                    {{ $group['label'] }}
                </div>
            @endif
            <ul class="space-y-0.5">
                @foreach ($group['items'] ?? [] as $item)
                    <li>
                        <a href="{{ $item['route'] ?? '#' }}"
                           @class([
                               'flex items-center gap-3 rounded-lg px-3 py-2 text-sm transition',
                               'bg-x2-primary text-white font-medium shadow' => $item['active'] ?? false,
                               'text-slate-300 hover:bg-x2-navy-600 hover:text-white' => !($item['active'] ?? false),
                           ])>
                            <span class="grid h-5 w-5 place-items-center text-current">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                    <circle cx="12" cy="12" r="8" />
                                </svg>
                            </span>
                            <span class="flex-1 truncate">{{ $item['label'] }}</span>
                            @if (!empty($item['badge']))
                                <span class="rounded-full bg-x2-red px-1.5 py-0.5 text-[10px] font-semibold text-white">
                                    {{ $item['badge'] }}
                                </span>
                            @endif
                        </a>
                    </li>
                @endforeach
            </ul>
        @endforeach
    </nav>
</div>
