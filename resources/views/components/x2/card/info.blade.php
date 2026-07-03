@props([
    'title' => null,
    'icon' => null,
    'padding' => true,
])

{{-- DS-01 info card — titled container for record detail blocks / dashboard panels.
     Header (optional icon + title) + right-aligned `actions` slot + body slot. --}}
<div {{ $attributes->class(['rounded-xl border border-slate-200 bg-white shadow-sm']) }}>
    @if ($title || isset($actions))
        <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3">
            <div class="flex items-center gap-2">
                @if ($icon)
                    <span class="text-slate-400">@svg($icon, 'h-4 w-4')</span>
                @endif
                <h3 class="font-title text-sm font-bold text-slate-900">{{ $title }}</h3>
            </div>
            @isset($actions)
                <div class="flex items-center gap-2">{{ $actions }}</div>
            @endisset
        </div>
    @endif
    <div @class(['px-4 py-3' => $padding])>
        {{ $slot }}
    </div>
</div>
