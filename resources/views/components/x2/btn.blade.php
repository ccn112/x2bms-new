@props([
    'variant' => 'outline', // primary(blue) | gold(brand CTA) | outline | danger | ghost
    'size' => 'md',         // sm | md
    'icon' => null,         // heroicon name, e.g. 'heroicon-m-plus'
    'iconRight' => null,
    'as' => 'button',       // button | a
    'disabled' => false,
    'loading' => false,
])

@php
    // DS-01 buttons: blue = primary system action, gold = brand/context create,
    // white/outline = secondary, red = danger. Height ~40px (--x2-button-height).
    $variants = [
        'primary' => 'bg-x2-primary text-white shadow-sm hover:bg-x2-primary-600 border border-transparent',
        'gold'    => 'bg-x2-gold text-white shadow-sm hover:bg-x2-gold-600 border border-transparent',
        'outline' => 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-50',
        'danger'  => 'bg-white text-x2-red border border-x2-red/30 hover:bg-x2-red/5',
        'ghost'   => 'bg-transparent text-slate-600 border border-transparent hover:bg-slate-100',
    ];
    $sizes = [
        'sm' => 'h-8 px-2.5 text-[13px] gap-1',
        'md' => 'h-10 px-3.5 text-sm gap-1.5',
    ];
    $cls = ($variants[$variant] ?? $variants['outline']).' '.($sizes[$size] ?? $sizes['md']);
    $tag = $as === 'a' ? 'a' : 'button';
@endphp

<{{ $tag }}
    {{ $attributes->class([
        'inline-flex items-center justify-center rounded-lg font-medium transition select-none',
        'font-semibold' => in_array($variant, ['primary', 'gold']),
        'opacity-50 pointer-events-none' => $disabled || $loading,
        $cls,
    ]) }}
    @if ($tag === 'button') type="{{ $attributes->get('type', 'button') }}" @disabled($disabled || $loading) @endif
>
    @if ($loading)
        <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/></svg>
    @elseif ($icon)
        @svg($icon, 'h-4 w-4 shrink-0')
    @endif
    <span>{{ $slot }}</span>
    @if ($iconRight)
        @svg($iconRight, 'h-4 w-4 shrink-0')
    @endif
</{{ $tag }}>
