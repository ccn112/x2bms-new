@php
    $wfTone = ['active' => 'green', 'paused' => 'amber', 'draft' => 'slate'];
    $wfLabel = ['active' => 'Đang chạy', 'paused' => 'Tạm dừng', 'draft' => 'Nháp'];
@endphp

<x-filament-panels::page>
    <x-x2.action-bar
        title="Trung tâm AI X2AI"
        subtitle="Toàn cảnh hoạt động trợ lý AI: mức dùng, tự động hoá, tri thức và kiểm soát." />

    {{-- KPI row --}}
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
        @foreach ($kpis as $kpi)
            <x-x2.kpi-card :label="$kpi['label']" :value="$kpi['value']" :sub="$kpi['sub'] ?? null" :accent="$kpi['accent']" />
        @endforeach
    </div>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-12">
        {{-- LEFT --}}
        <div class="space-y-6 xl:col-span-8">
            {{-- Usage trend --}}
            <x-x2.section-card title="Xu hướng sử dụng AI" subtitle="14 ngày gần nhất">
                <div class="flex h-44 items-end gap-1.5">
                    @foreach ($trend as $t)
                        <div class="group flex flex-1 flex-col items-center justify-end gap-1.5">
                            <span class="text-[10px] font-semibold text-slate-400 opacity-0 group-hover:opacity-100">{{ $t['count'] }}</span>
                            <div class="w-full rounded-t bg-gradient-to-t from-x2-navy to-x2-blue transition-all group-hover:opacity-80"
                                 style="height: {{ max(4, round($t['count'] / $trendMax * 140)) }}px"></div>
                            <span class="text-[10px] text-slate-400">{{ $t['label'] }}</span>
                        </div>
                    @endforeach
                </div>
            </x-x2.section-card>

            {{-- AI Copilot theo màn hình --}}
            <x-x2.section-card title="AI Copilot theo màn hình" subtitle="Số lượt trợ giúp theo từng màn (30 ngày)">
                <ul class="space-y-3">
                    @forelse ($bySurface as $row)
                        <li>
                            <div class="mb-1 flex items-center justify-between text-sm">
                                <span class="font-medium text-slate-700">{{ $row['surface'] }}</span>
                                <span class="tabular-nums text-slate-500">{{ number_format($row['count']) }}</span>
                            </div>
                            <div class="h-2 overflow-hidden rounded-full bg-slate-100">
                                <div class="h-full rounded-full bg-x2-gold" style="width: {{ round($row['count'] / $surfaceMax * 100) }}%"></div>
                            </div>
                        </li>
                    @empty
                        <li class="text-sm text-slate-400">Chưa có dữ liệu sử dụng.</li>
                    @endforelse
                </ul>
            </x-x2.section-card>

            {{-- Tự động hoá AI --}}
            <x-x2.section-card title="Tự động hoá AI" subtitle="Các workflow đang hoạt động nhiều nhất">
                <x-slot:action>
                    <a href="{{ \App\Filament\Pages\AiWorkflowAutomation::getUrl() }}" class="font-medium text-x2-blue hover:underline">Quản lý →</a>
                </x-slot:action>
                <div class="divide-y divide-slate-100">
                    @foreach ($workflows as $wf)
                        <div class="flex items-center justify-between gap-3 py-2.5">
                            <div class="min-w-0">
                                <div class="truncate text-sm font-medium text-slate-800">{{ $wf->name }}</div>
                                <div class="text-xs text-slate-500">{{ $wf->schedule }}</div>
                            </div>
                            <div class="flex shrink-0 items-center gap-3">
                                <span class="text-xs tabular-nums text-slate-500">{{ number_format($wf->runs_count) }} lần chạy</span>
                                <x-x2.status-badge :label="$wfLabel[$wf->status] ?? $wf->status" :tone="$wfTone[$wf->status] ?? 'slate'" />
                            </div>
                        </div>
                    @endforeach
                </div>
            </x-x2.section-card>
        </div>

        {{-- RIGHT --}}
        <aside class="space-y-6 xl:col-span-4">
            {{-- Tóm tắt & chốt duyệt --}}
            <x-x2.section-card title="Chờ con người duyệt" subtitle="Hành động AI rủi ro cao">
                <ul class="space-y-3">
                    @forelse ($pendingApproval as $p)
                        <li class="flex gap-2 rounded-lg bg-amber-50 p-2.5 text-sm">
                            <span class="mt-0.5 grid h-6 w-6 shrink-0 place-items-center rounded-full bg-x2-amber/20 text-x2-amber">
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3m0 3h.01M10.29 3.86l-7.5 12.99A1.5 1.5 0 004.08 19h15.84a1.5 1.5 0 001.29-2.15l-7.5-12.99a1.5 1.5 0 00-2.58 0z"/></svg>
                            </span>
                            <div class="min-w-0">
                                <div class="truncate text-slate-700">{{ $p->prompt_excerpt }}</div>
                                <div class="text-xs text-slate-400">{{ \App\Filament\Pages\AiCenter::SURFACE_LABELS[$p->surface] ?? $p->surface }} · {{ $p->created_at?->diffForHumans() }}</div>
                            </div>
                        </li>
                    @empty
                        <li class="rounded-lg bg-x2-green/5 p-3 text-sm text-x2-green">Không có mục nào chờ duyệt 🎉</li>
                    @endforelse
                </ul>
            </x-x2.section-card>

            {{-- Nguồn tri thức --}}
            <x-x2.section-card title="Nguồn tri thức (Knowledge)">
                <x-slot:action>
                    <a href="{{ \App\Filament\Pages\AiKnowledgeBase::getUrl() }}" class="font-medium text-x2-blue hover:underline">Mở KB →</a>
                </x-slot:action>
                <div class="grid grid-cols-2 gap-3">
                    <div class="rounded-lg bg-slate-50 p-3">
                        <div class="text-2xl font-bold text-x2-navy">{{ number_format($kbCount) }}</div>
                        <div class="text-xs text-slate-500">bài đã xuất bản</div>
                    </div>
                    <div class="rounded-lg bg-slate-50 p-3">
                        <div class="text-2xl font-bold text-x2-navy">{{ number_format($kbViews) }}</div>
                        <div class="text-xs text-slate-500">lượt xem</div>
                    </div>
                </div>
            </x-x2.section-card>

            {{-- Gợi ý nhanh --}}
            <x-x2.section-card title="Gợi ý nhanh" subtitle="Prompt mẫu dùng nhiều nhất">
                <div class="flex flex-wrap gap-2">
                    @foreach ($quickPrompts as $qp)
                        <button type="button"
                                onclick="window.Livewire.dispatch('x2ai-prefill', { prompt: @js($qp->name) }); window.dispatchEvent(new CustomEvent('x2ai-open'))"
                                class="rounded-full border border-slate-200 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 transition hover:border-x2-gold hover:bg-x2-gold/5 hover:text-x2-navy">
                            {{ $qp->name }}
                        </button>
                    @endforeach
                </div>
            </x-x2.section-card>
        </aside>
    </div>
</x-filament-panels::page>
