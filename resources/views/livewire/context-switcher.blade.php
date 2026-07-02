@php
    $canCompany = $this->canSwitchCompany();
    $wsIcon = [
        'bql' => 'M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h6M9 12h6',
        'hq' => 'M3 21h18M5 21V7l7-4 7 4v14M9 9h.01M9 13h.01M9 17h.01M15 9h.01M15 13h.01M15 17h.01',
        'superadmin' => 'M12 3l7 3v5c0 4.5-3 8-7 10-4-2-7-5.5-7-10V6l7-3Z',
    ];
    $cols = $canCompany ? 3 : 2;
    $gridClass = $canCompany ? 'lg:grid-cols-3' : 'lg:grid-cols-2';
    $currentProject = $this->projects->firstWhere('id', $projectId);
@endphp

<div x-data x-on:open-x2-context.window="$wire.open = true" x-on:keydown.escape.window="$wire.open && $wire.closeContext()">
    <div x-show="$wire.open" x-cloak x-transition.opacity class="fixed inset-0 z-[100] bg-black/40" x-on:click="$wire.closeContext()"></div>

    {{-- Centering wrapper: on lg+ the area is offset by the sidebar (pl-64) so the popup
         sits inside the content region; width = 2/3 of content (viewport − sidebar). Mobile = full-screen. --}}
    <div x-show="$wire.open" x-cloak x-transition
        class="fixed inset-0 z-[100] flex flex-col lg:items-center lg:justify-start lg:py-8 lg:pl-64"
        x-on:click.self="$wire.closeContext()"
    >
      <div class="flex w-full flex-1 flex-col bg-white lg:w-[calc((100vw-16rem)*0.667)] lg:flex-none lg:max-h-[calc(100vh-4rem)] lg:rounded-2xl lg:border lg:border-slate-200 lg:shadow-2xl dark:bg-gray-900 dark:lg:border-white/10">
        {{-- Header --}}
        <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4 dark:border-white/10">
            <div class="flex items-center gap-2.5">
                <span class="grid h-9 w-9 place-items-center rounded-lg bg-x2-primary/10 text-x2-primary">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7h12m0 0-4-4m4 4-4 4M16 17H4m0 0 4 4m-4-4 4-4"/></svg>
                </span>
                <h2 class="text-lg font-bold text-slate-900 dark:text-white">Chuyển ngữ cảnh làm việc</h2>
            </div>
            <button type="button" wire:click="closeContext" class="grid h-9 w-9 place-items-center rounded-lg text-slate-400 hover:bg-slate-100 dark:hover:bg-white/5">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 6l12 12M6 18 18 6"/></svg>
            </button>
        </div>

        {{-- Columns --}}
        <div class="grid flex-1 grid-cols-1 gap-px overflow-y-auto bg-slate-100 dark:bg-white/10 {{ $gridClass }}">
            {{-- 1. Company (platform admin only) --}}
            @if ($canCompany)
                <div class="flex flex-col bg-white p-4 dark:bg-gray-900">
                    <div class="mb-3 text-sm font-semibold text-slate-800 dark:text-slate-100">1. Công ty</div>
                    <div class="mb-3 flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 dark:border-white/10">
                        <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.3-4.3M11 19a8 8 0 1 1 0-16 8 8 0 0 1 0 16Z"/></svg>
                        <input type="text" wire:model.live.debounce.300ms="companyQuery" placeholder="Tìm công ty…" class="w-full border-0 p-0 text-sm focus:ring-0" />
                    </div>
                    <div class="flex-1 space-y-1 overflow-y-auto">
                        @foreach ($this->companies as $c)
                            <button type="button" wire:click="selectCompany({{ $c->id }})"
                                class="flex w-full items-center gap-2.5 rounded-lg px-2.5 py-2 text-left text-sm hover:bg-slate-50 dark:hover:bg-white/5 {{ $tenantId === $c->id ? 'bg-x2-primary/5' : '' }}">
                                <svg class="h-4 w-4 shrink-0 {{ $tenantId === $c->id ? 'text-x2-primary' : 'text-slate-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3 21h18M5 21V7l7-4 7 4v14M9 9h.01M9 13h.01"/></svg>
                                <span class="flex-1 truncate {{ $tenantId === $c->id ? 'font-medium text-x2-primary' : 'text-slate-700 dark:text-slate-200' }}">{{ $c->name }}</span>
                                @if ($tenantId === $c->id)
                                    <svg class="h-4 w-4 shrink-0 text-x2-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.4"><path stroke-linecap="round" stroke-linejoin="round" d="m5 13 4 4L19 7"/></svg>
                                @endif
                            </button>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- 2. Project --}}
            <div class="flex flex-col bg-white p-4 dark:bg-gray-900">
                <div class="mb-3 text-sm font-semibold text-slate-800 dark:text-slate-100">{{ $canCompany ? '2.' : '1.' }} Dự án</div>
                <div class="mb-3 flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 dark:border-white/10">
                    <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.3-4.3M11 19a8 8 0 1 1 0-16 8 8 0 0 1 0 16Z"/></svg>
                    <input type="text" wire:model.live.debounce.300ms="projectQuery" placeholder="Tìm dự án…" class="w-full border-0 p-0 text-sm focus:ring-0" />
                </div>
                <div class="flex-1 space-y-1 overflow-y-auto">
                    @forelse ($this->projects as $p)
                        <button type="button" wire:click="selectProject({{ $p->id }})"
                            class="flex w-full items-center gap-2.5 rounded-lg px-2.5 py-2 text-left text-sm hover:bg-slate-50 dark:hover:bg-white/5 {{ $projectId === $p->id ? 'bg-x2-primary/5' : '' }}">
                            <svg class="h-4 w-4 shrink-0 {{ $projectId === $p->id ? 'text-x2-primary' : 'text-slate-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h6M9 12h6"/></svg>
                            <span class="flex-1 truncate {{ $projectId === $p->id ? 'font-medium text-x2-primary' : 'text-slate-700 dark:text-slate-200' }}">{{ $p->name }}</span>
                            @if ($projectId === $p->id)
                                <svg class="h-4 w-4 shrink-0 text-x2-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.4"><path stroke-linecap="round" stroke-linejoin="round" d="m5 13 4 4L19 7"/></svg>
                            @endif
                        </button>
                    @empty
                        <p class="px-2.5 py-4 text-sm text-slate-400">Không có dự án khả dụng.</p>
                    @endforelse
                </div>
            </div>

            {{-- 3. Workspace / Role --}}
            <div class="flex flex-col bg-white p-4 dark:bg-gray-900">
                <div class="mb-3 text-sm font-semibold text-slate-800 dark:text-slate-100">{{ $canCompany ? '3.' : '2.' }} Workspace / Vai trò</div>
                <div class="grid grid-cols-2 gap-2">
                    @foreach ($this->workspaces as $w)
                        <button type="button" wire:click="selectWorkspace('{{ $w['key'] }}')"
                            class="flex flex-col items-center gap-1.5 rounded-xl border p-3 text-center transition {{ $workspace === $w['key'] ? 'border-x2-primary bg-x2-primary/5' : 'border-slate-200 hover:bg-slate-50 dark:border-white/10 dark:hover:bg-white/5' }}">
                            <svg class="h-6 w-6 {{ $workspace === $w['key'] ? 'text-x2-primary' : 'text-slate-500' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $wsIcon[$w['key']] ?? $wsIcon['bql'] }}"/></svg>
                            <span class="text-xs font-medium {{ $workspace === $w['key'] ? 'text-x2-primary' : 'text-slate-700 dark:text-slate-200' }}">{{ $w['label'] }}</span>
                        </button>
                    @endforeach
                </div>

                <div class="mt-4 text-xs font-semibold uppercase tracking-wide text-slate-400">Vai trò</div>
                <div class="mt-2 flex-1 space-y-1 overflow-y-auto">
                    @foreach ($this->roles as $i => $role)
                        <div class="flex items-center gap-2.5 rounded-lg px-2.5 py-2 text-sm {{ $i === 0 ? 'bg-x2-primary/5' : '' }}">
                            <span class="flex-1 truncate {{ $i === 0 ? 'font-medium text-x2-primary' : 'text-slate-700 dark:text-slate-200' }}">{{ $role }}</span>
                            @if ($i === 0)
                                <svg class="h-4 w-4 shrink-0 text-x2-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.4"><path stroke-linecap="round" stroke-linejoin="round" d="m5 13 4 4L19 7"/></svg>
                            @endif
                        </div>
                    @endforeach
                </div>

                <div class="mt-3 flex items-start gap-2 rounded-lg bg-blue-50 p-2.5 text-xs text-blue-700 dark:bg-blue-500/10 dark:text-blue-300">
                    <svg class="mt-0.5 h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M12 3a9 9 0 1 0 0 18 9 9 0 0 0 0-18Z"/></svg>
                    Phạm vi dữ liệu: {{ $currentProject?->name ?? 'dự án đã chọn' }} theo vai trò {{ $this->roles[0] ?? '—' }}.
                </div>
            </div>
        </div>

        {{-- Footer --}}
        <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 px-5 py-3.5 dark:border-white/10">
            <label class="flex items-center gap-2 text-sm text-slate-600 dark:text-slate-300">
                <input type="checkbox" wire:model="remember" class="h-4 w-4 rounded border-slate-300 text-x2-primary focus:ring-x2-primary" />
                Ghi nhớ ngữ cảnh gần nhất
            </label>
            <div class="flex items-center gap-2">
                <button type="button" wire:click="closeContext" class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50 dark:border-white/10 dark:text-slate-200">Hủy</button>
                <button type="button" wire:click="openContext" class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50 dark:border-white/10 dark:text-slate-200">Đặt lại</button>
                <button type="button" wire:click="apply" class="rounded-xl bg-x2-primary px-5 py-2 text-sm font-semibold text-white hover:bg-x2-primary-600">Áp dụng</button>
            </div>
        </div>
      </div>
    </div>
</div>
