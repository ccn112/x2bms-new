{{-- WEB-UX-00 · Header: LEFT = hamburger + page title. CENTER = global search
     (absolutely centred over the content area). Owner decision 2026-07-02. --}}
<div class="flex items-center gap-3" x-data>
    {{-- Collapse / expand sidebar --}}
    <button
        type="button"
        x-on:click="$store.sidebar.isOpen ? $store.sidebar.close() : $store.sidebar.open()"
        title="Thu gọn / mở rộng menu"
        aria-label="Thu gọn menu"
        class="grid h-10 w-10 shrink-0 place-items-center rounded-xl text-gray-500 transition hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-white/5"
    >
        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
    </button>

    {{-- Page title (relocated from content header) --}}
    <h1 id="x2-page-title" class="font-title hidden shrink-0 text-xl font-bold text-gray-900 sm:block dark:text-white"></h1>
</div>

{{-- Global search (Ctrl/Cmd+K) — CENTRED in the gap between the left group (title)
     and the right cluster (context/notif/avatar). `flex-1` grabs the middle space so
     the search never overlaps the heavy right cluster, unlike absolute centring. --}}
<div class="flex min-w-0 flex-1 justify-center px-4">
    <button
        type="button"
        x-data
        x-on:click="$dispatch('open-x2-search')"
        class="x2-global-search hidden h-10 w-[28rem] max-w-full items-center gap-2.5 rounded-xl border border-gray-200 bg-gray-50 px-3.5 text-sm text-gray-400 transition hover:bg-white hover:shadow-sm md:flex dark:border-white/10 dark:bg-white/5"
    >
        <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.3-4.3M11 19a8 8 0 1 1 0-16 8 8 0 0 1 0 16Z"/></svg>
        <span class="flex-1 truncate text-left">Tìm kiếm căn hộ, cư dân, công việc…</span>
        <kbd class="hidden shrink-0 rounded-md border border-gray-200 bg-white px-1.5 py-0.5 text-[10px] font-medium text-gray-400 sm:inline dark:border-white/10 dark:bg-white/10">Ctrl + K</kbd>
    </button>
</div>

@once
    <script>
        (function () {
            const sync = () => {
                const heading = document.querySelector('.fi-main .fi-header-heading');
                const target = document.getElementById('x2-page-title');
                if (target) {
                    target.textContent = heading
                        ? heading.textContent.trim()
                        : (document.title.split('·')[0].split('—')[0].trim());
                }
                // Hide the in-content page header now that the title sits in the topbar.
                const header = heading ? heading.closest('.fi-header') : null;
                if (header) header.style.display = 'none';
            };
            document.addEventListener('livewire:navigated', sync);
            document.addEventListener('DOMContentLoaded', sync);
            sync();
        })();
    </script>
@endonce
