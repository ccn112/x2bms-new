@php
    $ctx = app(\App\Support\Context\CurrentContext::class);
    $workspaceLabel = $ctx->workspaceLabel();
    $tenant = $ctx->tenant();
@endphp

{{-- /sa header — single context switcher chip → <livewire:context-switcher> (Company/Project/Workspace). --}}
<div class="flex items-center gap-1.5">
    <button type="button" x-data x-on:click="$dispatch('open-x2-context')"
        class="flex h-11 items-center gap-2.5 rounded-xl border border-gray-200 bg-white px-3 text-left transition hover:border-gray-300 dark:border-white/10 dark:bg-white/5">
        <svg class="h-5 w-5 shrink-0 text-x2-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7"><path stroke-linecap="round" stroke-linejoin="round" d="M3 21h18M5 21V7l7-4 7 4v14M9 9h.01M15 9h.01M9 13h.01M15 13h.01M9 17h6"/></svg>
        <span class="leading-tight">
            <span class="block max-w-[150px] truncate text-[10px] font-medium uppercase tracking-wide text-gray-400">{{ $tenant?->name ?? 'Nền tảng' }}</span>
            <span class="block max-w-[220px] truncate text-sm font-semibold text-gray-900 dark:text-white">{{ $workspaceLabel }}</span>
        </span>
        <svg class="h-4 w-4 shrink-0 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6"/></svg>
    </button>
</div>
