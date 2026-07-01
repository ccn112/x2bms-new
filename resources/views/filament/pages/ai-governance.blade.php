@php
    $polTone = ['active' => 'green', 'inactive' => 'slate'];
    $polLabel = ['active' => 'Đang áp dụng', 'inactive' => 'Tắt'];
    $riskTone = ['low' => 'slate', 'medium' => 'amber', 'high' => 'red'];
    $riskLabel = ['low' => 'Thấp', 'medium' => 'Trung bình', 'high' => 'Cao'];
    $catLabel = ['data' => 'Dữ liệu', 'access' => 'Truy cập', 'risk' => 'Rủi ro', 'content' => 'Nội dung'];
@endphp

<x-filament-panels::page>
    <x-x2.action-bar
        title="Governance & Audit AI"
        subtitle="Giám sát tuân thủ: chính sách, nguồn dữ liệu, prompt và nhật ký kiểm toán mọi tương tác AI." />

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ($kpis as $kpi)
            <x-x2.kpi-card :label="$kpi['label']" :value="$kpi['value']" :accent="$kpi['accent']" />
        @endforeach
    </div>

    <div x-data="{ tab: 'audit' }" class="space-y-5">
        {{-- Tab bar --}}
        <div class="flex flex-wrap gap-1 border-b border-slate-200">
            @foreach ([
                'audit' => 'Nhật ký kiểm toán',
                'policies' => 'Chính sách AI',
                'sources' => 'Nguồn dữ liệu',
                'prompts' => 'Prompt & phân loại',
            ] as $key => $label)
                <button type="button" @click="tab = '{{ $key }}'"
                        :class="tab === '{{ $key }}' ? 'border-x2-gold text-x2-navy' : 'border-transparent text-slate-500 hover:text-slate-700'"
                        class="-mb-px border-b-2 px-4 py-2.5 text-sm font-semibold transition">
                    {{ $label }}
                </button>
            @endforeach
        </div>

        {{-- Nhật ký kiểm toán --}}
        <div x-show="tab === 'audit'" x-cloak>
            <div class="rounded-2xl border border-slate-100 bg-white p-1 shadow-sm">
                {{ $this->table }}
            </div>
        </div>

        {{-- Chính sách AI --}}
        <div x-show="tab === 'policies'" x-cloak class="grid gap-4 sm:grid-cols-2">
            @foreach ($policies as $p)
                <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                    <div class="flex items-start justify-between gap-3">
                        <h3 class="text-sm font-semibold text-slate-800">{{ $p->name }}</h3>
                        <x-x2.status-badge :label="$polLabel[$p->status] ?? $p->status" :tone="$polTone[$p->status] ?? 'slate'" />
                    </div>
                    <p class="mt-1 text-xs text-slate-500">{{ $p->description }}</p>
                    <div class="mt-3 flex items-center justify-between gap-2">
                        <div class="flex items-center gap-2">
                            <span class="rounded-md bg-slate-100 px-2 py-0.5 text-[11px] font-medium text-slate-600">{{ $catLabel[$p->category] ?? $p->category }}</span>
                            <x-x2.status-badge :label="'Rủi ro: '.($riskLabel[$p->risk_level] ?? $p->risk_level)" :tone="$riskTone[$p->risk_level] ?? 'slate'" />
                        </div>
                        <button type="button" wire:click="togglePolicy({{ $p->id }})" wire:loading.attr="disabled"
                                @class([
                                    'shrink-0 rounded-lg px-2.5 py-1 text-xs font-semibold transition',
                                    'bg-x2-red/10 text-x2-red hover:bg-x2-red/20' => $p->status === 'active',
                                    'bg-x2-green/10 text-x2-green hover:bg-x2-green/20' => $p->status !== 'active',
                                ])>
                            {{ $p->status === 'active' ? 'Tắt' : 'Bật' }}
                        </button>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Nguồn dữ liệu --}}
        <div x-show="tab === 'sources'" x-cloak>
            <x-x2.section-card title="Nguồn dữ liệu AI được phép đọc" subtitle="Các màn hình / mô-đun đã cấp ngữ cảnh cho X2AI">
                <div class="divide-y divide-slate-100">
                    @foreach ($dataSources as $src)
                        <div class="flex items-center justify-between gap-3 py-3">
                            <div class="flex items-center gap-3">
                                <span class="grid h-9 w-9 place-items-center rounded-lg bg-x2-blue/10 text-x2-blue">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M4 7v10c0 1.1 3.6 2 8 2s8-.9 8-2V7M4 7c0 1.1 3.6 2 8 2s8-.9 8-2M4 7c0-1.1 3.6-2 8-2s8 .9 8 2"/></svg>
                                </span>
                                <div>
                                    <div class="text-sm font-medium text-slate-800">{{ $src['name'] }}</div>
                                    <div class="text-xs text-slate-400">Truy cập gần nhất: {{ $src['last'] }}</div>
                                </div>
                            </div>
                            <span class="text-sm tabular-nums text-slate-500">{{ number_format($src['count']) }} lượt</span>
                        </div>
                    @endforeach
                    <div class="flex items-center justify-between gap-3 py-3">
                        <div class="flex items-center gap-3">
                            <span class="grid h-9 w-9 place-items-center rounded-lg bg-x2-green/10 text-x2-green">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.25v13M12 6.25a5 5 0 00-5-5H3v13h4a5 5 0 015 5M12 6.25a5 5 0 015-5h4v13h-4a5 5 0 00-5 5"/></svg>
                            </span>
                            <div>
                                <div class="text-sm font-medium text-slate-800">Cơ sở tri thức (KB)</div>
                                <div class="text-xs text-slate-400">Tài liệu nội bộ phục vụ trả lời</div>
                            </div>
                        </div>
                        <span class="text-sm tabular-nums text-slate-500">{{ number_format($kbCount) }} bài</span>
                    </div>
                </div>
            </x-x2.section-card>
        </div>

        {{-- Prompt & phân loại --}}
        <div x-show="tab === 'prompts'" x-cloak>
            <div class="overflow-hidden rounded-2xl border border-slate-100 bg-white shadow-sm">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-4 py-3">Tên prompt</th>
                            <th class="px-4 py-3">Phân loại</th>
                            <th class="px-4 py-3">Màn hình</th>
                            <th class="px-4 py-3 text-right">Lượt dùng</th>
                            <th class="px-4 py-3">Trạng thái</th>
                            <th class="px-4 py-3 text-right"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($prompts as $pr)
                            <tr class="hover:bg-slate-50/60">
                                <td class="px-4 py-3 font-medium text-slate-800">{{ $pr->name }}</td>
                                <td class="px-4 py-3"><span class="rounded-md bg-x2-gold/10 px-2 py-0.5 text-[11px] font-medium text-x2-navy">{{ $pr->classification ?? '—' }}</span></td>
                                <td class="px-4 py-3 text-slate-500">{{ \App\Filament\Pages\AiCenter::SURFACE_LABELS[$pr->surface] ?? ($pr->surface ?? 'Toàn hệ thống') }}</td>
                                <td class="px-4 py-3 text-right tabular-nums text-slate-600">{{ number_format($pr->usage_count) }}</td>
                                <td class="px-4 py-3"><x-x2.status-badge :label="$pr->status === 'active' ? 'Đang dùng' : 'Tắt'" :tone="$pr->status === 'active' ? 'green' : 'slate'" /></td>
                                <td class="px-4 py-3 text-right">
                                    <button type="button" wire:click="togglePrompt({{ $pr->id }})" wire:loading.attr="disabled"
                                            class="rounded-lg px-2.5 py-1 text-xs font-semibold text-slate-600 transition hover:bg-slate-100">
                                        {{ $pr->status === 'active' ? 'Tắt' : 'Bật' }}
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-filament-panels::page>
