@php
    $user = auth()->user();
    $initials = \Illuminate\Support\Str::of($user?->name ?? '')
        ->explode(' ')->map(fn ($w) => mb_substr($w, 0, 1))->take(-2)->implode('');
@endphp

{{-- WEB-UX-00 · Signed-in user card, pinned to the sidebar footer. --}}
@if ($user)
    <div class="x2-user flex items-center gap-3 px-3 py-3">
        <span class="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-x2-primary text-xs font-semibold text-white">
            {{ $initials }}
        </span>
        <div class="x2-user-text min-w-0 flex-1 leading-tight">
            <div class="truncate text-sm font-medium text-white">{{ $user->name }}</div>
            @if ($user->title)
                <div class="truncate text-[11px] text-slate-400">{{ $user->title }}</div>
            @endif
        </div>
    </div>
@endif
