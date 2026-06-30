<div>
    {{-- Mode selector --}}
    <div class="mb-2 flex rounded-lg bg-slate-100 p-0.5 text-xs font-medium">
        <button type="button" wire:click="$set('mode','context')"
                @class(['flex-1 rounded-md px-2 py-1.5 transition', 'bg-white text-x2-navy shadow-sm' => $mode === 'context', 'text-slate-500' => $mode !== 'context'])>
            Ngữ cảnh màn hình
        </button>
        <button type="button" wire:click="$set('mode','data')"
                @class(['flex-1 rounded-md px-2 py-1.5 transition', 'bg-white text-x2-navy shadow-sm' => $mode === 'data', 'text-slate-500' => $mode !== 'data'])>
            Tra cứu CSDL
        </button>
    </div>
    <p class="mb-2 text-[11px] text-slate-400">
        @if ($mode === 'context')
            Đọc màn hình hiện tại + màn hình/quyền của bạn. Có thể đính kèm ảnh/PDF để X2AI đọc.
        @else
            X2AI tra cứu dữ liệu thật trong hệ thống khi cần số liệu cụ thể.
        @endif
    </p>

    {{-- Conversation --}}
    @if (count($messages))
        <div class="mb-2 space-y-2">
            @foreach ($messages as $m)
                @if ($m['role'] === 'user')
                    <div class="ml-6 rounded-lg bg-x2-navy px-3 py-2 text-sm text-white">{{ $m['content'] }}</div>
                @else
                    <div class="mr-6 whitespace-pre-line rounded-lg bg-slate-100 px-3 py-2 text-sm text-slate-700">{{ $m['content'] }}</div>
                @endif
            @endforeach
        </div>
    @endif

    {{-- Thinking indicator --}}
    <div wire:loading wire:target="send" class="mr-6 mb-2 flex items-center gap-1.5 rounded-lg bg-slate-100 px-3 py-2 text-sm text-slate-400">
        <span class="h-1.5 w-1.5 animate-bounce rounded-full bg-slate-400"></span>
        <span class="h-1.5 w-1.5 animate-bounce rounded-full bg-slate-400" style="animation-delay:.15s"></span>
        <span class="h-1.5 w-1.5 animate-bounce rounded-full bg-slate-400" style="animation-delay:.3s"></span>
    </div>

    {{-- Attachments (context mode) --}}
    @if ($mode === 'context' && $attachments)
        <div class="mb-2 flex flex-wrap gap-1.5">
            @foreach ($attachments as $f)
                <span class="inline-flex items-center gap-1 rounded-md bg-slate-100 px-2 py-1 text-[11px] text-slate-600">
                    📎 {{ \Illuminate\Support\Str::limit($f->getClientOriginalName(), 18) }}
                </span>
            @endforeach
        </div>
    @endif
    <div wire:loading wire:target="attachments" class="mb-2 text-[11px] text-slate-400">Đang tải tệp…</div>
    @error('attachments.*') <div class="mb-2 text-[11px] text-x2-red">{{ $message }}</div> @enderror

    {{-- Input --}}
    <form x-on:submit.prevent="$wire.send(window.x2aiCaptureScreen ? window.x2aiCaptureScreen() : null)" class="flex items-center gap-2">
        @if ($mode === 'context')
            <label class="grid h-8 w-8 shrink-0 cursor-pointer place-items-center rounded-lg border border-slate-200 text-slate-500 hover:bg-slate-50" title="Đính kèm ảnh/PDF">
                <input type="file" wire:model="attachments" multiple accept="image/*,application/pdf" class="hidden">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M18 8.5 9.6 16.9a2.5 2.5 0 1 1-3.5-3.5l8-8a4 4 0 0 1 5.7 5.7l-8.5 8.5a5.5 5.5 0 0 1-7.8-7.8L12 7"/></svg>
            </label>
        @endif
        <input type="text" wire:model="input" wire:loading.attr="disabled" wire:target="send"
               placeholder="Hỏi X2AI..."
               class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 placeholder:text-slate-400 focus:border-x2-gold focus:outline-none">
        <button type="submit" wire:loading.attr="disabled" wire:target="send"
                class="grid h-8 w-8 shrink-0 place-items-center rounded-lg bg-x2-navy text-white disabled:opacity-50">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14m0 0-6-6m6 6-6 6"/></svg>
        </button>
    </form>
</div>
