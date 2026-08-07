<x-filament-panels::page>
    <div class="x2-bql-page space-y-4">
        {{-- Highlights (code · trạng thái · loại · người tạo · chi phí) --}}
        <div class="flex flex-wrap items-center gap-2">
            <span class="rounded-full bg-x2-primary/10 px-3 py-1 text-xs font-semibold text-x2-primary">{{ $n->code }}</span>
            <span class="rounded-full px-3 py-1 text-xs font-semibold"
                @class([
                    'bg-slate-100 text-slate-600' => in_array($wf->tone(), ['slate', 'gray']),
                    'bg-x2-amber/10 text-x2-amber' => in_array($wf->tone(), ['amber', 'indigo']),
                    'bg-x2-blue/10 text-x2-blue' => $wf->tone() === 'blue',
                    'bg-x2-green/10 text-x2-green' => $wf->tone() === 'green',
                    'bg-x2-red/10 text-x2-red' => $wf->tone() === 'red',
                ])>{{ $wf->label() }}</span>
            <span class="text-sm text-slate-500">Loại: <b class="text-slate-700">{{ $n->content_type?->label() }}</b></span>
            <span class="text-sm text-slate-500">Người tạo: <b class="text-slate-700">{{ $n->creator?->name ?? '—' }}</b></span>
            @if ($n->cost_estimate > 0)
                <span class="text-sm text-slate-500">Chi phí ước tính: <b class="text-slate-700">{{ number_format($n->cost_estimate, 0, ',', '.') }}đ</b></span>
            @endif
        </div>

        <x-x2.kpi-row :cols="4">
            @foreach ($kpis as $kpi)
                <x-x2.card.kpi :label="$kpi['label']" :value="$kpi['value']" :accent="$kpi['accent']" :icon="$kpi['icon']" />
            @endforeach
        </x-x2.kpi-row>

        <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
            {{-- 2/3: nội dung + người nhận --}}
            <div class="space-y-4 lg:col-span-2">
                <x-x2.card.info :title="$n->title" :icon="$n->content_type?->icon() ?? 'heroicon-o-megaphone'">
                    @if ($n->summary)
                        <p class="mb-3 text-sm text-slate-500">{{ $n->summary }}</p>
                    @endif
                    <div class="prose prose-sm max-w-none text-slate-700">{!! $n->body !!}</div>
                    @if ($n->cta_label)
                        <div class="mt-3">
                            <span class="inline-flex items-center rounded-lg bg-x2-primary/10 px-3 py-1.5 text-sm font-medium text-x2-primary">
                                {{ $n->cta_label }} →
                            </span>
                        </div>
                    @endif
                </x-x2.card.info>

                <x-x2.card.info title="Người nhận & trạng thái" icon="heroicon-o-users" :padding="false">
                    <div class="px-2 py-2">{{ $this->table }}</div>
                </x-x2.card.info>
            </div>

            {{-- 1/3: kênh + tuyến duyệt + snapshot --}}
            <div class="space-y-4">
                <x-x2.card.info title="Kênh gửi" icon="heroicon-o-signal">
                    <div class="flex flex-wrap gap-1.5">
                        @forelse ($n->channels as $ch)
                            <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-600">{{ $ch->channel }}</span>
                        @empty
                            <span class="text-sm text-slate-400">Chưa chọn kênh</span>
                        @endforelse
                    </div>
                </x-x2.card.info>

                <x-x2.card.info title="Tuyến duyệt" icon="heroicon-o-shield-check">
                    @if ($approval)
                        <p class="mb-2 text-xs text-slate-500">{{ $approval->route_key }} · {{ $approval->status->label() }}</p>
                        <ol class="space-y-1.5 text-sm">
                            @foreach ($approval->steps as $step)
                                <li class="flex items-center justify-between">
                                    <span class="text-slate-600">{{ $step->step_no }}. {{ $step->role }}</span>
                                    <span @class([
                                        'text-xs font-medium',
                                        'text-x2-green' => $step->status->value === 'approved',
                                        'text-x2-red' => $step->status->value === 'rejected',
                                        'text-x2-amber' => in_array($step->status->value, ['requested', 'changes_requested']),
                                    ])>{{ $step->status->label() }}</span>
                                </li>
                            @endforeach
                        </ol>
                    @else
                        <p class="text-sm text-slate-400">Chưa gửi duyệt.</p>
                    @endif
                </x-x2.card.info>

                @if ($snapshot)
                    <x-x2.card.info title="Snapshot" icon="heroicon-o-lock-closed">
                        <p class="text-xs text-slate-500">Phiên bản {{ $snapshot->version }} · chốt {{ $snapshot->created_at?->format('d/m/Y H:i') }}</p>
                        <p class="mt-1 break-all font-mono text-[10px] text-slate-400">{{ Str::limit($snapshot->hash, 24) }}</p>
                    </x-x2.card.info>
                @endif
            </div>
        </div>
    </div>
</x-filament-panels::page>
