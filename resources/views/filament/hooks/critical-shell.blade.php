{{-- Critical shell CSS/JS injected at HEAD_START — runs BEFORE the external viteTheme
     stylesheet and BEFORE Alpine, to eliminate the sidebar/offset flash on reload.
     The sidebar's `fi-sidebar-open` class is Alpine-bound (from localStorage.isOpenDesktop),
     so pre-hydration we reflect the persisted state ourselves; the class is removed once
     Alpine finishes and takes over. Desktop (≥1024px) only — mobile uses the drawer. --}}
<style>
    @media (min-width: 1024px) {
        /* Skeleton so the navy rail + content offset exist on first paint even before theme.css. */
        .fi-main-sidebar.fi-sidebar {
            position: fixed;
            inset-block-start: 0;
            inset-inline-start: 0;
            height: 100dvh;
            z-index: 40;
            background: #0b2146; /* --color-x2-navy */
        }
        /* Default (collapsed-preference or unknown) offset = collapsed rail. */
        .fi-body-has-navigation { padding-inline-start: var(--collapsed-sidebar-width, 5rem); }

        /* Persisted OPEN → expanded offset + width, pre-hydration. Removed after Alpine boots
           (see script), so the normal collapse toggle keeps working afterwards. */
        html.x2-sidebar-preopen .fi-body-has-navigation { padding-inline-start: var(--sidebar-width, 20rem) !important; }
        html.x2-sidebar-preopen .fi-main-sidebar.fi-sidebar { width: var(--sidebar-width, 20rem) !important; }
    }
    [x-cloak] { display: none !important; }
</style>
<script>
    (function () {
        try {
            var open = localStorage.getItem('isOpenDesktop');
            if (open === null || open === 'true') {
                document.documentElement.classList.add('x2-sidebar-preopen');
            }
        } catch (e) {}
        document.addEventListener('alpine:initialized', function () {
            document.documentElement.classList.remove('x2-sidebar-preopen');
        });
    })();
</script>
