@props([
    'count' => 0,      // number of selected rows; bar shows only when > 0
    'label' => 'Đã chọn',
])

{{-- DS-01 bulk action bar — appears only when rows are selected. On mobile it becomes
     a sticky bottom bar (RESPONSIVE_RULES). Pass the selected count; put actions in slot. --}}
@if ((int) $count > 0)
    <div {{ $attributes->class([
        'flex flex-wrap items-center gap-3 rounded-xl border border-x2-primary/20 bg-x2-primary/5 px-4 py-2.5 shadow-sm',
        'max-lg:fixed max-lg:inset-x-3 max-lg:bottom-3 max-lg:z-40 max-lg:bg-white max-lg:shadow-2xl',
    ]) }}>
        <span class="text-sm font-semibold text-x2-primary">{{ $label }} {{ number_format((int) $count, 0, ',', '.') }}</span>
        <div class="flex flex-wrap items-center gap-2">
            {{ $slot }}
        </div>
    </div>
@endif
