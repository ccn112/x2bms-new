<x-filament-panels::page>
    <div class="x2-bql-page space-y-4">
        {{-- Highlights --}}
        <div class="flex flex-wrap items-center gap-3">
            <span class="rounded-full bg-x2-primary/10 px-3 py-1 text-xs font-semibold text-x2-primary">{{ $n->code }}</span>
            <span class="rounded-full px-3 py-1 text-xs font-semibold"
                @class([
                    'bg-slate-100 text-slate-600' => in_array($wf->tone(), ['slate', 'gray']),
                    'bg-amber-100 text-amber-700' => in_array($wf->tone(), ['amber', 'indigo']),
                    'bg-blue-100 text-blue-700' => $wf->tone() === 'blue',
                    'bg-green-100 text-green-700' => $wf->tone() === 'green',
                    'bg-red-100 text-red-700' => $wf->tone() === 'red',
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
            {{-- Content preview --}}
            <div class="space-y-3 lg:col-span-2">
                <div class="rounded-xl border border-slate-200 bg-white p-4">
                    <h3 class="mb-2 font-title text-base font-semibold text-x2-navy">{{ $n->title }}</h3>
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
                </div>

                {{-- Recipients (BQL-NOTI-08) --}}
                <div class="rounded-xl border border-slate-200 bg-white p-4">
                    <h3 class="mb-3 font-title text-base font-semibold text-x2-navy">Người nhận & trạng thái</h3>
                    {{ $this->table }}
                </div>
            </div>

            {{-- Side: channels + approval --}}
            <div class="space-y-4">
                <div class="rounded-xl border border-slate-200 bg-white p-4">
                    <h4 class="mb-2 text-sm font-semibold text-slate-700">Kênh gửi</h4>
                    <div class="flex flex-wrap gap-2">
                        @forelse ($n->channels as $ch)
                            <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-600">{{ $ch->channel }}</span>
                        @empty
                            <span class="text-sm text-slate-400">Chưa chọn kênh</span>
                        @endforelse
                    </div>
                </div>

                <div class="rounded-xl border border-slate-200 bg-white p-4">
                    <h4 class="mb-2 text-sm font-semibold text-slate-700">Tuyến duyệt</h4>
                    @if ($approval)
                        <p class="mb-2 text-xs text-slate-500">{{ $approval->route_key }} · {{ $approval->status->label() }}</p>
                        <ol class="space-y-1.5 text-sm">
                            @foreach ($approval->steps as $step)
                                <li class="flex items-center justify-between">
                                    <span class="text-slate-600">{{ $step->step_no }}. {{ $step->role }}</span>
                                    <span @class([
                                        'text-xs font-medium',
                                        'text-green-600' => $step->status->value === 'approved',
                                        'text-red-600' => $step->status->value === 'rejected',
                                        'text-amber-600' => in_array($step->status->value, ['requested', 'changes_requested']),
                                    ])>{{ $step->status->label() }}</span>
                                </li>
                            @endforeach
                        </ol>
                    @else
                        <p class="text-sm text-slate-400">Chưa gửi duyệt.</p>
                    @endif
                </div>

                @if ($snapshot)
                    <div class="rounded-xl border border-slate-200 bg-white p-4">
                        <h4 class="mb-1 text-sm font-semibold text-slate-700">Snapshot</h4>
                        <p class="text-xs text-slate-500">Phiên bản {{ $snapshot->version }} · chốt {{ $snapshot->created_at?->format('d/m/Y H:i') }}</p>
                        <p class="mt-1 break-all font-mono text-[10px] text-slate-400">{{ Str::limit($snapshot->hash, 24) }}</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-filament-panels::page>
