@props([
    'tabs' => [],        // [ ['key'=>'all','label'=>'Tất cả','count'=>1269,'url'=>null], ... ]
    'active' => null,    // active tab key
    'wire' => null,      // Livewire method name for wire:click(key); null → use url
])

{{-- DS-01 refined page tab row (Phương án refined · v1.2.0). Section-title tabs on
     the LEFT sit on a full-width baseline; the active tab is blue + bold with a
     ROUNDED blue indicator flush on that baseline, so the content region below
     connects visually to the active tab. Page-level actions inline on the RIGHT.
     Usage: <x-x2.page.tabs :tabs="..." :active="$tab" wire="setTab"><x-slot:actions>…</x-slot></x-x2.page.tabs> --}}
<div {{ $attributes->class(['relative mb-4 flex flex-col gap-3 border-b border-slate-200 sm:flex-row sm:items-center sm:justify-between']) }}>
    <nav class="x2-tabnav flex items-center gap-1 overflow-x-auto">
        @foreach ($tabs as $t)
            @php
                $isActive = ($t['key'] ?? null) === $active;
                $base = 'group relative inline-flex items-center gap-1.5 whitespace-nowrap px-3.5 py-2.5 text-sm transition';
                $state = $isActive
                    ? 'font-title font-bold text-x2-primary'
                    : 'font-semibold text-slate-600 hover:text-slate-900';
            @endphp
            <{{ isset($t['url']) ? 'a' : 'button' }}
                @class([$base, $state])
                @if (isset($t['url'])) href="{{ $t['url'] }}"
                @elseif ($wire) type="button" wire:click="{{ $wire }}('{{ $t['key'] }}')" @endif
            >
                {{ $t['label'] }}
                @if (isset($t['count']) && $t['count'] !== null)
                    <span @class([
                        'rounded-full px-1.5 py-0.5 text-[11px] font-semibold',
                        'bg-x2-primary/10 text-x2-primary' => $isActive,
                        'bg-slate-100 text-slate-500' => ! $isActive,
                    ])>{{ number_format((int) $t['count'], 0, ',', '.') }}</span>
                @endif
                {{-- Refined active indicator: rounded blue bar sitting flush on the
                     full-width baseline (overlaps the wrapper border by 1px). --}}
                @if ($isActive)
                    <span class="pointer-events-none absolute inset-x-2.5 -bottom-px h-[3px] rounded-full bg-x2-primary"></span>
                @endif
            </{{ isset($t['url']) ? 'a' : 'button' }}>
        @endforeach
    </nav>

    @isset($actions)
        <div class="flex shrink-0 items-center gap-2 pb-2 sm:pb-0">
            {{ $actions }}
        </div>
    @endisset
</div>
