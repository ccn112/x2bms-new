@php
    $items = [
        ['key' => 'foundations', 'label' => 'Nền tảng', 'url' => url('/sa/design-system')],
        ['key' => 'data-display', 'label' => 'KPI & Bảng', 'url' => url('/sa/design-system/data-display')],
        ['key' => 'buttons', 'label' => 'Nút & Hành động', 'url' => url('/sa/design-system/buttons')],
        ['key' => 'forms', 'label' => 'Form & Lọc', 'url' => url('/sa/design-system/forms')],
        ['key' => 'overlays', 'label' => 'Modal & AI', 'url' => url('/sa/design-system/overlays')],
        ['key' => 'records', 'label' => 'Tabs & Chi tiết', 'url' => url('/sa/design-system/records')],
    ];
    $active = $active ?? 'foundations';
@endphp

<div class="mb-5 flex flex-wrap items-center gap-1.5 rounded-xl border border-slate-200 bg-white p-1.5 shadow-sm">
    @foreach ($items as $it)
        <a href="{{ $it['url'] }}" @class([
            'rounded-lg px-3 py-1.5 text-sm transition',
            'font-title bg-x2-navy font-semibold text-white' => $it['key'] === $active,
            'font-medium text-slate-600 hover:bg-slate-100' => $it['key'] !== $active,
        ])>{{ $it['label'] }}</a>
    @endforeach
    <span class="ml-auto px-2 text-xs text-slate-400">X2-BMS Design System v1.0</span>
</div>
