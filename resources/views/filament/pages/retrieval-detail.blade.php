@if (! $log)
    <p class="text-sm text-slate-400">Không tìm thấy bản ghi.</p>
@else
    <div class="space-y-4 text-sm">
        <div class="rounded-xl border border-slate-100 p-4">
            <p class="mb-1 text-xs font-semibold uppercase text-slate-400">Câu hỏi</p>
            <p class="text-slate-700">{{ $log->question ?: '—' }}</p>
        </div>
        <div class="rounded-xl border border-slate-100 p-4">
            <p class="mb-1 text-xs font-semibold uppercase text-slate-400">Tóm tắt trả lời</p>
            <p class="text-slate-600">{{ $log->answer_summary ?: '—' }}</p>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div class="rounded-xl border border-green-100 bg-green-50/40 p-4">
                <p class="mb-1 font-semibold text-green-700">Tài liệu đã dùng ({{ count($log->retrieved_document_ids_json ?? []) }})</p>
                <p class="text-xs text-slate-600">{{ implode(', ', $log->retrieved_document_ids_json ?? []) ?: '—' }}</p>
            </div>
            <div class="rounded-xl border border-red-100 bg-red-50/40 p-4">
                <p class="mb-1 font-semibold text-red-700">Tài liệu bị chặn ({{ count($log->blocked_document_ids_json ?? []) }})</p>
                <p class="text-xs text-slate-600">{{ implode(', ', $log->blocked_document_ids_json ?? []) ?: '—' }}</p>
            </div>
        </div>

        <div class="rounded-xl border border-slate-100 p-4">
            <p class="mb-1 text-xs font-semibold uppercase text-slate-400">Snapshot quyền</p>
            <pre class="max-h-40 overflow-auto whitespace-pre-wrap rounded-lg bg-slate-50 p-3 text-xs text-slate-600">{{ json_encode($log->permission_snapshot_json ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
        </div>

        <dl class="grid grid-cols-3 gap-2 text-slate-500">
            <div><dt class="inline">Model:</dt> <dd class="inline">{{ $log->model ?? '—' }}</dd></div>
            <div><dt class="inline">Token:</dt> <dd class="inline">{{ number_format($log->token_input + $log->token_output) }}</dd></div>
            <div><dt class="inline">Độ trễ:</dt> <dd class="inline">{{ $log->latency_ms }}ms</dd></div>
        </dl>
    </div>
@endif
