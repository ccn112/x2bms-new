@php
    $icons = [
        'users' => 'M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm-7 8a7 7 0 0 1 14 0',
        'user' => 'M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm-7 8a7 7 0 0 1 14 0',
        'home' => 'M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h6M9 12h6',
        'chat' => 'M8 10h8M8 14h5m-9 6 3.5-2.1A2 2 0 0 1 11 18h6a3 3 0 0 0 3-3V8a3 3 0 0 0-3-3H7a3 3 0 0 0-3 3v12Z',
        'doc' => 'M8 3h6l4 4v14H6V5a2 2 0 0 1 2-2Zm6 0v4h4M9 13h6M9 17h4',
        'clipboard' => 'M9 5h6a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H9a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2Zm0 0V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v1',
    ];
@endphp

<div
    x-data
    x-on:open-x2-search.window="$wire.open = true"
    x-on:keydown.window.cmd.k.prevent="$wire.open = true"
    x-on:keydown.window.ctrl.k.prevent="$wire.open = true"
    x-on:keydown.escape.window="$wire.open && $wire.closeSearch()"
    x-effect="document.body.style.overflow = $wire.open ? 'hidden' : ''; if ($wire.open) $nextTick(() => $refs.input?.focus())"
>
    <div x-show="$wire.open" x-cloak x-transition.opacity class="fixed inset-0 z-[100] bg-black/40" x-on:click="$wire.closeSearch()"></div>

    {{-- Centering wrapper: offset by sidebar on lg+, width = 2/3 of content; mobile full-screen. --}}
    <div x-show="$wire.open" x-cloak x-transition
        class="fixed inset-0 z-[100] flex flex-col lg:items-center lg:justify-start lg:pt-16 lg:pl-64"
        x-on:click.self="$wire.closeSearch()"
    >
      <div class="flex w-full flex-1 flex-col bg-white lg:w-[calc((100vw-16rem)*0.667)] lg:flex-none lg:max-h-[80vh] lg:rounded-2xl lg:border lg:border-slate-200 lg:shadow-2xl dark:border-white/10 dark:bg-gray-900">
        {{-- Input row --}}
        <div class="flex items-center gap-2 border-b border-slate-100 px-3 py-2.5 dark:border-white/10">
            <div class="flex h-11 flex-1 items-center gap-2.5 rounded-xl border border-x2-primary bg-white px-3.5 dark:bg-gray-900">
                <svg class="h-5 w-5 shrink-0 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.3-4.3M11 19a8 8 0 1 1 0-16 8 8 0 0 1 0 16Z"/></svg>
                <input x-ref="input" wire:model.live.debounce.300ms="q" type="text" placeholder="Tìm cư dân, căn hộ, phản ánh, công việc…"
                    class="h-full flex-1 border-0 bg-transparent p-0 text-sm focus:ring-0" />
                <kbd class="hidden shrink-0 rounded-md border border-slate-200 bg-slate-50 px-1.5 py-0.5 text-[10px] font-medium text-slate-400 sm:inline dark:border-white/10 dark:bg-white/10">Ctrl + K</kbd>
            </div>
            <button type="button" wire:click="closeSearch" class="grid h-9 w-9 shrink-0 place-items-center rounded-lg text-slate-400 hover:bg-slate-100 dark:hover:bg-white/5">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 6l12 12M6 18 18 6"/></svg>
            </button>
        </div>

        <div class="flex-1 overflow-y-auto p-4">
            {{-- Recent searches + quick nav always visible; results appended when querying (WEB-UX-10). --}}
            <div>
                {{-- Recent searches --}}
                <div class="mb-5">
                    <div class="mb-2 flex items-center justify-between">
                        <h4 class="flex items-center gap-1.5 text-xs font-semibold uppercase tracking-wide text-slate-400">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 2m6-2a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                            Tìm kiếm gần đây
                        </h4>
                        @if (count($this->recent()))
                            <button type="button" wire:click="clearRecent" class="text-xs font-medium text-x2-primary hover:underline">Xóa lịch sử</button>
                        @endif
                    </div>
                    <div class="flex flex-wrap gap-2">
                        @forelse ($this->recent() as $term)
                            <button type="button" wire:click="useRecent(@js($term))" class="rounded-lg border border-slate-200 px-3 py-1.5 text-sm text-slate-600 hover:bg-slate-50 dark:border-white/10 dark:text-slate-300">{{ $term }}</button>
                        @empty
                            <p class="text-sm text-slate-400">Chưa có tìm kiếm gần đây.</p>
                        @endforelse
                    </div>
                </div>

                {{-- Quick navigation --}}
                <div>
                    <h4 class="mb-2 flex items-center gap-1.5 text-xs font-semibold uppercase tracking-wide text-slate-400">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M13 3 4 14h7l-1 7 9-11h-7l1-7Z"/></svg>
                        Điều hướng nhanh
                    </h4>
                    <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                        @foreach ($this->quickNav() as $nav)
                            <a href="{{ $nav['url'] }}" class="flex items-center gap-3 rounded-xl border border-slate-100 p-3 hover:border-x2-primary/40 hover:bg-slate-50 dark:border-white/10 dark:hover:bg-white/5">
                                <span class="grid h-9 w-9 shrink-0 place-items-center rounded-lg bg-x2-primary/10 text-x2-primary">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icons[$nav['icon']] ?? $icons['doc'] }}"/></svg>
                                </span>
                                <span class="min-w-0">
                                    <span class="block truncate text-sm font-medium text-slate-800 dark:text-slate-100">{{ $nav['label'] }}</span>
                                    <span class="block truncate text-xs text-slate-400">{{ $nav['sub'] }}</span>
                                </span>
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>

            @if (mb_strlen(trim($q)) >= 2)
                {{-- Grouped results --}}
                <div class="mt-5 mb-1 flex items-center gap-1.5 text-xs font-semibold uppercase tracking-wide text-slate-400">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                    Kết quả gợi ý
                </div>
                @forelse ($this->results as $group)
                    <div class="mb-5">
                        <div class="mb-2 flex items-center justify-between">
                            <h4 class="text-sm font-semibold text-slate-800 dark:text-slate-100">{{ $group['label'] }}</h4>
                            <a href="{{ $group['all'] }}" class="text-xs font-medium text-x2-primary hover:underline">Xem tất cả</a>
                        </div>
                        <div class="overflow-hidden rounded-xl border border-slate-100 dark:border-white/10">
                            @foreach ($group['items'] as $item)
                                <a href="{{ $item['url'] }}" class="flex items-center gap-3 border-b border-slate-50 px-3 py-2.5 last:border-0 hover:bg-slate-50 dark:border-white/5 dark:hover:bg-white/5">
                                    <span class="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-slate-100 text-slate-500 dark:bg-white/5">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icons[$group['icon']] ?? $icons['doc'] }}"/></svg>
                                    </span>
                                    <span class="min-w-0 flex-1">
                                        <span class="block truncate text-sm font-medium text-slate-800 dark:text-slate-100">{{ $item['title'] }}</span>
                                        <span class="block truncate text-xs text-slate-400">{{ $item['sub'] }}</span>
                                    </span>
                                    <span class="shrink-0 rounded-md bg-x2-primary/10 px-2 py-0.5 text-xs font-medium text-x2-primary">{{ $group['badge'] }}</span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @empty
                    <div class="py-16 text-center">
                        <svg class="mx-auto h-12 w-12 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.4"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.3-4.3M11 19a8 8 0 1 1 0-16 8 8 0 0 1 0 16Z"/></svg>
                        <p class="mt-3 text-sm font-medium text-slate-500">Không tìm thấy kết quả cho "{{ $q }}"</p>
                        <p class="text-xs text-slate-400">Thử từ khóa khác hoặc kiểm tra phạm vi dự án/tòa hiện tại.</p>
                    </div>
                @endforelse
            @endif
        </div>

        {{-- Footer hints (desktop) --}}
        <div class="hidden items-center gap-4 border-t border-slate-100 px-4 py-2.5 text-xs text-slate-400 sm:flex dark:border-white/10">
            <span><kbd class="rounded border border-slate-200 px-1">↑</kbd> <kbd class="rounded border border-slate-200 px-1">↓</kbd> điều hướng</span>
            <span><kbd class="rounded border border-slate-200 px-1">Enter</kbd> chọn</span>
            <span><kbd class="rounded border border-slate-200 px-1">Esc</kbd> đóng</span>
        </div>
      </div>
    </div>
</div>
