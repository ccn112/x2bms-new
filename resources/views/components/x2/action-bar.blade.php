@props([
    'title' => null,
    'subtitle' => null,
])

{{-- X2ActionBar — header row with title + action buttons (slot). Buttons should be policy-gated by caller. --}}
<div {{ $attributes->class(['flex flex-wrap items-center justify-between gap-3']) }}>
    <div>
        @if ($title)
            <h2 class="text-lg font-semibold text-slate-900">{{ $title }}</h2>
        @endif
        @if ($subtitle)
            <p class="text-sm text-slate-500">{{ $subtitle }}</p>
        @endif
    </div>
    <div class="flex items-center gap-2">
        {{ $slot }}
    </div>
</div>
