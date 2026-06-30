<div class="flex min-h-0 flex-1 flex-col">
    {{-- DATA AREA: history list OR conversation. Scrolls independently of the input.
         Inline max-height (panel 66vh − header/input ≈ 7.5rem) guarantees scrolling
         regardless of how the flex chain resolves across the Livewire boundary. --}}
    <div class="min-h-0 flex-1 space-y-2 overflow-y-auto p-4 text-sm"
         style="max-height: calc(66vh - 7.5rem)"
         x-data x-init="$el.scrollTop = $el.scrollHeight"
         x-on:x2ai-scroll.window="$nextTick(() => $el.scrollTop = $el.scrollHeight)">
        @if ($showHistory)
            <div class="mb-1 text-xs font-semibold uppercase tracking-wide text-x2-navy">Lịch sử trò chuyện</div>
            @forelse ($sessions as $s)
                <button type="button" wire:click="loadSession({{ $s['id'] }})"
                        class="block w-full rounded-lg border border-slate-100 bg-white p-2.5 text-left shadow-sm transition hover:border-x2-gold">
                    <div class="flex items-center justify-between gap-2">
                        <span class="truncate font-medium text-slate-800">{{ $s['title'] }}</span>
                        <span class="shrink-0 text-[11px] text-slate-400">{{ $s['time'] }}</span>
                    </div>
                    @if (! empty($s['surface']))
                        <div class="truncate text-[11px] text-slate-500">{{ $s['surface'] }}</div>
                    @endif
                </button>
            @empty
                <div class="text-sm text-slate-400">Chưa có cuộc trò chuyện nào được lưu.</div>
            @endforelse
        @else
            {{-- Per-screen context (fed by ProvidesAiContext via pageContext). --}}
            @if ($pageContext)
                @if (! empty($pageContext['title']))
                    <div class="text-xs font-semibold uppercase tracking-wide text-x2-navy">{{ $pageContext['title'] }}</div>
                @endif
                @foreach ($pageContext['lines'] ?? [] as $line)
                    <div class="flex items-start gap-2 text-slate-700">
                        <span class="mt-1.5 h-1.5 w-1.5 shrink-0 rounded-full bg-x2-red"></span>{{ $line }}
                    </div>
                @endforeach
                @foreach ($pageContext['suggestions'] ?? [] as $s)
                    <div class="rounded-lg border border-slate-100 bg-white p-2.5 shadow-sm">
                        <div class="flex items-center justify-between">
                            <span class="font-medium text-slate-800">{{ $s['title'] ?? '' }}</span>
                            @if (! empty($s['amount']))
                                <span class="text-xs font-semibold text-x2-navy">{{ $s['amount'] }}</span>
                            @endif
                        </div>
                        @if (! empty($s['sub']))
                            <div class="text-xs text-slate-500">{{ $s['sub'] }}</div>
                        @endif
                    </div>
                @endforeach
            @endif

            <div class="rounded-lg bg-slate-100 px-3 py-2 text-slate-700">{{ $greeting }}</div>

            @foreach ($messages as $m)
                @if ($m['role'] === 'user')
                    <div class="ml-6 rounded-lg bg-x2-navy px-3 py-2 text-sm text-white">{{ $m['content'] }}</div>
                @else
                    <div class="x2ai-prose mr-6 rounded-lg bg-slate-100 px-3 py-2 text-sm text-slate-700">{!! $m['html'] ?? e($m['content']) !!}</div>
                @endif
            @endforeach

            {{-- Awaiting reply: shown right after the prompt; x-init fires the model call (step 2). --}}
            @if ($awaitingReply)
                <div wire:key="awaiting-{{ count($messages) }}" x-init="$wire.generate()"
                     class="mr-6 flex items-center gap-1.5 rounded-lg bg-slate-100 px-3 py-2 text-sm text-slate-400">
                    <span class="h-1.5 w-1.5 animate-bounce rounded-full bg-slate-400"></span>
                    <span class="h-1.5 w-1.5 animate-bounce rounded-full bg-slate-400" style="animation-delay:.15s"></span>
                    <span class="h-1.5 w-1.5 animate-bounce rounded-full bg-slate-400" style="animation-delay:.3s"></span>
                </div>
            @endif
        @endif
    </div>

    {{-- INPUT BAR — pinned to the bottom. --}}
    <div class="shrink-0 space-y-2 border-t border-slate-100 bg-white p-3">
        @if ($attachments)
            <div class="flex flex-wrap gap-1.5">
                @foreach ($attachments as $f)
                    <span class="inline-flex items-center gap-1 rounded-md bg-slate-100 px-2 py-1 text-[11px] text-slate-600">
                        📎 {{ \Illuminate\Support\Str::limit($f->getClientOriginalName(), 18) }}
                    </span>
                @endforeach
            </div>
        @endif
        <div wire:loading wire:target="attachments" class="text-[11px] text-slate-400">Đang tải tệp…</div>
        @error('attachments.*') <div class="text-[11px] text-x2-red">{{ $message }}</div> @enderror

        <form x-on:submit.prevent="$wire.submit(window.x2aiCaptureScreen ? window.x2aiCaptureScreen() : null)" class="flex items-center gap-2">
            <label class="grid h-8 w-8 shrink-0 cursor-pointer place-items-center rounded-lg border border-slate-200 text-slate-500 hover:bg-slate-50" title="Đính kèm ảnh/PDF">
                <input type="file" wire:model="attachments" multiple accept="image/*,application/pdf" class="hidden">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M18 8.5 9.6 16.9a2.5 2.5 0 1 1-3.5-3.5l8-8a4 4 0 0 1 5.7 5.7l-8.5 8.5a5.5 5.5 0 0 1-7.8-7.8L12 7"/></svg>
            </label>
            <input type="text" wire:model="input" @disabled($awaitingReply)
                   placeholder="Hỏi X2AI..."
                   class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 placeholder:text-slate-400 focus:border-x2-gold focus:outline-none disabled:opacity-60">
            <button type="submit" @disabled($awaitingReply)
                    class="grid h-8 w-8 shrink-0 place-items-center rounded-lg bg-x2-navy text-white disabled:opacity-50">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14m0 0-6-6m6 6-6 6"/></svg>
            </button>
        </form>
    </div>
</div>
