@props([
    'label' => null,     // e.g. 'Trạng thái'
    'value' => null,     // e.g. 'Tất cả'
    'removeWire' => null, // Livewire method to clear this filter
])

{{-- DS-01 active-filter chip — rendered in a row below the filter bar. --}}
<span {{ $attributes->class(['inline-flex items-center gap-1.5 rounded-full border border-slate-200 bg-slate-50 px-2.5 py-1 text-xs text-slate-600']) }}>
    @if ($label)<span class="text-slate-400">{{ $label }}:</span>@endif
    <span class="font-medium text-slate-700">{{ $value ?? $slot }}</span>
    @if ($removeWire)
        <button type="button" wire:click="{{ $removeWire }}" class="text-slate-400 hover:text-x2-red">
            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 6l12 12M18 6L6 18"/></svg>
        </button>
    @endif
</span>
