@php
    use Illuminate\Support\Facades\Storage;
    $st = $record->status;
    // Gộp timeline: status history + comment + assignment, sắp theo thời gian.
    $events = collect();
    foreach ($record->statusHistories as $h) {
        $events->push(['at' => $h->changed_at ?? $h->created_at, 'type' => 'status', 'who' => $h->changedBy?->name, 'text' => 'Chuyển trạng thái → '.($h->to_status), 'note' => $h->note]);
    }
    foreach ($record->comments as $c) {
        $events->push(['at' => $c->created_at, 'type' => $c->is_internal ? 'internal' : 'comment', 'who' => $c->author_name ?? $c->user?->name, 'text' => $c->body, 'note' => null]);
    }
    foreach ($record->assignments as $a) {
        $events->push(['at' => $a->assigned_at ?? $a->created_at, 'type' => 'assign', 'who' => $a->assignee?->name, 'text' => 'Được giao xử lý', 'note' => $a->note]);
    }
    $events = $events->sortByDesc('at')->values();
    $dot = ['status' => 'bg-x2-blue', 'comment' => 'bg-x2-green', 'internal' => 'bg-x2-gold', 'assign' => 'bg-x2-navy'];
@endphp

<div class="space-y-4 text-sm">
    {{-- Meta --}}
    <div class="flex flex-wrap items-center gap-2 text-xs">
        @if ($record->category)<span class="rounded-md bg-slate-100 px-2 py-0.5 font-medium text-slate-600">{{ $record->category->name }}</span>@endif
        <x-x2.status-badge :label="$st->label()" :tone="$st->tone()" />
        <span class="rounded-md bg-slate-100 px-2 py-0.5 text-slate-600">Ưu tiên: {{ $record->priority }}</span>
        @if ($record->apartment)<span class="text-slate-400">· Căn {{ $record->apartment->code }}</span>@endif
        @if ($record->resident)<span class="text-slate-400">· {{ $record->resident->full_name ?? $record->resident->name ?? '' }}</span>@endif
        @if ($record->sla_due_at)<span @class(['text-slate-400', 'text-x2-red font-semibold' => $record->sla_due_at->isPast()])>· SLA {{ $record->sla_due_at->diffForHumans() }}</span>@endif
    </div>

    @if ($record->description)
        <p class="rounded-lg bg-slate-50 p-3 text-slate-700">{{ $record->description }}</p>
    @endif

    {{-- Tệp đính kèm --}}
    @if ($record->attachments->isNotEmpty())
        <div>
            <h4 class="mb-1.5 font-semibold text-slate-700">Tệp đính kèm ({{ $record->attachments->count() }})</h4>
            <div class="flex flex-wrap gap-2">
                @foreach ($record->attachments as $att)
                    <a href="{{ Storage::disk('public')->url($att->path) }}" target="_blank" rel="noopener"
                       class="rounded-lg border border-slate-200 px-2.5 py-1 text-xs text-slate-600 hover:border-x2-gold">{{ $att->name ?? basename($att->path) }}</a>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Timeline --}}
    <div>
        <h4 class="mb-2 font-semibold text-slate-700">Diễn tiến xử lý</h4>
        <ol class="space-y-3 border-l-2 border-slate-100 pl-4">
            @forelse ($events as $e)
                <li class="relative">
                    <span class="absolute -left-[1.35rem] top-1 h-2.5 w-2.5 rounded-full {{ $dot[$e['type']] ?? 'bg-slate-300' }}"></span>
                    <div class="text-slate-700">
                        {{ $e['text'] }}
                        @if ($e['type'] === 'internal')<span class="ml-1 rounded bg-x2-gold/15 px-1 text-[10px] font-semibold text-x2-navy">nội bộ</span>@endif
                    </div>
                    @if (! empty($e['note']))<div class="text-xs text-slate-500">{{ $e['note'] }}</div>@endif
                    <div class="text-xs text-slate-400">{{ $e['who'] ?? 'Hệ thống' }} · {{ $e['at']?->format('d/m/Y H:i') }}</div>
                </li>
            @empty
                <li class="text-sm text-slate-400">Chưa có diễn tiến.</li>
            @endforelse
        </ol>
    </div>

    @if ($record->rating)
        <div class="rounded-lg bg-x2-green/5 p-3 text-sm text-x2-green">Đánh giá của cư dân: {{ str_repeat('★', (int) $record->rating) }} ({{ $record->rating }}/5)</div>
    @endif
</div>
