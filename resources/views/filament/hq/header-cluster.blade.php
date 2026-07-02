@php
    use App\Models\Tenant;

    $user = auth()->user();
    $ctx = app(\App\Support\Context\CurrentContext::class);
    $tenant = $ctx->tenant();
    $projects = $ctx->availableProjects();
    $selectedIds = $ctx->hqProjectIds();
    $allSelected = $ctx->hqAllProjectsSelected();
    $selectedCount = count($selectedIds);

    // Platform admin may switch which company (tenant) they operate as.
    $tenantOptions = $user?->isPlatformAdmin()
        ? Tenant::orderBy('name')->get(['id', 'name', 'short_name'])
        : collect();

    $scopeLabel = $allSelected
        ? 'Tất cả dự án'
        : ($selectedCount === 1
            ? ($projects->firstWhere('id', $selectedIds[0])?->name ?? '1 dự án')
            : $selectedCount.' dự án');
@endphp

<div class="flex items-center gap-2">
    {{-- Company (tenant) context selector --}}
    <div x-data="{ open: false }" class="relative">
        <button type="button" @click="open = !open" @click.outside="open = false"
                class="flex items-center gap-2.5 rounded-xl border border-gray-200 bg-white px-3 py-1.5 text-left hover:border-gray-300">
            <span class="grid h-8 w-8 shrink-0 place-items-center rounded-lg bg-blue-50 text-blue-600">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21"/></svg>
            </span>
            <span class="leading-tight">
                <span class="block text-[10px] font-medium uppercase tracking-wide text-gray-400">Công ty quản lý</span>
                <span class="block max-w-[180px] truncate text-sm font-semibold text-gray-900">{{ $tenant?->name ?? 'Chưa chọn công ty' }}</span>
            </span>
            <svg class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
        </button>

        @if ($tenantOptions->isNotEmpty())
            <div x-show="open" x-cloak x-transition
                 class="absolute right-0 z-50 mt-2 w-72 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-lg">
                <div class="border-b border-gray-100 px-3 py-2 text-[11px] font-semibold uppercase tracking-wide text-gray-400">Chọn công ty (SuperAdmin)</div>
                <div class="max-h-80 overflow-y-auto py-1">
                    @foreach ($tenantOptions as $t)
                        <a href="{{ route('context.hq_tenant', $t) }}"
                           class="flex items-center justify-between px-3 py-2 text-sm hover:bg-gray-50 {{ $tenant?->id === $t->id ? 'bg-blue-50/60' : '' }}">
                            <span class="truncate text-gray-800">{{ $t->name }}</span>
                            @if ($tenant?->id === $t->id)
                                <svg class="h-4 w-4 text-blue-600" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                            @endif
                        </a>
                    @endforeach
                </div>
            </div>
        @endif
    </div>

    {{-- Multi-project aggregation scope --}}
    <div x-data="{ open: false }" class="relative">
        <button type="button" @click="open = !open" @click.outside="open = false"
                class="flex items-center gap-2 rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-700 hover:border-gray-300">
            <svg class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25A2.25 2.25 0 0 1 13.5 8.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25A2.25 2.25 0 0 1 13.5 18v-2.25Z"/></svg>
            <span class="max-w-[140px] truncate">{{ $scopeLabel }}</span>
            <svg class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
        </button>

        <div x-show="open" x-cloak x-transition
             class="absolute right-0 z-50 mt-2 w-72 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-lg">
            <form method="POST" action="{{ route('context.hq_projects') }}">
                @csrf
                <div class="flex items-center justify-between border-b border-gray-100 px-3 py-2">
                    <span class="text-[11px] font-semibold uppercase tracking-wide text-gray-400">Phạm vi dự án</span>
                    <button type="submit" name="project_ids" value="" class="text-xs font-medium text-blue-600 hover:underline">Tất cả</button>
                </div>
                <div class="max-h-72 overflow-y-auto px-1 py-1">
                    @forelse ($projects as $p)
                        <label class="flex cursor-pointer items-center gap-2.5 rounded-lg px-2 py-1.5 text-sm hover:bg-gray-50">
                            <input type="checkbox" name="project_ids[]" value="{{ $p->id }}"
                                   @checked(! $allSelected && in_array($p->id, $selectedIds, true))
                                   class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            <span class="truncate text-gray-800">{{ $p->name }}</span>
                        </label>
                    @empty
                        <p class="px-3 py-4 text-center text-sm text-gray-400">Chưa có dự án nào.</p>
                    @endforelse
                </div>
                <div class="border-t border-gray-100 px-3 py-2">
                    <button type="submit" class="w-full rounded-lg bg-blue-600 px-3 py-1.5 text-sm font-semibold text-white hover:bg-blue-700">Áp dụng phạm vi</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Notifications --}}
    <a href="#" class="relative grid h-10 w-10 place-items-center rounded-xl text-gray-500 hover:bg-gray-100">
        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0"/></svg>
        <span class="absolute right-1.5 top-1.5 grid h-4 min-w-4 place-items-center rounded-full bg-red-500 px-1 text-[10px] font-bold text-white">12</span>
    </a>

    {{-- Help --}}
    <a href="#" class="grid h-10 w-10 place-items-center rounded-xl text-gray-500 hover:bg-gray-100">
        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75M12 17.25h.007v.008H12v-.008ZM21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
    </a>
</div>
