@props([
    'tabs' => [],        // [ ['key'=>'all','label'=>'Tất cả','count'=>1269,'url'=>null], ... ]
    'active' => null,    // active tab key
    'wire' => null,      // Livewire method name for wire:click(key); null → use url
])

{{-- DS-01 page tab row. Tabs are major section titles (bold, blue underline when
     active) on the LEFT; page-level actions are inline on the RIGHT (same row).
     Usage: <x-x2.page.tabs :tabs="..." :active="$tab" wire="setTab"><x-slot:actions>…</x-slot></x-x2.page.tabs> --}}
<div {{ $attributes->class(['mb-4 flex flex-col gap-3 border-b border-slate-200 sm:flex-row sm:items-center sm:justify-between']) }}>
    <nav class="-mb-px flex items-center gap-1 overflow-x-auto">
        @foreach ($tabs as $t)
            @php
                $isActive = ($t['key'] ?? null) === $active;
                $base = 'group inline-flex items-center gap-1.5 whitespace-nowrap border-b-2 px-3 py-2.5 text-sm transition';
                $state = $isActive
                    ? 'border-x2-primary font-title font-bold text-x2-primary'
                    : 'border-transparent font-medium text-slate-500 hover:text-slate-800 hover:border-slate-300';
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
            </{{ isset($t['url']) ? 'a' : 'button' }}>
        @endforeach
    </nav>

    @isset($actions)
        <div class="flex shrink-0 items-center gap-2 pb-2 sm:pb-0">
            {{ $actions }}
        </div>
    @endisset
</div>
