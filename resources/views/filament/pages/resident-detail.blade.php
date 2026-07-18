<x-filament-panels::page>
    @php
        $money = fn ($v) => number_format((float) $v, 0, ',', '.').' đ';
        $dotColor = ['gray' => 'bg-slate-400', 'orange' => 'bg-amber-500', 'green' => 'bg-emerald-500', 'purple' => 'bg-violet-500', 'blue' => 'bg-blue-500'];
        $aiTone = [
            'amber' => ['bg-amber-50', 'text-amber-600', 'text-amber-700'],
            'green' => ['bg-emerald-50', 'text-emerald-600', 'text-emerald-700'],
            'blue' => ['bg-sky-50', 'text-sky-600', 'text-sky-700'],
        ];
        $tabs = [
            ['key' => 'overview', 'label' => 'Hồ sơ tổng quan'],
            ['key' => 'apartment', 'label' => 'Căn hộ'],
            ['key' => 'assets', 'label' => 'Phương tiện & thẻ'],
            ['key' => 'finance', 'label' => 'Công nợ'],
            ['key' => 'feedback', 'label' => 'Phản ánh', 'count' => $fbOpen ?: null],
            ['key' => 'history', 'label' => 'Nhật ký'],
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

        {{-- ===== Section tabs (6) ===== --}}
        <x-x2.page.tabs :tabs="$tabs" :active="$tab" wire="setTab" class="mt-4" />

        {{-- ================= HỒ SƠ TỔNG QUAN ================= --}}
        @if ($tab === 'overview')
            <div class="grid grid-cols-1 gap-4 xl:grid-cols-3">
                {{-- Col 1: Hồ sơ cư dân --}}
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex flex-col items-center text-center">
                        <img src="{{ $r->avatar_url }}" alt="{{ $r->full_name }}" class="h-24 w-24 rounded-full object-cover ring-2 ring-slate-100" />
                        <h3 class="mt-3 font-title text-base font-bold text-x2-navy">{{ $r->full_name }}</h3>
                        <div class="mt-1"><x-x2.status-badge :label="'Tài khoản '.mb_strtolower($statusLabel)" :tone="$statusTone" /></div>
                        <p class="mt-2 text-xs text-slate-500">Mã cư dân</p>
                        <p class="font-mono text-sm font-semibold text-slate-800">{{ $r->code ?? '—' }}</p>
                    </div>
                    <dl class="mt-4 space-y-2 border-t border-slate-100 pt-4 text-sm">
                        @if ($apartment)
                            <div class="flex justify-between gap-3">
                                <dt class="text-slate-500">Căn hộ liên kết</dt>
                                <dd class="text-right"><a href="{{ url('/admin/apartments/'.$apartment->id.'/profile') }}" class="font-medium text-x2-primary hover:underline">{{ $apartment->code }} – {{ $apartment->building?->name }}</a></dd>
                            </div>
                        @endif
                        @foreach ($quick as [$l, $v])
                            <div class="flex justify-between gap-3"><dt class="shrink-0 text-slate-500">{{ $l }}</dt><dd class="truncate text-right font-medium text-slate-800">{{ $v }}</dd></div>
                        @endforeach
                    </dl>
                </div>

                {{-- Col 2: Thông tin cá nhân --}}
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm xl:col-span-2">
                    <h3 class="mb-3 font-title text-base font-bold text-x2-navy">Thông tin cá nhân</h3>
                    <dl class="grid grid-cols-1 gap-x-8 gap-y-1.5 text-sm sm:grid-cols-2">
                        @foreach ($personal as $row)
                            <div class="flex justify-between gap-3 py-1">
                                <dt class="shrink-0 text-slate-500">{{ $row[0] }}</dt>
                                <dd class="flex items-center gap-1.5 text-right font-medium text-slate-800">
                                    {{ $row[1] }}
                                    @if (($row[2] ?? false) === true)
                                        <svg class="h-4 w-4 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    @endif
                                </dd>
                            </div>
                        @endforeach
                    </dl>
                </div>
            </div>

            {{-- Row 2: Căn hộ liên kết + Snapshot phí & công nợ --}}
            <div class="mt-4 grid grid-cols-1 gap-4 xl:grid-cols-3">
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm xl:col-span-2">
                    <div class="mb-3 flex items-center justify-between">
                        <h3 class="font-title text-base font-bold text-x2-navy">Thông tin căn hộ liên kết</h3>
                        @if ($apartment)<button type="button" wire:click="setTab('apartment')" class="text-sm font-medium text-x2-primary hover:underline">Xem chi tiết</button>@endif
                    </div>
                    @if ($apartment)
                        <dl class="grid grid-cols-1 gap-x-8 gap-y-1.5 text-sm sm:grid-cols-2">
                            @foreach ($apartmentInfo as [$l, $v])
                                <div class="flex justify-between gap-3 py-1"><dt class="text-slate-500">{{ $l }}</dt><dd class="text-right font-medium text-slate-800">{{ $v }}</dd></div>
                            @endforeach
                        </dl>
                    @else
                        <p class="text-sm text-slate-400">Cư dân chưa được gắn vào căn hộ nào.</p>
                    @endif
                </div>

                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h3 class="mb-3 font-title text-base font-bold text-x2-navy">Snapshot phí & công nợ</h3>
                    <div class="grid grid-cols-2 gap-3">
                        <div class="rounded-xl border border-slate-100 p-3"><p class="text-xs text-slate-500">Phí quản lý tháng này</p><p class="mt-1 font-title text-base font-bold text-slate-800">{{ $money($finance['feeThisMonth']) }}</p></div>
                        <div class="rounded-xl border border-slate-100 p-3"><p class="text-xs text-slate-500">Công nợ hiện tại</p><p class="mt-1 font-title text-base font-bold {{ $finance['currentDebt'] > 0 ? 'text-x2-red' : 'text-emerald-600' }}">{{ $money($finance['currentDebt']) }}</p></div>
                        <div class="rounded-xl border border-slate-100 p-3"><p class="text-xs text-slate-500">Tổng đã thanh toán</p><p class="mt-1 font-title text-base font-bold text-slate-800">{{ $money($finance['totalPaid']) }}</p></div>
                        <div class="rounded-xl border border-slate-100 p-3"><p class="text-xs text-slate-500">Kỳ thanh toán tiếp theo</p><p class="mt-1 font-title text-base font-bold text-slate-800">{{ $finance['nextDue'] }}</p>@if ($finance['nextDays'] !== null)<p class="text-[11px] text-slate-400">Còn {{ $finance['nextDays'] }} ngày</p>@endif</div>
                    </div>
                    <button type="button" wire:click="setTab('finance')" class="mt-3 w-full rounded-lg bg-slate-50 py-2 text-sm font-medium text-x2-primary hover:bg-slate-100">Xem chi tiết công nợ</button>
                </div>
            </div>

            {{-- Row 3: Thành viên hộ + Phương tiện & thẻ + AI --}}
            <div class="mt-4 grid grid-cols-1 gap-4 xl:grid-cols-3">
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm xl:col-span-2">
                    <div class="mb-3 flex items-center justify-between">
                        <h3 class="font-title text-base font-bold text-x2-navy">Thành viên hộ gia đình ({{ count($members) }})</h3>
                        <button type="button" wire:click="setTab('apartment')" class="text-sm font-medium text-x2-primary hover:underline">Xem tất cả</button>
                    </div>
                    @include('filament.pages.partials.apartment-residents-table', ['residents' => $members, 'compact' => true])

                    <h3 class="mb-3 mt-5 font-title text-base font-bold text-x2-navy">Phương tiện & thẻ đang dùng ({{ $vehicles->count() + $cards->count() }})</h3>
                    @include('filament.pages.partials.apartment-assets', ['vehicles' => $vehicles, 'cards' => $cards, 'compact' => true])
                </div>

                {{-- AI rule-based --}}
                <div class="rounded-2xl border border-violet-200 bg-violet-50/40 p-5 shadow-sm">
                    <h3 class="mb-3 flex items-center gap-2 font-title text-base font-bold text-x2-navy">
                        <svg class="h-5 w-5 text-violet-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09Z"/></svg>
                        Gợi ý từ AI
                    </h3>
                    <p class="mb-2 text-xs text-slate-500">AI phát hiện {{ count($aiSuggestions) }} gợi ý cho cư dân này:</p>
                    <ul class="space-y-2.5">
                        @foreach ($aiSuggestions as $s)
                            @php [$bg, $ic, $tx] = $aiTone[$s['tone']] ?? $aiTone['blue']; @endphp
                            <li class="rounded-lg {{ $bg }} px-3 py-2">
                                <p class="text-sm font-semibold {{ $tx }}">{{ $s['title'] }}</p>
                                <p class="text-xs text-slate-500">{{ $s['detail'] }}</p>
                            </li>
                        @endforeach
                    </ul>
                    <p class="mt-3 text-[11px] text-slate-400">Dữ liệu được phân tích lúc {{ now()->format('d/m/Y H:i') }}</p>
                </div>
            </div>

        {{-- ================= CĂN HỘ ================= --}}
        @elseif ($tab === 'apartment')
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="mb-3 flex items-center justify-between">
                    <h3 class="font-title text-base font-bold text-x2-navy">Căn hộ liên kết</h3>
                    @if ($apartment)<a href="{{ url('/admin/apartments/'.$apartment->id.'/profile') }}" class="text-sm font-medium text-x2-primary hover:underline">Mở hồ sơ căn hộ →</a>@endif
                </div>
                @if ($apartment)
                    <dl class="grid grid-cols-1 gap-x-8 gap-y-1.5 text-sm sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($apartmentInfo as [$l, $v])
                            <div class="flex justify-between gap-3 py-1.5 border-b border-slate-50"><dt class="text-slate-500">{{ $l }}</dt><dd class="text-right font-medium text-slate-800">{{ $v }}</dd></div>
                        @endforeach
                    </dl>
                @else
                    <p class="text-sm text-slate-400">Cư dân chưa được gắn vào căn hộ nào.</p>
                @endif
            </div>
            <div class="mt-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h3 class="mb-3 font-title text-base font-bold text-x2-navy">Thành viên hộ gia đình ({{ count($members) }})</h3>
                @include('filament.pages.partials.apartment-residents-table', ['residents' => $members, 'compact' => false])
            </div>

        {{-- ================= PHƯƠNG TIỆN & THẺ ================= --}}
        @elseif ($tab === 'assets')
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h3 class="mb-3 font-title text-base font-bold text-x2-navy">Phương tiện & thẻ ({{ $vehicles->count() + $cards->count() }})</h3>
                @include('filament.pages.partials.apartment-assets', ['vehicles' => $vehicles, 'cards' => $cards, 'compact' => false])
            </div>

        {{-- ================= CÔNG NỢ ================= --}}
        @elseif ($tab === 'finance')
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h3 class="mb-3 font-title text-base font-bold text-x2-navy">Công nợ</h3>
                <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                    @foreach ([
                        ['Phí tháng này', $money($finance['feeThisMonth']), 'text-slate-800'],
                        ['Công nợ hiện tại', $money($finance['currentDebt']), $finance['currentDebt'] > 0 ? 'text-x2-red' : 'text-emerald-600'],
                        ['Tổng đã thanh toán', $money($finance['totalPaid']), 'text-slate-800'],
                        ['Số khoản quá hạn', $finance['debtCount'], 'text-slate-800'],
                    ] as [$l, $v, $c])
                        <div class="rounded-xl border border-slate-200 p-3">
                            <p class="text-xs text-slate-500">{{ $l }}</p>
                            <p class="mt-1 font-title text-lg font-bold {{ $c }}">{{ $v }}</p>
                        </div>
                    @endforeach
                </div>
                <p class="mt-3 text-sm text-slate-500">Tổng đã phát hành: <span class="font-medium text-slate-800">{{ $money($finance['totalBilled']) }}</span> · Kỳ thanh toán tiếp theo: <span class="font-medium text-slate-800">{{ $finance['nextDue'] }}</span></p>
            </div>

        {{-- ================= PHẢN ÁNH ================= --}}
        @elseif ($tab === 'feedback')
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h3 class="mb-3 font-title text-base font-bold text-x2-navy">Phản ánh ({{ $fbOpen }} đang mở)</h3>
                @include('filament.pages.partials.apartment-feedback', ['feedback' => $feedback])
            </div>

        {{-- ================= NHẬT KÝ ================= --}}
        @elseif ($tab === 'history')
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h3 class="mb-3 font-title text-base font-bold text-x2-navy">Nhật ký & sự kiện</h3>
                @include('filament.pages.partials.apartment-timeline', ['timeline' => $timeline, 'dotColor' => $dotColor])
            </div>
        @endif
    </div>
</x-filament-panels::page>
