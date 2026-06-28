@props([
    'breadcrumb' => null,
    'buildingName' => null,
    'userName' => null,
    'userRole' => null,
    'notificationCount' => 0,
])

{{-- X2Topbar — breadcrumb, building switcher, alerts, user. Data via props. --}}
<header class="sticky top-0 z-20 flex h-14 items-center gap-4 border-b border-slate-200 bg-white px-5">
    @if ($breadcrumb)
        <div class="text-sm font-medium text-slate-600">{{ $breadcrumb }}</div>
    @endif

    @if ($buildingName)
        <button type="button" class="flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-1.5 text-sm text-slate-700 hover:bg-slate-50">
            <svg class="h-4 w-4 text-x2-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3 21h18M5 21V7l7-4 7 4v14M9 9h.01M15 9h.01M9 13h.01M15 13h.01"/></svg>
            <span class="font-medium">{{ $buildingName }}</span>
            <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6"/></svg>
        </button>
    @endif

    <div class="ml-auto flex items-center gap-3">
        <div class="relative">
            <svg class="h-6 w-6 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6"><path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.4-1.4A2 2 0 0 1 18 14.2V11a6 6 0 1 0-12 0v3.2c0 .5-.2 1-.6 1.4L4 17h5m6 0v1a3 3 0 1 1-6 0v-1m6 0H9"/></svg>
            @if ($notificationCount > 0)
                <span class="absolute -right-1 -top-1 grid h-4 min-w-4 place-items-center rounded-full bg-x2-red px-1 text-[10px] font-semibold text-white">{{ $notificationCount }}</span>
            @endif
        </div>

        @if ($userName)
            <div class="flex items-center gap-2 border-l border-slate-200 pl-3">
                <span class="grid h-8 w-8 place-items-center rounded-full bg-x2-navy text-xs font-semibold text-white">
                    {{ \Illuminate\Support\Str::of($userName)->explode(' ')->map(fn ($w) => mb_substr($w, 0, 1))->take(-2)->implode('') }}
                </span>
                <div class="leading-tight">
                    <div class="text-sm font-medium text-slate-800">{{ $userName }}</div>
                    @if ($userRole)
                        <div class="text-[11px] text-slate-500">{{ $userRole }}</div>
                    @endif
                </div>
            </div>
        @endif
    </div>
</header>
