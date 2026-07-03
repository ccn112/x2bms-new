@props([])

{{-- DS-01 page action cluster for pages WITHOUT tabs (right-aligned row of buttons).
     For pages with tabs, use the `actions` slot of <x-x2.page.tabs> instead so the
     actions sit inline with the tab row. Compose with <x-x2.btn>. --}}
<div {{ $attributes->class(['flex flex-wrap items-center justify-end gap-2']) }}>
    {{ $slot }}
</div>
