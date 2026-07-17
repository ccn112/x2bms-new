<x-filament-panels::page>
    {{-- BQL density layer (handoff 0307): scoped .x2-bql-page controls spacing/density.
         Breadcrumb + action trang dùng header Filament mặc định (getBreadcrumbs + getHeaderActions);
         topbar JS chỉ ẩn title, giữ breadcrumb + actions (cùng hàng). --}}
    <div class="x2-bql-page">
        {{-- KPI — tổng theo context (5 card compact 88–96px), BẤT BIẾN theo filter. --}}
        <x-x2.kpi-row :cols="5">
            @foreach ($kpis as $kpi)
                <x-x2.card.kpi
                    class="x2-kpi"
                    :label="$kpi['label']"
                    :value="$kpi['value']"
                    :sub="$kpi['sub'] ?? null"
                    :accent="$kpi['accent']"
                    :icon="$kpi['icon'] ?? 'heroicon-o-chart-bar'" />
            @endforeach
        </x-x2.kpi-row>

        {{-- X2FilterBar (density Task 4/5): filter nghiệp vụ luôn hiện + chip + drawer nâng cao;
             state Livewire → tác động query/pagination/export thật (thay filter mặc định Filament). --}}
        <div x-data="{ adv: false }" class="mt-4">
            <x-x2.filter.bar :advanced-count="$advancedCount" advanced-click="adv = true">
                <x-slot:inline>
                    <x-x2.filter.select field="fBuilding" placeholder="Tất cả tòa" :options="$filterOptions['buildings']" />
                    <x-x2.filter.select field="fFloor" placeholder="Tất cả tầng" :options="$filterOptions['floors']" />
                    <x-x2.filter.select field="fType" placeholder="Tất cả loại căn" :options="$filterOptions['types']" />
                    <x-x2.filter.select field="fStatus" placeholder="Tất cả trạng thái" :options="$filterOptions['statuses']" />
                    <x-x2.filter.select field="fHolder" placeholder="Chủ thể" :options="$filterOptions['holders']" />
                </x-slot:inline>
                <x-slot:search>
                    <input type="search" wire:model.live.debounce.400ms="fSearch"
                        placeholder="Tìm mã căn, chủ sở hữu, người thuê, SĐT…"
                        class="h-9 w-full rounded-lg border border-slate-200 bg-white px-3 text-sm text-slate-700 focus:border-x2-primary focus:ring-0" />
                </x-slot:search>
                <x-slot:trailing>
                    {{-- Ẩn/hiện cột — nhóm cùng bộ lọc nâng cao (chuẩn listing) --}}
                    <div x-data="{ colOpen: false }" class="relative">
                        <button type="button" @click="colOpen = ! colOpen" title="Ẩn/hiện cột"
                            class="inline-flex h-9 items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 text-sm font-medium text-slate-600 hover:bg-slate-50">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25v16.5H6A2.25 2.25 0 013.75 18V6zM10.5 3.75h3v16.5h-3V3.75zM15.75 3.75H18A2.25 2.25 0 0120.25 6v12A2.25 2.25 0 0118 20.25h-2.25V3.75z"/></svg>
                            Cột
                        </button>
                        <div x-show="colOpen" x-cloak @click.outside="colOpen = false"
                            class="absolute right-0 z-20 mt-1 w-56 rounded-xl border border-slate-200 bg-white p-3 shadow-lg">
                            <div class="mb-2 flex items-center justify-between">
                                <span class="text-xs font-semibold text-slate-500">Cột hiển thị</span>
                                <button type="button" wire:click="resetCols" @click="colOpen = false" class="text-xs font-medium text-x2-red hover:underline">Đặt lại</button>
                            </div>
                            <div class="space-y-0.5">
                                @foreach ($columnToggle as $key => $label)
                                    <label class="flex cursor-pointer items-center gap-2 rounded-md px-1.5 py-1 text-sm text-slate-700 hover:bg-slate-50">
                                        <input type="checkbox" wire:model="cols.{{ $key }}"
                                            class="rounded border-slate-300 text-x2-primary focus:ring-x2-primary" />
                                        {{ $label }}
                                    </label>
                                @endforeach
                            </div>
                            <button type="button" wire:click="applyCols" @click="colOpen = false"
                                class="mt-3 w-full rounded-lg bg-x2-primary px-3 py-1.5 text-sm font-semibold text-white hover:opacity-90">Áp dụng</button>
                        </div>
                    </div>
                </x-slot:trailing>
            </x-x2.filter.bar>

            {{-- Chip filter đang bật + Xóa tất cả --}}
            @if (count($activeChips))
                <div class="mt-2 flex flex-wrap items-center gap-2">
                    @foreach ($activeChips as $chip)
                        <x-x2.filter.chip :label="$chip['label']" :value="$chip['value']"
                            :remove-wire="'clearFilter(\''.$chip['key'].'\')'" />
                    @endforeach
                    <button type="button" wire:click="clearAllFilters"
                        class="ml-1 text-xs font-semibold text-x2-primary hover:underline">Xóa tất cả</button>
                </div>
            @endif

            {{-- Bảng Filament: sort / row + bulk action / phân trang (dense).
                 Trên <768px, .fi-ta-content bị ẩn (CSS scoped) và card list bên dưới hiện thay;
                 .fi-pagination vẫn hiển thị nên phân trang/sort/filter do Filament xử lý. --}}
            <div class="mt-3 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                {{ $this->table }}

                {{-- Mobile card/list (Task 7) — render từ cùng nguồn getTableRecords() (đã lọc/sort/phân trang) --}}
                <div class="x2-mobile-cards space-y-2.5 p-3">
                    @forelse ($this->getTableRecords() as $a)
                        @php $m = $this->cardMeta($a); @endphp
                        <div class="rounded-xl border border-slate-200 bg-white p-3.5 shadow-sm">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <a href="{{ url('/admin/apartments/'.$a->id.'/profile') }}" class="font-title text-sm font-bold text-x2-primary">{{ $a->code }}</a>
                                    <div class="mt-0.5 text-xs text-slate-500">
                                        {{ $a->building?->name ?? '—' }} · {{ $a->floor?->name ?? '—' }} · {{ $a->type ?? '—' }}
                                        · {{ $a->area_sqm ? number_format((float) $a->area_sqm, 1, ',', '.').' m²' : '—' }}
                                    </div>
                                </div>
                                <span class="shrink-0 rounded-full px-2 py-0.5 text-[11px] font-semibold {{ $m['statusBadgeClass'] }}">{{ $m['statusLabel'] }}</span>
                            </div>
                            <div class="mt-2.5 flex items-center justify-between gap-3 border-t border-slate-100 pt-2.5">
                                <div class="min-w-0">
                                    <div class="truncate text-xs text-slate-700">
                                        {{ $m['holderName'] }}@if ($m['holderRole'])<span class="text-slate-400"> · {{ $m['holderRole'] }}</span>@endif
                                    </div>
                                    <div class="text-[11px] text-slate-400">{{ $m['residentCount'] }} cư dân · cập nhật {{ $a->updated_at?->format('d/m/Y') }}</div>
                                </div>
                                <div class="shrink-0 text-right">
                                    <div class="text-sm font-semibold {{ $m['debt'] > 0 ? 'text-x2-red' : 'text-emerald-600' }}">{{ number_format($m['debt'], 0, ',', '.') }}</div>
                                    <div class="text-[11px] text-slate-400">công nợ (đ)</div>
                                </div>
                            </div>
                            <div class="mt-2.5 flex items-center gap-2">
                                <a href="{{ url('/admin/apartments/'.$a->id.'/profile') }}" class="flex-1 rounded-lg border border-slate-200 py-1.5 text-center text-xs font-medium text-slate-600">Hồ sơ</a>
                                <a href="{{ url('/fila/apartments/'.$a->id.'/edit') }}" class="flex-1 rounded-lg border border-slate-200 py-1.5 text-center text-xs font-medium text-slate-600">Sửa</a>
                            </div>
                        </div>
                    @empty
                        <div class="rounded-xl border border-dashed border-slate-200 bg-white p-6 text-center text-sm text-slate-500">Không tìm thấy căn hộ phù hợp</div>
                    @endforelse
                </div>
            </div>

            {{-- Drawer bộ lọc nâng cao (Task 4): fields ánh xạ cột thật --}}
            <div x-show="adv" x-cloak class="fixed inset-0 z-40" style="display:none"
                @keydown.escape.window="adv = false">
                <div class="absolute inset-0 bg-slate-900/40" @click="adv = false"></div>
                <div class="absolute right-0 top-0 flex h-full w-full max-w-md flex-col bg-white shadow-2xl"
                    x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0"
                    x-transition:leave="transition ease-in duration-150"
                    x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full">
                    <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
                        <h3 class="font-title text-base font-bold text-x2-primary">Bộ lọc nâng cao</h3>
                        <button type="button" @click="adv = false" class="text-slate-400 hover:text-slate-700">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 6l12 12M18 6L6 18"/></svg>
                        </button>
                    </div>
                    <div class="flex-1 space-y-5 overflow-y-auto px-5 py-5">
                        {{-- Bộ lọc chính — chỉ hiện trên mobile (desktop đã có inline). --}}
                        <div class="space-y-3 md:hidden">
                            <x-x2.filter.select field="fBuilding" placeholder="Tất cả tòa" :options="$filterOptions['buildings']" class="w-full" />
                            <x-x2.filter.select field="fFloor" placeholder="Tất cả tầng" :options="$filterOptions['floors']" class="w-full" />
                            <x-x2.filter.select field="fType" placeholder="Tất cả loại căn" :options="$filterOptions['types']" class="w-full" />
                            <x-x2.filter.select field="fStatus" placeholder="Tất cả trạng thái" :options="$filterOptions['statuses']" class="w-full" />
                            <x-x2.filter.select field="fHolder" placeholder="Chủ thể" :options="$filterOptions['holders']" class="w-full" />
                            <hr class="border-slate-100">
                        </div>
                        <div>
                            <label class="mb-1.5 block text-xs font-semibold text-slate-500">Diện tích (m²)</label>
                            <div class="flex items-center gap-2">
                                <input type="number" min="0" step="0.1" wire:model.live.debounce.500ms="fAreaMin" placeholder="Từ"
                                    class="h-9 w-full rounded-lg border border-slate-200 px-3 text-sm focus:border-x2-primary focus:ring-0" />
                                <span class="text-slate-400">–</span>
                                <input type="number" min="0" step="0.1" wire:model.live.debounce.500ms="fAreaMax" placeholder="Đến"
                                    class="h-9 w-full rounded-lg border border-slate-200 px-3 text-sm focus:border-x2-primary focus:ring-0" />
                            </div>
                        </div>
                        <div>
                            <label class="mb-1.5 block text-xs font-semibold text-slate-500">Cập nhật</label>
                            <div class="flex items-center gap-2">
                                <input type="date" wire:model.live="fUpdatedFrom"
                                    class="h-9 w-full rounded-lg border border-slate-200 px-3 text-sm focus:border-x2-primary focus:ring-0" />
                                <span class="text-slate-400">–</span>
                                <input type="date" wire:model.live="fUpdatedTo"
                                    class="h-9 w-full rounded-lg border border-slate-200 px-3 text-sm focus:border-x2-primary focus:ring-0" />
                            </div>
                        </div>
                        <div>
                            <label class="mb-1.5 block text-xs font-semibold text-slate-500">Công nợ</label>
                            <select wire:model.live="fDebt"
                                class="h-9 w-full rounded-lg border border-slate-200 bg-white px-2.5 text-sm text-slate-700 focus:border-x2-primary focus:ring-0">
                                <option value="">Tất cả</option>
                                <option value="with">Có công nợ quá hạn</option>
                                <option value="none">Không có công nợ</option>
                            </select>
                        </div>
                    </div>
                    <div class="flex items-center justify-between gap-3 border-t border-slate-200 px-5 py-4">
                        <button type="button" wire:click="clearAdvanced"
                            class="text-sm font-medium text-slate-500 hover:text-slate-800">Đặt lại</button>
                        <button type="button" @click="adv = false"
                            class="rounded-lg bg-x2-primary px-4 py-2 text-sm font-semibold text-white hover:opacity-90">Áp dụng</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>
