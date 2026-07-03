@props([
    'advancedCount' => 0,   // badge on "Bộ lọc nâng cao"
    'advancedWire' => null, // Livewire method to toggle advanced filters
])

{{-- DS-01 filter toolbar — sits directly above the table/list and only affects rows
     (never the context-wide KPIs). Layout:
       [ saved-view ] [ search ..................] [ Bộ lọc nâng cao (n) ]   [ trailing: cột · mật độ · xuất ]
     Slots: `savedView`, `search`, `trailing`. Chips render below via <x-x2.filter.chip>. --}}
<div {{ $attributes->class(['flex flex-wrap items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2.5 shadow-sm']) }}>
    @isset($savedView)
        <div class="shrink-0">{{ $savedView }}</div>
    @endisset

    <div class="min-w-[200px] flex-1">
        {{ $search ?? '' }}
    </div>

    <button type="button"
        @if ($advancedWire) wire:click="{{ $advancedWire }}" @endif
        class="inline-flex shrink-0 items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50">
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3 4h18M6 8h12M9 12h6M11 16h2"/></svg>
        Bộ lọc nâng cao
        @if ((int) $advancedCount > 0)
            <span class="rounded-full bg-x2-primary px-1.5 text-[11px] font-semibold text-white">{{ $advancedCount }}</span>
        @endif
    </button>

    @isset($trailing)
        <div class="ml-auto flex shrink-0 items-center gap-2">{{ $trailing }}</div>
    @endisset
</div>
