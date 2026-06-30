@props([
    'title' => 'Trợ lý AI X2AI',
    'suggestions' => [], // [['title'=>..,'detail'=>..], ...] from ai_suggestions
])

{{-- X2AiPanel — read-only X2AI suggestions (from ai_suggestions, not hardcoded).
     Per the X2AI rule the interactive chat lives only in the global floating
     <x-x2.ai-fab>; this panel never carries an inline chat input. --}}
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
</section>
