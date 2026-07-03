@props([
    'title' => null,
    'icon' => null,
    'padding' => true,
])

{{-- Info card — titled container for record detail blocks / dashboard panels.
     DS-02 spacing: card radius 16 (rounded-2xl), card padding 20–24px (px-5 py-4).
     Header (optional icon + title) + right-aligned `actions` slot + body slot. --}}
<div {{ $attributes->class(['rounded-2xl border border-slate-200 bg-white shadow-sm']) }}>
    @if ($title || isset($actions))
        <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
            <div class="flex items-center gap-2">
                @if ($icon)
                    <span class="text-slate-400">@svg($icon, 'h-[18px] w-[18px]')</span>
                @endif
                <h3 class="font-title text-[15px] font-bold text-slate-900">{{ $title }}</h3>
            </div>
            @isset($actions)
                <div class="flex items-center gap-2">{{ $actions }}</div>
            @endisset
        </div>
    @endif
    <div @class(['px-5 py-4' => $padding])>
        {{ $slot }}
    </div>
</div>
