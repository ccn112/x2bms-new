@props([
    'field',                 // Livewire property bound via wire:model.live
    'placeholder' => 'Tất cả', // shown as the empty option
    'options' => [],         // [value => label]
])

{{-- DS-01 inline business-filter select. Live-binds to a Livewire property so the
     Filament table query re-runs on change. Used inside <x-x2.filter.bar :inline>. --}}
<select wire:model.live="{{ $field }}"
    {{ $attributes->class(['h-9 shrink-0 rounded-lg border border-slate-200 bg-white px-2.5 pr-8 text-sm text-slate-700 focus:border-x2-primary focus:ring-0']) }}>
    <option value="">{{ $placeholder }}</option>
    @foreach ($options as $val => $label)
        <option value="{{ $val }}">{{ $label }}</option>
    @endforeach
</select>
