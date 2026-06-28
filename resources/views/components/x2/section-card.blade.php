@props([
    'title' => null,
    'subtitle' => null,
    'action' => null,
])

{{-- X2SectionCard — titled content panel. Body comes from slot. --}}
<section {{ $attributes->class(['rounded-xl border border-slate-200 bg-white shadow-sm']) }}>
    @if ($title || $action)
        <div class="flex items-center justify-between gap-3 border-b border-slate-100 px-4 py-3">
            <div>
                @if ($title)
                    <h3 class="text-sm font-semibold text-slate-800">{{ $title }}</h3>
                @endif
                @if ($subtitle)
                    <p class="text-xs text-slate-500">{{ $subtitle }}</p>
                @endif
            </div>
            @if ($action)
                <div class="shrink-0 text-sm">{{ $action }}</div>
            @endif
        </div>
    @endif

    <div class="p-4">
        {{ $slot }}
    </div>
</section>
