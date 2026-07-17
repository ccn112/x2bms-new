<x-filament-panels::page>
    @php
        $cellTone = [
            'green' => ['bg-emerald-50', 'border-emerald-200', 'text-emerald-700', 'bg-emerald-500'],
            'slate' => ['bg-white', 'border-slate-200', 'text-slate-500', 'bg-slate-300'],
            'amber' => ['bg-amber-50', 'border-amber-200', 'text-amber-700', 'bg-amber-500'],
            'violet' => ['bg-violet-50', 'border-violet-200', 'text-violet-700', 'bg-violet-500'],
            'red' => ['bg-red-50', 'border-red-200', 'text-red-700', 'bg-red-500'],
            'blue' => ['bg-sky-50', 'border-sky-200', 'text-sky-700', 'bg-sky-500'],
        ];
    @endphp

    {{-- Shell 2 khung cố định chiều cao viewport; mỗi khung scroll dọc riêng (lg+). --}}
    <div class="x2-bql-page">
    <div class="grid grid-cols-1 gap-4 lg:grid-cols-[280px_minmax(0,1fr)] lg:h-[calc(100dvh_-_11rem)]">
        {{-- ================= KHUNG TRÁI: cây lọc theo tầng ================= --}}
        <aside class="min-h-0">
            <div class="flex h-full flex-col rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="shrink-0 border-b border-slate-100 p-4">
                    <label class="mb-1 block text-xs font-semibold text-slate-500">Dự án</label>
                    <div class="mb-3 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-medium text-slate-700">{{ $projectName }}</div>
                    <label class="mb-1 block text-xs font-semibold text-slate-500">Tòa nhà</label>
                    <select x-on:change="$wire.selectBuilding(+$event.target.value)"
                        class="h-9 w-full rounded-lg border border-slate-200 bg-white px-2.5 text-sm text-slate-700 focus:border-x2-primary focus:ring-0">
                        @foreach ($buildings as $b)
                            <option value="{{ $b['id'] }}" @selected($b['id'] === $buildingId)>{{ $b['name'] }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Cây điều hướng — vùng scroll --}}
                <div class="min-h-0 flex-1 overflow-y-auto px-3 py-3 text-sm">
                    <div class="flex items-center gap-1.5 py-1 font-medium text-slate-700">
                        <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.75V12A2.25 2.25 0 014.5 9.75h15A2.25 2.25 0 0121.75 12v.75m-8.69-6.44l-2.12-2.12a1.5 1.5 0 00-1.061-.44H4.5A2.25 2.25 0 002.25 6v12a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9a2.25 2.25 0 00-2.25-2.25h-5.379a1.5 1.5 0 01-1.06-.44z"/></svg>
                        {{ $projectName }}
                    </div>
                    @foreach ($buildings as $b)
                        <button type="button" wire:click="selectBuilding({{ $b['id'] }})"
                            @class([
                                'flex w-full items-center gap-1.5 rounded-md py-1 pl-4 pr-2 text-left transition',
                                'font-semibold text-x2-primary' => $b['id'] === $buildingId,
                                'text-slate-600 hover:bg-slate-50' => $b['id'] !== $buildingId,
                            ])>
                            <svg class="h-4 w-4 shrink-0 opacity-70" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h6M9 10.5h6M9 14.25h6"/></svg>
                            <span class="truncate">{{ $b['name'] }}</span>
                            <span class="ml-auto shrink-0 text-xs text-slate-400">{{ $b['floorCount'] }} tầng</span>
                        </button>
                        @if ($b['id'] === $buildingId)
                            @foreach ($treeFloors as $f)
                                <button type="button" wire:click="toggleFloor({{ $f['id'] }})"
                                    @class(['flex w-full items-center gap-1 rounded-md py-1 pl-8 pr-2 text-left text-slate-600 hover:bg-slate-50', 'font-semibold text-x2-primary' => $f['expanded']])>
                                    <svg @class(['h-3.5 w-3.5 shrink-0 transition', 'rotate-90' => $f['expanded']]) fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
                                    {{ $f['name'] }}
                                </button>
                                @if ($f['expanded'])
                                    @foreach ($f['units'] as $u)
                                        <button type="button" wire:click="selectApartment({{ $u['id'] }})"
                                            @class([
                                                'flex w-full items-center gap-1.5 rounded-md py-1 pl-[3.25rem] pr-2 text-left transition',
                                                'bg-x2-primary/10 font-semibold text-x2-primary' => $u['id'] === $this->apartmentId,
                                                'text-slate-500 hover:bg-slate-50' => $u['id'] !== $this->apartmentId,
                                            ])>
                                            <span class="h-1 w-1 rounded-full bg-current opacity-50"></span>{{ $u['code'] }}
                                        </button>
                                    @endforeach
                                @endif
                            @endforeach
                        @endif
                    @endforeach
                </div>

                <div class="shrink-0 border-t border-slate-100 px-4 py-2 text-[11px] text-slate-400">Cập nhật lần cuối: {{ now()->format('d/m/Y H:i') }}</div>
            </div>
        </aside>

        {{-- ================= KHUNG PHẢI: danh sách / layout + chi tiết ================= --}}
        <div class="flex min-h-0 flex-col gap-4 lg:h-full">
            <div class="flex min-h-0 flex-1 flex-col rounded-2xl border border-slate-200 bg-white shadow-sm">
                {{-- Header cố định --}}
                <div class="shrink-0 border-b border-slate-100 p-5 pb-3">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <h2 class="font-title text-lg font-bold text-x2-navy">{{ $buildingName }}</h2>
                            <div class="inline-flex rounded-lg border border-slate-200 p-0.5">
                                <button type="button" wire:click="setView('list')"
                                    @class(['rounded-md px-3 py-1 text-xs font-semibold transition', 'bg-x2-primary text-white' => $viewMode === 'list', 'text-slate-500 hover:text-slate-800' => $viewMode !== 'list'])>Danh sách</button>
                                <button type="button" @if ($hasLayout) wire:click="setView('layout')" @else disabled title="Tòa này chưa có bản layout mặt bằng" @endif
                                    @class([
                                        'rounded-md px-3 py-1 text-xs font-semibold transition',
                                        'bg-x2-primary text-white' => $viewMode === 'layout',
                                        'text-slate-500 hover:text-slate-800' => $viewMode === 'list' && $hasLayout,
                                        'cursor-not-allowed text-slate-300' => ! $hasLayout,
                                    ])>Layout mặt bằng</button>
                            </div>
                        </div>
                        <div class="flex flex-wrap items-center gap-3 text-xs text-slate-500">
                            @foreach ($legend as [$label, $color])
                                <span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full {{ $color }}"></span>{{ $label }}</span>
                            @endforeach
                        </div>
                    </div>
                </div>

                {{-- Nội dung — vùng scroll dọc --}}
                <div class="min-h-0 flex-1 overflow-y-auto p-5 pt-4">
                    @if ($viewMode === 'layout')
                        <div class="grid h-full place-items-center rounded-xl border border-dashed border-slate-200 text-center text-sm text-slate-400">
                            Tòa này chưa có bản layout mặt bằng. Tải ảnh mặt bằng + gắn hotspot ở đợt sau.
                        </div>
                    @else
                        @forelse ($grid as $row)
                            <div class="mb-4">
                                <div class="mb-1.5 text-sm font-semibold text-slate-600">{{ $row['floor'] }}</div>
                                <div class="flex flex-wrap gap-2">
                                    @foreach ($row['units'] as $u)
                                        @php [$bg, $bd, $tx, $dot] = $cellTone[$u['tone']] ?? $cellTone['slate']; @endphp
                                        <button type="button" wire:click="selectApartment({{ $u['id'] }})"
                                            @class([
                                                'flex w-44 flex-col rounded-lg border px-3 py-2 text-left transition hover:shadow-sm',
                                                $bg, $bd,
                                                'ring-2 ring-x2-primary ring-offset-1' => $u['id'] === $this->apartmentId,
                                            ])>
                                            <div class="flex items-center justify-between gap-2">
                                                <span class="text-sm font-semibold text-slate-800">{{ $u['code'] }}</span>
                                                <span class="text-xs text-slate-500">{{ $u['area'] ?? '—' }}</span>
                                            </div>
                                            {{-- Màu ô = trạng thái; luôn hiện chủ hộ (chưa có cư dân → "Chưa gắn") --}}
                                            <div class="mt-1 flex items-center gap-1 truncate text-xs {{ $u['owner'] ? 'text-slate-600' : 'italic text-slate-400' }}" title="{{ $u['owner'] ?? 'Chưa gắn' }}">
                                                <svg class="h-3 w-3 shrink-0 opacity-60" fill="currentColor" viewBox="0 0 24 24"><path d="M12 12a5 5 0 100-10 5 5 0 000 10zm0 2c-5 0-9 2.5-9 6v2h18v-2c0-3.5-4-6-9-6z"/></svg>
                                                <span class="truncate">{{ $u['owner'] ?? 'Chưa gắn' }}</span>
                                            </div>
                                        </button>
                                    @endforeach
                                </div>
                            </div>
                        @empty
                            <p class="py-8 text-center text-sm text-slate-400">Chọn tòa nhà để xem danh sách căn theo tầng.</p>
                        @endforelse
                    @endif
                </div>
            </div>

            {{-- Panel chi tiết căn đang chọn — ghim đáy khung phải, tự scroll nếu dài --}}
            @if ($selected)
                <div class="shrink-0 overflow-y-auto rounded-2xl border border-slate-200 bg-white p-5 shadow-sm lg:max-h-[42%]">
                    <div class="mb-3 flex items-center justify-between gap-3">
                        <div class="flex items-center gap-2">
                            <h3 class="font-title text-lg font-bold text-x2-navy">{{ $selected['code'] }}</h3>
                            <x-x2.status-badge :label="$selected['statusLabel']" :tone="$selected['statusTone'] === 'violet' ? 'blue' : $selected['statusTone']" />
                        </div>
                        <button type="button" wire:click="selectApartment({{ $selected['id'] }})" class="text-slate-400 hover:text-slate-700">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 6l12 12M18 6L6 18"/></svg>
                        </button>
                    </div>
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-5">
                        <div class="text-sm">
                            <p class="mb-1.5 text-xs font-semibold text-slate-500">Thông tin căn</p>
                            <dl class="space-y-1 text-slate-600">
                                <div>{{ $selected['building'] }} · {{ $selected['floor'] }}</div>
                                <div>Loại: <span class="font-medium text-slate-800">{{ $selected['type'] ?? '—' }}</span></div>
                                <div>Diện tích: <span class="font-medium text-slate-800">{{ $selected['area'] }}</span></div>
                                <div>Hướng: <span class="font-medium text-slate-800">{{ $selected['direction'] }}</span></div>
                            </dl>
                        </div>
                        <div class="text-sm xl:col-span-2">
                            <p class="mb-1.5 text-xs font-semibold text-slate-500">Cư dân gắn với căn ({{ count($selected['residents']) }})</p>
                            @forelse ($selected['residents'] as $r)
                                <div class="mb-1.5">
                                    <a href="{{ url('/admin/residents/'.$r['id'].'/detail') }}" class="font-medium text-x2-primary hover:underline">{{ $r['name'] }}</a>
                                    <span class="ml-1 rounded px-1.5 py-0.5 text-[11px] {{ $r['isOwner'] ? 'bg-x2-primary/10 text-x2-primary' : 'bg-slate-100 text-slate-500' }}">{{ $r['role'] }}</span>
                                    <div class="text-xs text-slate-400">CCCD {{ $r['cccd'] }} · {{ $r['phone'] }}</div>
                                </div>
                            @empty
                                <p class="text-slate-400">Chưa có cư dân.</p>
                            @endforelse
                        </div>
                        <div class="text-sm">
                            <p class="mb-1.5 text-xs font-semibold text-slate-500">Phương tiện & thẻ</p>
                            @foreach ($selected['vehicles'] as $v)<div class="text-slate-600">🚗 {{ $v['plate'] }} <span class="text-slate-400">{{ $v['type'] }}</span></div>@endforeach
                            @foreach ($selected['cards'] as $c)<div class="text-slate-600">💳 #{{ $c['no'] }} <span class="text-slate-400">{{ $c['type'] }}</span></div>@endforeach
                            @if (! count($selected['vehicles']) && ! count($selected['cards']))<p class="text-slate-400">—</p>@endif
                            <p class="mt-2 text-xs text-slate-500">Công nợ quá hạn</p>
                            <p class="font-semibold {{ $selected['debtTotal'] > 0 ? 'text-x2-red' : 'text-emerald-600' }}">{{ number_format($selected['debtTotal'], 0, ',', '.') }} đ</p>
                        </div>
                        <div class="text-sm">
                            <p class="mb-1.5 text-xs font-semibold text-slate-500">Thao tác nhanh</p>
                            <div class="space-y-1.5">
                                <a href="{{ url('/admin/apartments/'.$selected['id'].'/profile') }}" class="block rounded-lg border border-slate-200 px-3 py-1.5 text-center text-xs font-medium text-slate-600 hover:bg-slate-50">Xem hồ sơ căn hộ</a>
                                <a href="{{ url('/admin/residents/binding-queue') }}" class="block rounded-lg border border-slate-200 px-3 py-1.5 text-center text-xs font-medium text-slate-600 hover:bg-slate-50">Gắn cư dân</a>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
    </div>
</x-filament-panels::page>
