@php
    use App\Enums\WorkOrderStatus;
    use Illuminate\Support\Facades\Storage;
@endphp

@if (! $record)
    <p class="text-sm text-slate-400">Không tìm thấy công việc.</p>
@else
    <div class="space-y-4 text-sm">
        <div class="flex flex-wrap items-center gap-2 text-xs">
            <x-x2.status-badge :label="$record->status->label()" :tone="$record->status->tone()" />
            <span class="rounded-md bg-slate-100 px-2 py-0.5 text-slate-600">Ưu tiên: {{ $record->priority }}</span>
            @if ($record->category)<span class="text-slate-400">· {{ $record->category }}</span>@endif
            @if ($record->apartment)<span class="text-slate-400">· Căn {{ $record->apartment->code }}</span>@endif
            <span class="text-slate-400">· Người xử lý: {{ $record->assignee?->name ?? 'Chưa giao' }}</span>
            @if ($record->cost)<span class="text-slate-400">· Chi phí: {{ number_format($record->cost) }}đ</span>@endif
        </div>

        @if ($record->description)
            <p class="rounded-lg bg-slate-50 p-3 text-slate-700">{{ $record->description }}</p>
        @endif

        {{-- Checklist --}}
        @foreach ($record->checklists as $cl)
            <div>
                <h4 class="mb-1.5 font-semibold text-slate-700">{{ $cl->name }}</h4>
                <ul class="space-y-1">
                    @foreach ($cl->items as $it)
                        <li class="flex items-center gap-2">
                            <span @class(['grid h-4 w-4 place-items-center rounded border text-[10px]', 'border-x2-green bg-x2-green text-white' => $it->is_done, 'border-slate-300 text-transparent' => ! $it->is_done])>✓</span>
                            <span @class(['text-slate-700', 'text-slate-400 line-through' => $it->is_done])>{{ $it->label }}</span>
                            @if ($it->is_done && $it->done_at)<span class="text-xs text-slate-400">· {{ $it->done_at->format('d/m H:i') }}</span>@endif
                        </li>
                    @endforeach
                </ul>
            </div>
        @endforeach

        {{-- Đính kèm --}}
        @if ($record->attachments->isNotEmpty())
            <div>
                <h4 class="mb-1.5 font-semibold text-slate-700">Minh chứng ({{ $record->attachments->count() }})</h4>
                <div class="flex flex-wrap gap-2">
                    @foreach ($record->attachments as $att)
                        <a href="{{ Storage::disk('public')->url($att->path) }}" target="_blank" rel="noopener"
                           class="rounded-lg border border-slate-200 px-2.5 py-1 text-xs text-slate-600 hover:border-x2-gold">{{ $att->type }}: {{ $att->name ?? basename($att->path) }}</a>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Nghiệm thu --}}
        @if ($record->signatures->isNotEmpty())
            <div>
                <h4 class="mb-1.5 font-semibold text-slate-700">Chữ ký nghiệm thu</h4>
                <ul class="space-y-1">
                    @foreach ($record->signatures as $sig)
                        <li class="text-slate-600">✍ {{ $sig->signer_name }} ({{ $sig->signer_role }}) · {{ $sig->signed_at?->format('d/m/Y H:i') }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Lịch sử giao việc --}}
        @if ($record->assignments->isNotEmpty())
            <div>
                <h4 class="mb-1.5 font-semibold text-slate-700">Giao việc</h4>
                <ul class="space-y-1">
                    @foreach ($record->assignments as $a)
                        <li class="text-slate-600">{{ $a->assignee?->name ?? '—' }} · {{ $a->status }} · {{ ($a->assigned_at ?? $a->created_at)?->format('d/m H:i') }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>
@endif
