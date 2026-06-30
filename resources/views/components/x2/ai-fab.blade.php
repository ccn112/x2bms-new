{{--
    X2AI floating chat — the ONE shared AI surface (owner rule: AI chat is always
    a floating panel, never an inline column that compresses page content).
    Rendered globally via PanelsRenderHook::BODY_END.

    Two sizes (Alpine `expanded`):
      - default  : narrow (w-96), 2/3 viewport height.
      - expanded : 1/2 viewport width × 2/3 viewport height.
    The conversation scrolls; the input stays pinned to the bottom (inside the
    Livewire component). Mode (context vs DB lookup) is permission-driven — no toggle.
--}}
@props(['greeting' => 'Xin chào! Tôi là X2AI. Tôi có thể giúp gì cho công việc vận hành hôm nay?'])

@php
    // Per-screen context fed in by the current page via view()->share('x2aiContext', ...).
    $ctx = $x2aiContext ?? null;
@endphp

{{-- Any screen can open this shared chat (and optionally prefill a prompt) by
     dispatching window event `x2ai-open`; see AiCenter "Gợi ý nhanh" & KB Copilot. --}}
<div x-data="{ open: false, expanded: false }" x-on:x2ai-open.window="open = true" class="x2ai-fab">
    {{-- Chat popover --}}
    <div x-show="open" x-transition x-cloak
         :class="expanded ? 'w-[50vw]' : 'w-96'" style="height: 66vh"
         class="fixed bottom-24 right-6 z-50 flex flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl">
        <div class="flex shrink-0 items-center justify-between bg-x2-navy px-4 py-3">
            <div class="flex items-center gap-2">
                <span class="grid h-7 w-7 place-items-center rounded-lg bg-white/10 text-xs font-bold text-x2-gold">AI</span>
                <span class="font-title text-sm font-semibold text-white">X2AI</span>
                <span class="rounded bg-white/15 px-1.5 py-0.5 text-[10px] font-semibold text-x2-gold">Beta</span>
            </div>
            <div class="flex items-center gap-1">
                <button type="button" @click="Livewire.dispatch('x2ai-new-chat')"
                        class="grid h-7 w-7 place-items-center rounded text-white/70 hover:bg-white/10 hover:text-white"
                        title="Cuộc trò chuyện mới">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14"/></svg>
                </button>
                <button type="button" @click="Livewire.dispatch('x2ai-history')"
                        class="grid h-7 w-7 place-items-center rounded text-white/70 hover:bg-white/10 hover:text-white"
                        title="Lịch sử trò chuyện">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 2m6-2a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                </button>
                <button type="button" @click="expanded = !expanded"
                        class="grid h-7 w-7 place-items-center rounded text-white/70 hover:bg-white/10 hover:text-white"
                        :title="expanded ? 'Thu nhỏ' : 'Mở rộng'">
                    <svg x-show="!expanded" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9 4H5a1 1 0 0 0-1 1v4m11-5h4a1 1 0 0 1 1 1v4M9 20H5a1 1 0 0 1-1-1v-4m11 5h4a1 1 0 0 0 1-1v-4"/></svg>
                    <svg x-show="expanded" x-cloak class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5v4a1 1 0 0 1-1 1H4m16 0h-4a1 1 0 0 1-1-1V5M4 14h4a1 1 0 0 1 1 1v4m11-5h-4a1 1 0 0 0-1 1v4"/></svg>
                </button>
                <button type="button" @click="open = false" class="grid h-7 w-7 place-items-center rounded text-white/70 hover:bg-white/10 hover:text-white">✕</button>
            </div>
        </div>

        {{-- Interactive chat (WEB-UX-09). Fills remaining height; input pins to the bottom. --}}
        @livewire('x2ai-chat', ['pageContext' => $ctx, 'greeting' => $greeting], key('x2ai-chat'))
    </div>

    {{-- Floating button --}}
    <button type="button" @click="open = !open"
            class="fixed bottom-6 right-6 z-50 grid h-14 w-14 place-items-center rounded-full bg-x2-navy text-x2-gold shadow-xl ring-2 ring-x2-gold/30 transition hover:scale-105"
            title="Hỏi X2AI">
        <span class="text-lg font-bold">AI</span>
    </button>
</div>

{{-- Markdown bubble styling (assistant replies are rendered Markdown → tables/lists/headings). --}}
<style>
    .x2ai-prose > :first-child { margin-top: 0; }
    .x2ai-prose > :last-child { margin-bottom: 0; }
    .x2ai-prose p { margin: .35rem 0; }
    .x2ai-prose h1, .x2ai-prose h2, .x2ai-prose h3, .x2ai-prose h4 { font-weight: 700; color: #0f2747; line-height: 1.25; margin: .5rem 0 .25rem; }
    .x2ai-prose h1 { font-size: 1rem; } .x2ai-prose h2 { font-size: .95rem; } .x2ai-prose h3, .x2ai-prose h4 { font-size: .9rem; }
    .x2ai-prose ul, .x2ai-prose ol { margin: .35rem 0; padding-left: 1.15rem; }
    .x2ai-prose ul { list-style: disc; } .x2ai-prose ol { list-style: decimal; }
    .x2ai-prose li { margin: .12rem 0; }
    .x2ai-prose a { color: #1d4ed8; text-decoration: underline; }
    .x2ai-prose strong { font-weight: 600; color: #0f2747; }
    .x2ai-prose code { background: #e2e8f0; border-radius: 4px; padding: .05rem .3rem; font-size: .82em; }
    .x2ai-prose pre { background: #0f2747; color: #e2e8f0; border-radius: 8px; padding: .6rem .75rem; overflow-x: auto; margin: .4rem 0; }
    .x2ai-prose pre code { background: transparent; padding: 0; color: inherit; }
    .x2ai-prose blockquote { border-left: 3px solid #cbd5e1; padding-left: .6rem; color: #475569; margin: .4rem 0; }
    .x2ai-prose table { width: 100%; border-collapse: collapse; margin: .45rem 0; font-size: .8rem; display: block; overflow-x: auto; }
    .x2ai-prose th, .x2ai-prose td { border: 1px solid #e2e8f0; padding: .35rem .5rem; text-align: left; vertical-align: top; }
    .x2ai-prose thead th { background: #f1f5f9; color: #0f2747; font-weight: 600; white-space: nowrap; }
    .x2ai-prose tbody tr:nth-child(even) { background: #f8fafc; }
</style>

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
