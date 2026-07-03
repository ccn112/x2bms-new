@props([
    'label' => null,
    'value' => null,
    'sub' => null,                 // e.g. '100% tổng cư dân' or 'Hôm nay'
    'accent' => 'blue',            // blue|green|amber|red|teal|violet
    'icon' => 'heroicon-o-chart-bar',
    'trend' => null,               // e.g. '2,1%'
    'trendUp' => true,
    'trendNote' => 'so với tháng trước',
    'href' => null,                // shows "Xem chi tiết →" when set
    'loading' => false,
])

@php
    // DS-01 KPI card: context-wide total, compact enough for 5–6 per row. The number
    // uses Plus Jakarta Sans (.font-title). KPIs never react to table filters.
    $accents = [
        'blue'   => 'bg-x2-blue/10 text-x2-blue',
        'green'  => 'bg-x2-green/10 text-x2-green',
        'amber'  => 'bg-x2-amber/10 text-x2-amber',
        'red'    => 'bg-x2-red/10 text-x2-red',
        'teal'   => 'bg-x2-teal/10 text-x2-teal',
        'violet' => 'bg-x2-ai/10 text-x2-ai',
    ];
    $tint = $accents[$accent] ?? $accents['blue'];
@endphp

<div {{ $attributes->class(['flex flex-col rounded-xl border border-slate-200 bg-white p-4 shadow-sm']) }}>
    @if ($loading)
        <div class="animate-pulse space-y-3">
            <div class="h-3 w-20 rounded bg-slate-200"></div>
            <div class="h-7 w-24 rounded bg-slate-200"></div>
            <div class="h-3 w-28 rounded bg-slate-100"></div>
        </div>
    @else
        <div class="flex items-start justify-between">
            <div class="min-w-0">
                <div class="truncate text-xs font-medium text-slate-500">{{ $label }}</div>
                <div class="font-title mt-1 text-3xl font-bold tracking-tight text-slate-900">{{ $value }}</div>
            </div>
            <span class="grid h-10 w-10 shrink-0 place-items-center rounded-full {{ $tint }}">
                @svg($icon, 'h-5 w-5')
            </span>
        </div>

        <div class="mt-2 flex items-center gap-2">
            @if ($trend)
                <span @class([
                    'inline-flex items-center gap-0.5 text-xs font-semibold',
                    'text-x2-green' => $trendUp,
                    'text-x2-red' => ! $trendUp,
                ])>
                    {{ $trendUp ? '▲' : '▼' }} {{ $trend }}
                </span>
                @if ($trendNote)
                    <span class="text-xs text-slate-400">{{ $trendNote }}</span>
                @endif
            @elseif ($sub)
                <span class="text-xs text-slate-500">{{ $sub }}</span>
            @endif
        </div>

        @if ($href)
            <a href="{{ $href }}" class="mt-3 inline-flex items-center gap-1 text-xs font-semibold text-x2-primary hover:underline">
                Xem chi tiết
                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
            </a>
        @endif
    @endif
</div>
