@php
    $msgMeta = ['customer' => ['Khách hàng', 'bg-blue-50 text-blue-700'], 'internal' => ['Nội bộ', 'bg-slate-100 text-slate-600'], 'system' => ['Hệ thống', 'bg-amber-50 text-amber-700']];
@endphp
<div class="space-y-5 text-sm">
    <div class="grid grid-cols-2 gap-3 md:grid-cols-4">
        <div><div class="text-xs text-slate-400">Tenant</div><div class="text-slate-700">{{ $record->tenant?->name ?? '—' }}</div></div>
        <div><div class="text-xs text-slate-400">Ưu tiên</div><div class="text-slate-700">{{ ucfirst($record->priority) }}</div></div>
        <div><div class="text-xs text-slate-400">Trạng thái</div><div class="text-slate-700">{{ $record->status }}</div></div>
        <div><div class="text-xs text-slate-400">SLA</div><div class="text-slate-700">{{ $record->sla_state }}</div></div>
        <div><div class="text-xs text-slate-400">Module</div><div class="text-slate-700">{{ $record->module ?? '—' }}</div></div>
        <div><div class="text-xs text-slate-400">Owner</div><div class="text-slate-700">{{ $record->owner?->name ?? '—' }}</div></div>
        <div><div class="text-xs text-slate-400">Đội</div><div class="text-slate-700">{{ $record->team?->name ?? '—' }}</div></div>
        <div><div class="text-xs text-slate-400">SLA due</div><div class="text-slate-700">{{ $record->sla_due_at?->format('d/m H:i') ?? '—' }}</div></div>
    </div>

    @if ($record->description)
        <div class="rounded-lg border border-slate-100 bg-slate-50/60 p-3">
            <div class="mb-1 text-xs font-semibold uppercase tracking-wide text-slate-400">Mô tả</div>
            <div class="prose prose-sm max-w-none text-slate-700">{!! $record->description !!}</div>
        </div>
    @endif

    <div>
        <div class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-400">Timeline</div>
        <ol class="relative space-y-3 border-l border-slate-100 pl-4">
            @forelse ($record->messages as $m)
                <li class="relative">
                    <span class="absolute -left-[21px] top-1 h-2.5 w-2.5 rounded-full bg-slate-300"></span>
                    <div class="flex items-center gap-2">
                        <span class="rounded-full px-2 py-0.5 text-[11px] font-medium {{ $msgMeta[$m->type][1] ?? 'bg-slate-100' }}">{{ $msgMeta[$m->type][0] ?? $m->type }}</span>
                        <span class="text-[11px] text-slate-400">{{ $m->author_name ?? $m->author?->name }} · {{ $m->created_at?->format('d/m H:i') }}</span>
                    </div>
                    <div class="prose prose-sm mt-1 max-w-none text-slate-700">{!! $m->body !!}</div>
                </li>
            @empty
                <li class="text-slate-400">Chưa có trao đổi.</li>
            @endforelse
        </ol>
    </div>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
        <div>
            <div class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-400">Escalation</div>
            <ul class="divide-y divide-slate-50 rounded-lg border border-slate-100">
                @forelse ($record->escalations as $e)
                    <li class="flex items-center justify-between px-3 py-2"><span class="text-slate-600">{{ $e->from_level }} → {{ $e->to_level }}</span><span class="text-[11px] text-slate-400">{{ $e->reason }}</span></li>
                @empty
                    <li class="px-3 py-2 text-slate-400">Không có.</li>
                @endforelse
            </ul>
        </div>
        <div>
            <div class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-400">Data correction liên kết</div>
            <ul class="divide-y divide-slate-50 rounded-lg border border-slate-100">
                @forelse ($record->dataCorrectionRequests as $d)
                    <li class="flex items-center justify-between px-3 py-2"><span class="font-mono text-[11px] text-slate-600">{{ $d->code }}</span><span class="text-[11px] text-slate-400">{{ $d->status }}</span></li>
                @empty
                    <li class="px-3 py-2 text-slate-400">Không có.</li>
                @endforelse
            </ul>
        </div>
    </div>
</div>
