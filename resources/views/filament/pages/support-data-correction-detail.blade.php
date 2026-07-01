<div class="space-y-5 text-sm">
    <div class="grid grid-cols-2 gap-3 md:grid-cols-3">
        <div><div class="text-xs text-slate-400">Tenant</div><div class="text-slate-700">{{ $record->tenant?->name ?? '—' }}</div></div>
        <div><div class="text-xs text-slate-400">Loại dữ liệu</div><div class="text-slate-700">{{ $record->data_type }}</div></div>
        <div><div class="text-xs text-slate-400">Bản ghi ảnh hưởng</div><div class="text-slate-700">{{ $record->affected_records }}</div></div>
        <div><div class="text-xs text-slate-400">Rủi ro</div><div class="text-slate-700">{{ ucfirst($record->risk) }}</div></div>
        <div><div class="text-xs text-slate-400">Trạng thái</div><div class="text-slate-700">{{ $record->status }}</div></div>
        <div><div class="text-xs text-slate-400">Người yêu cầu</div><div class="text-slate-700">{{ $record->requestedBy?->name ?? '—' }}</div></div>
    </div>
    @if ($record->reason)
        <div class="rounded-lg border border-slate-100 bg-slate-50/60 p-3"><div class="mb-1 text-xs font-semibold uppercase tracking-wide text-slate-400">Lý do</div><div class="prose prose-sm max-w-none text-slate-700">{!! $record->reason !!}</div></div>
    @endif
    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
        <div>
            <div class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-400">Bản ghi ảnh hưởng (mẫu)</div>
            <ul class="divide-y divide-slate-50 rounded-lg border border-slate-100">
                @forelse ($record->affectedRecords as $a)
                    <li class="flex items-center justify-between px-3 py-2"><span class="text-slate-600">{{ $a->entity }} #{{ $a->record_id }}</span><span class="text-[11px] text-slate-400">{{ $a->identifier }}</span></li>
                @empty
                    <li class="px-3 py-2 text-slate-400">Chưa nhận diện.</li>
                @endforelse
            </ul>
        </div>
        <div>
            <div class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-400">Preview diff (before → after)</div>
            <ul class="divide-y divide-slate-50 rounded-lg border border-slate-100">
                @forelse ($record->diffItems as $d)
                    <li class="px-3 py-2"><div class="text-[11px] text-slate-400">{{ $d->field }}</div><div class="text-slate-600"><span class="text-red-500 line-through">{{ $d->before_value }}</span> → <span class="text-emerald-600">{{ $d->after_value }}</span></div></li>
                @empty
                    <li class="px-3 py-2 text-slate-400">Chưa có diff.</li>
                @endforelse
            </ul>
        </div>
    </div>
    <div>
        <div class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-400">Phê duyệt</div>
        <ul class="divide-y divide-slate-50 rounded-lg border border-slate-100">
            @forelse ($record->approvals as $ap)
                <li class="flex items-center justify-between px-3 py-2"><span class="text-slate-600">{{ $ap->approver?->name ?? '—' }} · {{ $ap->decision }}</span><span class="text-[11px] text-slate-400">{{ $ap->approved_at?->format('d/m H:i') }}</span></li>
            @empty
                <li class="px-3 py-2 text-slate-400">Chưa có phê duyệt.</li>
            @endforelse
        </ul>
    </div>
</div>
