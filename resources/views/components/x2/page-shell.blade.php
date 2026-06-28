@props([
    'sidebar' => null,
    'topbar' => null,
    'footer' => null,
])

{{-- X2PageShell — overall chrome: sidebar + (topbar / content / footer). No sample data. --}}
<div class="flex min-h-screen">
    @if ($sidebar)
        <aside class="hidden w-64 shrink-0 lg:block">
            {{ $sidebar }}
        </aside>
    @endif

    <div class="flex min-h-screen flex-1 flex-col">
        @if ($topbar)
            {{ $topbar }}
        @endif

        <main class="flex-1 space-y-5 p-5">
            {{ $slot }}
        </main>

        @if ($footer)
            {{ $footer }}
        @endif
    </div>
</div>
