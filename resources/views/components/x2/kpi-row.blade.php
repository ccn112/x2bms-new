@props([
    'cols' => 6, // cards per row on desktop (xl). Owner rule 2026-07-02: keep the
                 // designed count — a 6-card row stays 6 at desktop, never auto-collapses to 2/3.
])

@php
    // Fixed desktop column count = the design's cards-per-row. Smaller breakpoints
    // wrap only because the viewport is genuinely too narrow, not to "simplify".
    $map = [
        2 => 'grid-cols-1 sm:grid-cols-2',
        3 => 'grid-cols-2 sm:grid-cols-3',
        4 => 'grid-cols-2 sm:grid-cols-2 xl:grid-cols-4',
        5 => 'grid-cols-2 sm:grid-cols-3 xl:grid-cols-5',
        6 => 'grid-cols-2 sm:grid-cols-3 xl:grid-cols-6',
        7 => 'grid-cols-2 sm:grid-cols-4 xl:grid-cols-7',
    ];
    $cls = $map[(int) $cols] ?? $map[6];
@endphp

{{-- X2KpiRow — the standard KPI/stat card row. Wrap <x-x2.kpi-card>s in this so the
     designed cards-per-row is preserved on desktop. Usage: <x-x2.kpi-row :cols="6"> --}}
<div {{ $attributes->class(['grid gap-4', $cls]) }}>
    {{ $slot }}
</div>
