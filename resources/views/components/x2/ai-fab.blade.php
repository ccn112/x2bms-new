{{--
    X2AI floating chat — the ONE shared AI surface (owner rule: AI chat is always
    a floating panel, never an inline column that compresses page content).
    Rendered globally via PanelsRenderHook::BODY_END; can also be dropped on any
    view. Optional $slot adds contextual quick-help above the greeting.
--}}
@props(['greeting' => 'Xin chào! Tôi là X2AI. Tôi có thể giúp gì cho công việc vận hành hôm nay?'])

@php
    // Per-screen context fed in by the current page via view()->share('x2aiContext', ...).
    $ctx = $x2aiContext ?? null;
@endphp

{{-- Any screen can open this shared chat (and optionally prefill a prompt) by
     dispatching window event `x2ai-open`; see AiCenter "Gợi ý nhanh" & KB Copilot. --}}
<div x-data="{ open: false }" x-on:x2ai-open.window="open = true" class="x2ai-fab">
    {{-- Chat popover --}}
    <div x-show="open" x-transition x-cloak
         class="fixed bottom-24 right-6 z-50 w-96 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl">
        <div class="flex items-center justify-between bg-x2-navy px-4 py-3">
            <div class="flex items-center gap-2">
                <span class="grid h-7 w-7 place-items-center rounded-lg bg-white/10 text-xs font-bold text-x2-gold">AI</span>
                <span class="font-title text-sm font-semibold text-white">X2AI</span>
                <span class="rounded bg-white/15 px-1.5 py-0.5 text-[10px] font-semibold text-x2-gold">Beta</span>
            </div>
            <button type="button" @click="open = false" class="text-white/70 hover:text-white">✕</button>
        </div>
        <div class="max-h-[21.6rem] space-y-2 overflow-y-auto p-4 text-sm">
            {{ $slot }}

            @if ($ctx)
                @if (! empty($ctx['title']))
                    <div class="text-xs font-semibold uppercase tracking-wide text-x2-navy">{{ $ctx['title'] }}</div>
                @endif
                @foreach ($ctx['lines'] ?? [] as $line)
                    <div class="flex items-start gap-2 text-slate-700">
                        <span class="mt-1.5 h-1.5 w-1.5 shrink-0 rounded-full bg-x2-red"></span>{{ $line }}
                    </div>
                @endforeach
                @foreach ($ctx['suggestions'] ?? [] as $s)
                    <div class="rounded-lg border border-slate-100 bg-white p-2.5 shadow-sm">
                        <div class="flex items-center justify-between">
                            <span class="font-medium text-slate-800">{{ $s['title'] }}</span>
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

            <div class="mb-3 rounded-lg bg-slate-100 px-3 py-2 text-slate-700">{{ $greeting }}</div>

            {{-- Interactive chat (WEB-UX-09) — calls the Messages API via X2aiClient. --}}
            @livewire('x2ai-chat', ['pageContext' => $ctx], key('x2ai-chat'))
        </div>
    </div>

    {{-- Floating button --}}
    <button type="button" @click="open = !open"
            class="fixed bottom-6 right-6 z-50 grid h-14 w-14 place-items-center rounded-full bg-x2-navy text-x2-gold shadow-xl ring-2 ring-x2-gold/30 transition hover:scale-105"
            title="Hỏi X2AI">
        <span class="text-lg font-bold">AI</span>
    </button>
</div>

{{-- Reads the current screen from the DOM (client-side) so X2AI sees what the user sees.
     Captures the Filament main content area only — the floating chat lives outside it. --}}
<script>
    window.x2aiCaptureScreen = window.x2aiCaptureScreen || function () {
        try {
            var root = document.querySelector('.fi-main') || document.querySelector('main') || document.body;
            var text = (root && root.innerText ? root.innerText : '').replace(/\n{3,}/g, '\n\n').trim();
            if (text.length > 6000) { text = text.slice(0, 6000) + '…'; }
            return 'URL: ' + location.pathname + '\nTiêu đề: ' + document.title + '\n\n' + text;
        } catch (e) { return ''; }
    };
</script>
