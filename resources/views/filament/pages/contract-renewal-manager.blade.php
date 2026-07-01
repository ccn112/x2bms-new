<x-filament-panels::page>
    <x-x2.action-bar
        title="Hợp đồng & gia hạn thuê bao"
        subtitle="Vòng đời hợp đồng · pipeline gia hạn · đàm phán · duyệt/từ chối. Ghi nhật ký billing." />

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ($kpis as $kpi)
            <x-x2.kpi-card :label="$kpi['label']" :value="$kpi['value']" :accent="$kpi['accent']" />
        @endforeach
    </div>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-12">
        <div class="xl:col-span-8">
            <div class="rounded-2xl border border-slate-100 bg-white p-1 shadow-sm">{{ $this->table }}</div>
        </div>
        <aside class="space-y-4 xl:col-span-4">
            <x-x2.section-card title="Pipeline gia hạn">
                <ul class="space-y-2.5">
                    @forelse ($pipeline as $r)
                        <li class="rounded-xl border border-slate-100 p-3">
                            <div class="flex items-start justify-between gap-2">
                                <div>
                                    <p class="text-sm font-medium text-slate-800">{{ $r->subscription?->tenant?->name ?? ($r->contract?->contract_no ?? '—') }}</p>
                                    <p class="text-xs text-slate-500">{{ $stageMap[$r->stage] ?? $r->stage }} · mục tiêu {{ $r->target_date?->format('d/m/Y') ?? '—' }}
                                        @if($r->proposed_value) · {{ number_format($r->proposed_value/1000000,0) }}tr @endif</p>
                                </div>
                                @if(in_array($r->stage, ['pending','negotiation']))
                                    <div class="flex shrink-0 gap-1">
                                        <button wire:click="decideRenewal({{ $r->id }}, 'approve')" class="rounded-lg bg-green-50 px-2 py-1 text-xs font-medium text-green-700">Duyệt</button>
                                        <button wire:click="decideRenewal({{ $r->id }}, 'reject')" class="rounded-lg bg-red-50 px-2 py-1 text-xs font-medium text-red-600">Từ chối</button>
                                    </div>
                                @else
                                    <span class="shrink-0 rounded-lg bg-slate-100 px-2 py-1 text-xs text-slate-500">{{ $stageMap[$r->stage] ?? $r->stage }}</span>
                                @endif
                            </div>
                        </li>
                    @empty
                        <li class="text-sm text-slate-400">Chưa có task gia hạn.</li>
                    @endforelse
                </ul>
            </x-x2.section-card>
        </aside>
    </div>
</x-filament-panels::page>
