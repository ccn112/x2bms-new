@php
    $statusMeta = \App\Filament\Pages\StatementApprovalQueue::STATUS;
@endphp

<x-filament-panels::page>
    <x-x2.action-bar subtitle="Duyệt & phát hành bảng kê phí theo lô (tòa / kỳ thu)" />

    {{-- KPI row --}}
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ($kpis as $kpi)
            <x-x2.kpi-card :label="$kpi['label']" :value="$kpi['value']" :sub="$kpi['sub'] ?? null" :accent="$kpi['accent']" />
        @endforeach
    </div>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-12">
        {{-- LEFT: queue table + approval flow --}}
        <div class="space-y-6 xl:col-span-9">
            <div class="rounded-2xl border border-slate-100 bg-white p-1 shadow-sm">
                {{ $this->table }}
            </div>

            {{-- Quy trình phê duyệt --}}
            <div class="rounded-2xl border border-slate-100 bg-white p-5 shadow-sm">
                <h3 class="mb-4 font-title text-base font-semibold text-x2-navy">Quy trình phê duyệt</h3>
                <div class="flex flex-wrap items-start gap-2">
                    @foreach ($flow as $i => [$title, $desc, $state])
                        <div class="flex flex-1 items-start gap-3">
                            <div class="flex flex-col items-center">
                                <span @class([
                                    'grid h-9 w-9 place-items-center rounded-full text-sm font-semibold',
                                    'bg-x2-green text-white' => $state === 'done',
                                    'bg-x2-gold text-white ring-4 ring-x2-gold/20' => $state === 'current',
                                    'bg-slate-100 text-slate-400' => $state === 'todo',
                                ])>{{ $i + 1 }}</span>
                            </div>
                            <div class="min-w-0">
                                <div @class([
                                    'text-sm font-semibold',
                                    'text-slate-800' => $state !== 'todo',
                                    'text-slate-400' => $state === 'todo',
                                ])>{{ $title }}</div>
                                <div class="text-xs text-slate-500">{{ $desc }}</div>
                            </div>
                            @if (! $loop->last)
                                <svg class="mt-2 h-4 w-4 shrink-0 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- RIGHT: read-only context panels. (X2AI gợi ý đã đẩy vào khung chat nổi chung.) --}}
        <aside class="space-y-4 xl:col-span-3">
            <div class="rounded-2xl border border-slate-100 bg-white p-4 shadow-sm">
                <h3 class="mb-3 font-title text-sm font-semibold text-x2-navy">Lịch sử phê duyệt</h3>
                <ol class="space-y-3">
                    @forelse ($history as $h)
                        <li class="flex gap-2 text-sm">
                            <span class="mt-1 h-1.5 w-1.5 shrink-0 rounded-full bg-x2-gold"></span>
                            <div>
                                <div class="text-slate-700">{{ $h->description }}</div>
                                <div class="text-xs text-slate-400">{{ $h->actor_name }} · {{ $h->created_at?->format('d/m/Y H:i') }}</div>
                            </div>
                        </li>
                    @empty
                        <li class="text-sm text-slate-400">Chưa có hoạt động phê duyệt.</li>
                    @endforelse
                </ol>
            </div>
        </aside>
    </div>
</x-filament-panels::page>
