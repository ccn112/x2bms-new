@props([
    'title' => 'Trợ lý AI X2AI',
    'suggestions' => [], // [['title'=>..,'detail'=>..], ...] from ai_suggestions
])

{{-- X2AiPanel — X2AI assistant. Suggestions come from ai_suggestions (seeded), not hardcoded.
     Livewire-ready: promote when prompt/accept/reject interaction is wired. --}}
<section class="flex h-full flex-col rounded-xl border border-x2-primary/20 bg-gradient-to-b from-x2-primary/5 to-white shadow-sm">
    <div class="flex items-center gap-2 border-b border-x2-primary/10 px-4 py-3">
        <span class="grid h-7 w-7 place-items-center rounded-lg bg-x2-primary text-xs font-bold text-white">AI</span>
        <h3 class="text-sm font-semibold text-slate-800">{{ $title }}</h3>
    </div>

    <div class="flex-1 space-y-2 p-4">
        @forelse ($suggestions as $s)
            <div class="rounded-lg border border-slate-100 bg-white p-3">
                <div class="text-sm font-medium text-slate-800">{{ $s['title'] }}</div>
                @if (!empty($s['detail']))
                    <div class="mt-0.5 text-xs text-slate-500">{{ $s['detail'] }}</div>
                @endif
            </div>
        @empty
            <div class="py-6 text-center text-sm text-slate-400">Chưa có gợi ý</div>
        @endforelse
    </div>

    <div class="border-t border-x2-primary/10 p-3">
        <div class="flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-400">
            <span class="flex-1">Hỏi X2AI…</span>
            <span class="grid h-6 w-6 place-items-center rounded-md bg-x2-primary text-white">
                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14m0 0-6-6m6 6-6 6"/></svg>
            </span>
        </div>
    </div>
</section>
