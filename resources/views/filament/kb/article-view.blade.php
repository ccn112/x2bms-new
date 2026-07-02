@php
    use Illuminate\Support\Facades\Storage;
    $statusMeta = \App\Filament\Hq\Pages\AiKnowledgeBase::STATUS[$record->status] ?? [$record->status, 'gray'];
    $attachments = collect($record->attachments ?? []);
@endphp

<div class="space-y-4 text-sm">
    {{-- Meta --}}
    <div class="flex flex-wrap items-center gap-2 text-xs">
        @if ($record->category)
            <span class="rounded-md bg-slate-100 px-2 py-0.5 font-medium text-slate-600">{{ $record->category->name }}</span>
        @endif
        <x-x2.status-badge :label="$statusMeta[0]" :tone="['published' => 'green', 'draft' => 'slate', 'archived' => 'amber'][$record->status] ?? 'slate'" />
        <span class="text-slate-400">{{ number_format($record->views) }} lượt xem · {{ $record->helpful_count }}/{{ $record->helpful_count + $record->not_helpful_count }} thấy hữu ích</span>
        @if ($record->published_at)
            <span class="text-slate-400">· Xuất bản {{ $record->published_at->format('d/m/Y') }}</span>
        @endif
    </div>

    @if ($record->excerpt)
        <p class="rounded-lg bg-slate-50 p-3 italic text-slate-600">{{ $record->excerpt }}</p>
    @endif

    {{-- Body (HTML do người soạn nhập) --}}
    <div class="x2ai-prose max-w-none text-slate-700">
        {!! $record->body ?: '<p class="text-slate-400">Chưa có nội dung.</p>' !!}
    </div>

    {{-- Attachments --}}
    @if ($attachments->isNotEmpty())
        <div>
            <h4 class="mb-2 font-semibold text-slate-700">Tệp đính kèm ({{ $attachments->count() }})</h4>
            <ul class="space-y-1.5">
                @foreach ($attachments as $path)
                    @php
                        $name = basename($path);
                        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                        $url = Storage::disk('public')->url($path);
                    @endphp
                    <li>
                        <a href="{{ $url }}" target="_blank" rel="noopener"
                           class="flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 transition hover:border-x2-gold hover:bg-x2-gold/5">
                            <span @class([
                                'grid h-8 w-8 shrink-0 place-items-center rounded-lg text-[10px] font-bold uppercase',
                                'bg-x2-red/10 text-x2-red' => $ext === 'pdf',
                                'bg-x2-blue/10 text-x2-blue' => in_array($ext, ['doc', 'docx']),
                                'bg-slate-100 text-slate-500' => ! in_array($ext, ['pdf', 'doc', 'docx']),
                            ])>{{ $ext ?: 'file' }}</span>
                            <span class="min-w-0 flex-1 truncate font-medium text-slate-700">{{ $name }}</span>
                            <svg class="h-4 w-4 shrink-0 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25"/></svg>
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
    @else
        <p class="text-xs text-slate-400">Chưa có tệp đính kèm.</p>
    @endif
</div>
