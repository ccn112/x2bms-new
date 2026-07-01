<div class="space-y-4 text-sm">
    <div class="grid grid-cols-2 gap-3">
        <div class="col-span-2"><div class="text-xs text-slate-400">Event ID</div><div class="font-mono text-slate-700">{{ $record->event_id }}</div></div>
        <div class="col-span-2"><div class="text-xs text-slate-400">Correlation ID</div><div class="font-mono text-slate-700">{{ $record->correlation_id ?? '—' }}</div></div>
        <div><div class="text-xs text-slate-400">Nguồn</div><div class="text-slate-700">{{ $record->source }}</div></div>
        <div><div class="text-xs text-slate-400">Loại</div><div class="text-slate-700">{{ $record->event_type }}</div></div>
        <div><div class="text-xs text-slate-400">Tenant</div><div class="text-slate-700">{{ $record->tenant?->name ?? '—' }}</div></div>
        <div><div class="text-xs text-slate-400">Trạng thái</div><div class="text-slate-700">{{ $record->status }}</div></div>
        <div><div class="text-xs text-slate-400">Thời lượng</div><div class="text-slate-700">{{ $record->duration_ms }}ms</div></div>
        <div><div class="text-xs text-slate-400">Retry count</div><div class="text-slate-700">{{ $record->retry_count }}</div></div>
        <div class="col-span-2"><div class="text-xs text-slate-400">Payload hash</div><div class="truncate font-mono text-[11px] text-slate-500">{{ $record->payload_hash ?? '—' }}</div></div>
        <div class="col-span-2"><div class="text-xs text-slate-400">Thông điệp</div><div class="text-slate-700">{{ $record->message ?? '—' }}</div></div>
        <div class="col-span-2"><div class="text-xs text-slate-400">Thời điểm</div><div class="text-slate-700">{{ $record->created_at?->format('d/m/Y H:i:s') }}</div></div>
    </div>
</div>
