@props([
    'title' => null,
    'subtitle' => null, // WEB-UX: subtitle deprecated — page title lives in the header, no subtitle (owner decision 2026-07-02).
])

{{-- X2ActionBar — action-button row (slot). The page title now lives in the topbar
     header and subtitles are dropped to free vertical space; only pass $title for a
     genuine in-content SECTION heading, never to repeat the page title. --}}
<div {{ $attributes->class(['flex flex-wrap items-center justify-between gap-3']) }}>
    <div>
        @if ($title)
            <h2 class="text-lg font-semibold text-slate-900">{{ $title }}</h2>
        @endif
    </div>
    <div class="flex items-center gap-2">
        {{ $slot }}
    </div>
</div>
