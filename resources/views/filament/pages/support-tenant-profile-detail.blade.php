<div class="space-y-5 text-sm">
    <div class="grid grid-cols-2 gap-3">
        <div><div class="text-xs text-slate-400">Gói hỗ trợ</div><div class="text-slate-700">{{ $record->support_plan ?? '—' }}</div></div>
        <div><div class="text-xs text-slate-400">Tier</div><div class="text-slate-700">{{ $record->tier ?? '—' }}</div></div>
        <div><div class="text-xs text-slate-400">Health score</div><div class="text-slate-700">{{ number_format((float) $record->health_score, 1) }}</div></div>
        <div><div class="text-xs text-slate-400">CSAT</div><div class="text-slate-700">{{ number_format((float) $record->csat, 2) }}</div></div>
        <div><div class="text-xs text-slate-400">Account manager</div><div class="text-slate-700">{{ $record->accountManager?->name ?? '—' }}</div></div>
    </div>
    @if ($record->vip_notes)
        <div class="rounded-lg border border-amber-100 bg-amber-50/60 p-3"><div class="mb-1 text-xs font-semibold uppercase tracking-wide text-amber-500">VIP notes</div><div class="prose prose-sm max-w-none text-slate-700">{!! $record->vip_notes !!}</div></div>
    @endif
    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
        <div>
            <div class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-400">Liên hệ</div>
            <ul class="divide-y divide-slate-50 rounded-lg border border-slate-100">
                @forelse ($record->contacts as $c)
                    <li class="px-3 py-2"><div class="text-slate-700">{{ $c->name }} @if($c->is_primary)<span class="ml-1 rounded-full bg-blue-50 px-1.5 text-[10px] text-blue-600">chính</span>@endif</div><div class="text-[11px] text-slate-400">{{ $c->role }} · {{ $c->email }}</div></li>
                @empty
                    <li class="px-3 py-2 text-slate-400">Chưa có.</li>
                @endforelse
            </ul>
        </div>
        <div>
            <div class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-400">Entitlements</div>
            <ul class="divide-y divide-slate-50 rounded-lg border border-slate-100">
                @forelse ($record->entitlements as $e)
                    <li class="flex items-center justify-between px-3 py-2"><span class="text-slate-700">{{ $e->name }}</span><span class="text-[11px] text-slate-400">{{ $e->value }}</span></li>
                @empty
                    <li class="px-3 py-2 text-slate-400">Chưa có.</li>
                @endforelse
            </ul>
        </div>
    </div>
    <div>
        <div class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-400">Lịch sử ticket</div>
        <ul class="divide-y divide-slate-50 rounded-lg border border-slate-100">
            @forelse ($tickets as $t)
                <li class="flex items-center justify-between px-3 py-2"><span class="text-slate-600">{{ $t->ticket_no }} · {{ Str::limit($t->subject, 30) }}</span><span class="text-[11px] text-slate-400">{{ $t->status }}</span></li>
            @empty
                <li class="px-3 py-2 text-slate-400">Chưa có ticket.</li>
            @endforelse
        </ul>
    </div>
</div>
