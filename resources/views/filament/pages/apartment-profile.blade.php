<x-filament-panels::page>
    @php
        $money = fn ($v) => number_format((float) $v, 0, ',', '.').' đ';
        $dotColor = ['gray' => 'bg-slate-400', 'orange' => 'bg-amber-500', 'green' => 'bg-emerald-500', 'purple' => 'bg-violet-500', 'blue' => 'bg-blue-500'];
        $alertTone = [
            'amber' => ['bg-amber-50', 'text-amber-600', 'text-amber-700'],
            'green' => ['bg-emerald-50', 'text-emerald-600', 'text-emerald-700'],
            'blue' => ['bg-sky-50', 'text-sky-600', 'text-sky-700'],
        ];
        $tabs = [
            ['key' => 'info', 'label' => 'Thông tin căn hộ'],
            ['key' => 'residents', 'label' => 'Cư dân liên quan'],
            ['key' => 'assets', 'label' => 'Phương tiện & thẻ'],
            ['key' => 'finance', 'label' => 'Công nợ'],
            ['key' => 'feedback', 'label' => 'Phản ánh', 'count' => $fbOpen ?: null],
            ['key' => 'documents', 'label' => 'Tài liệu'],
            ['key' => 'history', 'label' => 'Lịch sử & audit'],
        ];
    @endphp

    <div class="x2-bql-page">
        {{-- ===== KPI strip (7 ô) ===== --}}
        <div class="grid grid-cols-2 gap-px overflow-hidden rounded-2xl border border-slate-200 bg-slate-200 shadow-sm sm:grid-cols-4 xl:grid-cols-7">
            @foreach ($kpis as $k)
                <div class="bg-white px-4 py-3">
                    <p class="text-xs text-slate-500">{{ $k['label'] }}</p>
                    @if ($k['badge'] ?? false)
                        <div class="mt-1"><x-x2.status-badge :label="$k['value']" :tone="$k['tone'] ?? 'slate'" /></div>
                    @else
                        <p class="mt-0.5 font-title text-lg font-bold {{ ($k['warn'] ?? false) ? 'text-x2-red' : 'text-slate-800' }}">{{ $k['value'] }}</p>
                    @endif
                    @if (! empty($k['sub']))<p class="truncate text-[11px] text-slate-400">{{ $k['sub'] }}</p>@endif
                    @if (! empty($k['link']))
                        <button type="button" wire:click="{{ $k['link'][0] }}('{{ $k['link'][1] }}')" class="mt-0.5 text-[11px] font-medium text-x2-primary hover:underline">{{ $k['link'][2] }}</button>
                    @endif
                </div>
            @endforeach
        </div>

        {{-- ===== Section tabs (7) ===== --}}
        <x-x2.page.tabs :tabs="$tabs" :active="$tab" wire="setTab" class="mt-4" />

        {{-- ================= THÔNG TIN CĂN HỘ ================= --}}
        @if ($tab === 'info')
            <div class="grid grid-cols-1 gap-4 xl:grid-cols-3">
                {{-- Col 1: Tổng quan --}}
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h3 class="mb-3 font-title text-base font-bold text-x2-navy">Tổng quan căn hộ</h3>
                    <div class="mb-4 aspect-video overflow-hidden rounded-xl bg-slate-100">
                        <div class="flex h-full w-full items-center justify-center text-slate-300">
                            <svg class="h-10 w-10" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15l4.5-4.5 3 3 6-6 4.5 4.5M3 19.5h18a.75.75 0 00.75-.75V5.25A.75.75 0 0021 4.5H3a.75.75 0 00-.75.75v13.5c0 .414.336.75.75.75z"/></svg>
                        </div>
                    </div>
                    <dl class="space-y-2 text-sm">
                        @foreach ($overview as [$l, $v])
                            <div class="flex justify-between gap-3"><dt class="text-slate-500">{{ $l }}</dt><dd class="text-right font-medium text-slate-800">{{ $v }}</dd></div>
                        @endforeach
                    </dl>
                    <div class="mt-3 flex items-center gap-2 rounded-lg bg-emerald-50 px-3 py-2 text-sm text-emerald-700">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Căn hộ đang hoạt động bình thường
                    </div>
                </div>

                {{-- Col 2: Thông tin chi tiết + công tơ --}}
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="mb-3 flex items-center justify-between">
                        <h3 class="font-title text-base font-bold text-x2-navy">Thông tin chi tiết</h3>
                        <x-x2.status-badge :label="$statusLabel" :tone="$statusTone" />
                    </div>
                    <dl class="divide-y divide-slate-50 text-sm">
                        @foreach ($detail as [$l, $v])
                            <div class="flex justify-between gap-3 py-1.5"><dt class="text-slate-500">{{ $l }}</dt><dd class="text-right font-medium text-slate-800">{{ $v }}</dd></div>
                        @endforeach
                        @foreach ($meters as [$l, $v])
                            <div class="flex items-center justify-between gap-3 py-1.5">
                                <dt class="text-slate-500">{{ $l }}</dt>
                                <dd class="flex items-center gap-1.5 text-right font-medium text-slate-800">
                                    {{ $v ?? '—' }}
                                    @if ($v)<svg class="h-4 w-4 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>@endif
                                </dd>
                            </div>
                        @endforeach
                        <div class="flex justify-between gap-3 py-1.5"><dt class="text-slate-500">Loại hợp đồng</dt><dd class="text-right font-medium text-slate-800">{{ $contractType }}</dd></div>
                        <div class="flex justify-between gap-3 py-1.5"><dt class="shrink-0 text-slate-500">Ghi chú</dt><dd class="text-right text-slate-600">{{ $note }}</dd></div>
                    </dl>
                </div>

                {{-- Col 3: Thông tin nhanh + Cảnh báo --}}
                <div class="space-y-4">
                    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                        <h3 class="mb-3 font-title text-base font-bold text-x2-navy">Thông tin nhanh</h3>
                        <dl class="space-y-2 text-sm">
                            @foreach ($quick as [$l, $v])
                                <div class="flex justify-between gap-3"><dt class="shrink-0 text-slate-500">{{ $l }}</dt><dd class="truncate text-right font-medium text-slate-800">{{ $v }}</dd></div>
                            @endforeach
                        </dl>
                    </div>
                    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                        <h3 class="mb-3 font-title text-base font-bold text-x2-navy">Cảnh báo & thông tin cần lưu ý</h3>
                        <ul class="space-y-2.5">
                            @foreach ($alerts as $al)
                                @php [$bg, $ic, $tx] = $alertTone[$al['tone']] ?? $alertTone['blue']; @endphp
                                <li class="flex items-start gap-3 rounded-lg {{ $bg }} px-3 py-2">
                                    <x-filament::icon :icon="$al['icon']" @class(['mt-0.5 h-5 w-5 shrink-0', $ic]) />
                                    <div class="min-w-0 flex-1">
                                        <div class="flex items-center justify-between gap-2">
                                            <p class="text-sm font-semibold {{ $tx }}">{{ $al['title'] }}</p>
                                            @if ($al['badge'])<span class="shrink-0 rounded-full bg-white/70 px-2 py-0.5 text-[11px] font-medium {{ $tx }}">{{ $al['badge'] }}</span>@endif
                                        </div>
                                        <p class="text-xs text-slate-500">{{ $al['detail'] }}</p>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>

            {{-- Bottom: Cư dân liên quan + Tài liệu --}}
            <div class="mt-4 grid grid-cols-1 gap-4 xl:grid-cols-2">
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="mb-3 flex items-center justify-between">
                        <h3 class="font-title text-base font-bold text-x2-navy">Cư dân liên quan ({{ count($residents) }})</h3>
                        <button type="button" wire:click="setTab('residents')" class="text-sm font-medium text-x2-primary hover:underline">Xem tất cả</button>
                    </div>
                    @include('filament.pages.partials.apartment-residents-table', ['residents' => $residents, 'compact' => true])
                </div>
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="mb-3 flex items-center justify-between">
                        <h3 class="font-title text-base font-bold text-x2-navy">Tài liệu liên quan ({{ count($documents) }})</h3>
                        <button type="button" wire:click="setTab('documents')" class="text-sm font-medium text-x2-primary hover:underline">Xem tất cả</button>
                    </div>
                    @include('filament.pages.partials.apartment-documents', ['documents' => array_slice($documents, 0, 3)])
                </div>
            </div>

        @elseif ($tab === 'residents')
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h3 class="mb-3 font-title text-base font-bold text-x2-navy">Cư dân liên quan ({{ count($residents) }})</h3>
                @include('filament.pages.partials.apartment-residents-table', ['residents' => $residents, 'compact' => false])
            </div>

        @elseif ($tab === 'assets')
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h3 class="mb-3 font-title text-base font-bold text-x2-navy">Phương tiện & thẻ ({{ $vehicles->count() + $cards->count() }})</h3>
                @include('filament.pages.partials.apartment-assets', ['vehicles' => $vehicles, 'cards' => $cards, 'compact' => false])
            </div>

        @elseif ($tab === 'finance')
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h3 class="mb-3 font-title text-base font-bold text-x2-navy">Công nợ</h3>
                <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                    @foreach ([['Tổng nợ', $money($debt['total']), $debt['total'] > 0 ? 'text-x2-red' : 'text-emerald-600'], ['Trong hạn', $money($debt['inTerm']), 'text-slate-800'], ['Quá hạn', $money($debt['overdue']), $debt['overdue'] > 0 ? 'text-x2-red' : 'text-emerald-600'], ['Số khoản quá hạn', $debt['overdueCount'], 'text-slate-800']] as [$l, $v, $c])
                        <div class="rounded-xl border border-slate-200 p-3">
                            <p class="text-xs text-slate-500">{{ $l }}</p>
                            <p class="mt-1 font-title text-lg font-bold {{ $c }}">{{ $v }}</p>
                        </div>
                    @endforeach
                </div>
                <p class="mt-3 text-sm text-slate-500">Ngày thanh toán gần nhất: <span class="font-medium text-slate-800">{{ $debt['lastPaid'] }}</span></p>
            </div>

        @elseif ($tab === 'feedback')
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h3 class="mb-3 font-title text-base font-bold text-x2-navy">Phản ánh ({{ $fbOpen }} đang mở)</h3>
                @include('filament.pages.partials.apartment-feedback', ['feedback' => $feedback])
            </div>

        @elseif ($tab === 'documents')
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h3 class="mb-3 font-title text-base font-bold text-x2-navy">Tài liệu liên quan ({{ count($documents) }})</h3>
                @include('filament.pages.partials.apartment-documents', ['documents' => $documents])
            </div>

        @elseif ($tab === 'history')
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h3 class="mb-3 font-title text-base font-bold text-x2-navy">Lịch sử & audit</h3>
                @include('filament.pages.partials.apartment-timeline', ['timeline' => $timeline, 'dotColor' => $dotColor])
            </div>
        @endif
    </div>
</x-filament-panels::page>
