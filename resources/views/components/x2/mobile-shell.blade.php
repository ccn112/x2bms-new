@auth
@php
    $ctx = app(\App\Support\Context\CurrentContext::class);
    $project = $ctx->project();
    $buildings = $ctx->buildings();
    $projects = $ctx->availableProjects();
    $buildingIds = $ctx->buildingIds() ?: [0];
    $buildingLabel = $buildings->count() === 1 ? $buildings->first()?->name : $buildings->count().' tòa';

    $approvalCount = \App\Models\ResidentApprovalRequest::whereIn('building_id', $buildingIds)->where('status', 'pending')->count();
    $notifCount = \App\Models\IocAlert::whereIn('building_id', $buildingIds)->where('status', 'open')->count()
        + \App\Models\SlaEvent::whereIn('building_id', $buildingIds)->where('status', 'open')->count();

    $panelId = filament()->getId();
    $logoutUrl = route('filament.'.$panelId.'.auth.logout');
@endphp

{{-- WEB-UX-MOBILE — responsive app shell (phones/tablets < lg). Desktop keeps the
     native Filament sidebar + topbar; here we show a compact header + context row and
     transform actions into a left drawer (native sidebar), a full-screen search overlay
     and a bottom sheet. --}}
<div class="lg:hidden" x-data="x2MobileShell()" x-on:keydown.escape.window="closeTop()">
    {{-- ===== Compact header ===== --}}
    <header class="sticky top-0 z-30 border-b border-slate-200 bg-white/95 backdrop-blur dark:border-white/10 dark:bg-gray-900/95">
        <div class="flex items-center gap-2 px-3 py-2.5">
            <button type="button" aria-label="Mở menu"
                x-on:click="$store.sidebar && $store.sidebar.open()"
                class="grid h-11 w-11 shrink-0 place-items-center rounded-xl text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-white/5">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
            </button>

            <button type="button" x-on:click="$dispatch('open-x2-search')"
                class="flex h-11 flex-1 items-center gap-2.5 rounded-xl border border-slate-200 bg-slate-50 px-3.5 text-sm text-slate-400 dark:border-white/10 dark:bg-white/5">
                <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.3-4.3M11 19a8 8 0 1 1 0-16 8 8 0 0 1 0 16Z"/></svg>
                <span class="truncate">Tìm căn hộ, cư dân…</span>
            </button>

            <button type="button" aria-label="Thêm hành động" x-on:click="actions = true"
                class="relative grid h-11 w-11 shrink-0 place-items-center rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-100 dark:border-white/10 dark:text-slate-300 dark:hover:bg-white/5">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 12h.01M12 12h.01M18 12h.01"/></svg>
                @if ($notifCount + $approvalCount > 0)
                    <span class="absolute right-1.5 top-1.5 h-2 w-2 rounded-full bg-x2-red"></span>
                @endif
            </button>
        </div>

        {{-- Context selector row → unified context switcher modal --}}
        <button type="button" x-on:click="$dispatch('open-x2-context')"
            class="mx-3 mb-2.5 flex h-11 w-[calc(100%-1.5rem)] items-center gap-2.5 rounded-xl border border-slate-200 bg-white px-3 text-left dark:border-white/10 dark:bg-gray-900">
            <span class="grid h-7 w-7 shrink-0 place-items-center rounded-lg bg-x2-primary/10 text-x2-primary">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h6M9 12h6"/></svg>
            </span>
            <span class="flex-1 truncate text-sm font-medium text-slate-800 dark:text-slate-100">{{ $project?->name ?? 'Chọn dự án' }} · {{ $buildingLabel ?: 'Tòa' }}</span>
            <svg class="h-4 w-4 shrink-0 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6"/></svg>
        </button>
    </header>

    {{-- Global search is the shared <livewire:global-search> palette (opened via
         the 'open-x2-search' event dispatched by the search button above). --}}

    {{-- ===== Overflow bottom sheet ===== --}}
    <div x-show="actions" x-cloak class="fixed inset-0 z-50">
        <div x-show="actions" x-transition.opacity x-on:click="actions = false" class="absolute inset-0 bg-black/40"></div>
        <div x-show="actions"
            x-transition:enter="transition ease-out duration-200" x-transition:enter-start="translate-y-full" x-transition:enter-end="translate-y-0"
            x-transition:leave="transition ease-in duration-150" x-transition:leave-start="translate-y-0" x-transition:leave-end="translate-y-full"
            class="absolute inset-x-0 bottom-0 rounded-t-2xl bg-white pb-[env(safe-area-inset-bottom)] dark:bg-gray-900">
            <div class="flex justify-center py-2.5"><span class="h-1.5 w-10 rounded-full bg-slate-200 dark:bg-white/10"></span></div>
            <nav class="pb-3">
                <button type="button" x-on:click="actions=false; $dispatch('open-x2-context')" class="flex w-full items-center gap-3 px-5 py-3 text-left hover:bg-slate-50 dark:hover:bg-white/5">
                    <svg class="h-5 w-5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h6M9 12h6"/></svg>
                    <span class="flex-1 font-medium text-slate-800 dark:text-slate-100">Chuyển dự án / tòa</span>
                    <svg class="h-4 w-4 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m9 6 6 6-6 6"/></svg>
                </button>
                <a href="#" class="flex items-center gap-3 px-5 py-3 hover:bg-slate-50 dark:hover:bg-white/5">
                    <svg class="h-5 w-5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M18 8a6 6 0 1 0-12 0v5l-2 2v1h16v-1l-2-2V8ZM9 21h6"/></svg>
                    <span class="flex-1 font-medium text-slate-800 dark:text-slate-100">Thông báo</span>
                    @if ($notifCount)<span class="rounded-full bg-x2-red px-2 py-0.5 text-xs font-semibold text-white">{{ $notifCount }}</span>@endif
                    <svg class="h-4 w-4 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m9 6 6 6-6 6"/></svg>
                </a>
                <a href="{{ url('/admin/my-work') }}" class="flex items-center gap-3 px-5 py-3 hover:bg-slate-50 dark:hover:bg-white/5">
                    <svg class="h-5 w-5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm-7 8a7 7 0 0 1 14 0m1-9 1.5 1.5L23 9"/></svg>
                    <span class="flex-1 font-medium text-slate-800 dark:text-slate-100">Việc cần duyệt</span>
                    @if ($approvalCount)<span class="rounded-full bg-x2-red px-2 py-0.5 text-xs font-semibold text-white">{{ $approvalCount }}</span>@endif
                    <svg class="h-4 w-4 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m9 6 6 6-6 6"/></svg>
                </a>
                <a href="#" class="flex items-center gap-3 px-5 py-3 hover:bg-slate-50 dark:hover:bg-white/5">
                    <svg class="h-5 w-5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14m-7-7h14"/></svg>
                    <span class="flex-1 font-medium text-slate-800 dark:text-slate-100">Tạo nhanh</span>
                    <svg class="h-4 w-4 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m9 6 6 6-6 6"/></svg>
                </a>
                <button type="button" x-on:click="openAi()" class="flex w-full items-center gap-3 px-5 py-3 text-left hover:bg-slate-50 dark:hover:bg-white/5">
                    <svg class="h-5 w-5 text-x2-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3l1.9 4.6L18.5 9l-4.6 1.9L12 15l-1.9-4.1L5.5 9l4.6-1.4L12 3Z"/></svg>
                    <span class="flex-1 font-medium text-slate-800 dark:text-slate-100">X2AI Copilot</span>
                    <span class="rounded bg-x2-primary/10 px-1.5 py-0.5 text-[10px] font-bold text-x2-primary">AI</span>
                    <svg class="h-4 w-4 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m9 6 6 6-6 6"/></svg>
                </button>
                <a href="#" class="flex items-center gap-3 px-5 py-3 hover:bg-slate-50 dark:hover:bg-white/5">
                    <svg class="h-5 w-5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm-7 8a7 7 0 0 1 14 0"/></svg>
                    <span class="flex-1 font-medium text-slate-800 dark:text-slate-100">Hồ sơ cá nhân</span>
                    <svg class="h-4 w-4 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m9 6 6 6-6 6"/></svg>
                </a>
                <form method="POST" action="{{ $logoutUrl }}" x-on:submit="return confirm('Đăng xuất khỏi X2-BMS?')">
                    @csrf
                    <button type="submit" class="flex w-full items-center gap-3 px-5 py-3 text-left hover:bg-red-50 dark:hover:bg-red-500/10">
                        <svg class="h-5 w-5 text-x2-red" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12H3m0 0 4-4m-4 4 4 4m5-11h5a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2h-5"/></svg>
                        <span class="flex-1 font-medium text-x2-red">Đăng xuất</span>
                    </button>
                </form>
            </nav>
        </div>
    </div>

    {{-- Context switching uses the shared <livewire:context-switcher> modal
         (opened via the 'open-x2-context' event from the context row / bottom sheet). --}}
</div>

@once
    <script>
        function x2MobileShell() {
            return {
                actions: false,
                openAi() { this.actions = false; window.dispatchEvent(new CustomEvent('x2ai-open')); document.querySelector('.x2-ai-fab-btn')?.click(); },
                closeTop() { if (this.actions) this.actions = false; },
                init() { this.$watch('actions', () => this.lock()); },
                lock() { document.body.style.overflow = this.actions ? 'hidden' : ''; },
            };
        }
    </script>
@endonce
@endauth
