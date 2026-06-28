@props([
    'label' => null,
    'value' => null,
    'sub' => null,
    'accent' => 'blue', // green|teal|amber|red|blue
    'trend' => null,     // e.g. '+1.2%'
    'trendUp' => true,
])

@php
    $accents = [
        'green' => 'bg-x2-green/10 text-x2-green',
        'teal' => 'bg-x2-teal/10 text-x2-teal',
        'amber' => 'bg-x2-amber/10 text-x2-amber',
        'red' => 'bg-x2-red/10 text-x2-red',
        'blue' => 'bg-x2-blue/10 text-x2-blue',
    ];
    $dot = $accents[$accent] ?? $accents['blue'];
@endphp

{{-- X2KpiCard — single KPI. value/label/sub passed in from query, never hardcoded. --}}
<div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
    <div class="flex items-start justify-between">
        <div>
            <div class="text-xs font-medium text-slate-500">{{ $label }}</div>
            <div class="mt-1 text-2xl font-bold tracking-tight text-slate-900">{{ $value }}</div>
        </div>
        <span class="grid h-9 w-9 place-items-center rounded-lg {{ $dot }}">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13l4-4 4 4 7-7"/></svg>
        </span>
    </div>
    <div class="mt-2 flex items-center gap-2">
        @if ($trend)
            <span @class([
                'inline-flex items-center gap-0.5 rounded px-1.5 py-0.5 text-[11px] font-semibold',
                'bg-x2-green/10 text-x2-green' => $trendUp,
                'bg-x2-red/10 text-x2-red' => ! $trendUp,
            ])>
                {{ $trendUp ? '▲' : '▼' }} {{ $trend }}
            </span>
        @endif
        @if ($sub)
            <span class="text-xs text-slate-500">{{ $sub }}</span>
        @endif
    </div>
</div>
