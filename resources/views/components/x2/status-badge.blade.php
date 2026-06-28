@props([
    'status' => null,   // raw enum value
    'label' => null,    // display label (defaults to status)
    'tone' => 'slate',  // green|amber|red|blue|slate|teal
])

@php
    $tones = [
        'green' => 'bg-x2-green/10 text-x2-green ring-x2-green/20',
        'teal' => 'bg-x2-teal/10 text-x2-teal ring-x2-teal/20',
        'amber' => 'bg-x2-amber/10 text-x2-amber ring-x2-amber/20',
        'red' => 'bg-x2-red/10 text-x2-red ring-x2-red/20',
        'blue' => 'bg-x2-blue/10 text-x2-blue ring-x2-blue/20',
        'slate' => 'bg-slate-100 text-slate-600 ring-slate-200',
    ];
    $cls = $tones[$tone] ?? $tones['slate'];
@endphp

{{-- X2StatusBadge — colored pill. Tone resolved from a domain Enum by the caller. --}}
<span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[11px] font-medium ring-1 ring-inset {{ $cls }}">
    <span class="h-1.5 w-1.5 rounded-full bg-current"></span>
    {{ $label ?? $status }}
</span>
