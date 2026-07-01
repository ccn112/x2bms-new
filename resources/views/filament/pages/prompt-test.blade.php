<div class="space-y-4 text-sm">
    <div class="flex flex-wrap gap-2">
        @if ($record->use_case)<span class="rounded bg-blue-50 px-2 py-0.5 text-xs text-blue-700">{{ $useCaseMap[$record->use_case] ?? $record->use_case }}</span>@endif
        <span class="rounded bg-slate-100 px-2 py-0.5 text-xs text-slate-600">{{ $record->code ?? $record->name }}</span>
    </div>

    <div class="rounded-xl border border-slate-100 p-4">
        <p class="mb-1 text-xs font-semibold uppercase text-slate-400">System prompt</p>
        <pre class="whitespace-pre-wrap rounded-lg bg-slate-50 p-3 text-xs text-slate-600">{{ $record->system_prompt ?: '—' }}</pre>
    </div>

    <div class="rounded-xl border border-slate-100 p-4">
        <p class="mb-1 text-xs font-semibold uppercase text-slate-400">User prompt template</p>
        <pre class="whitespace-pre-wrap rounded-lg bg-slate-50 p-3 text-xs text-slate-600">{{ $record->user_prompt_template ?: '—' }}</pre>
    </div>

    <p class="text-xs text-slate-400">Mô phỏng: prompt sẽ được ghép với KB theo phạm vi được phép của người hỏi trước khi gửi X2AI.</p>
</div>
