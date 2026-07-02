<x-filament-panels::page>
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div><h1 class="font-title text-2xl font-bold text-slate-900">Nhóm quyền</h1>
            <p class="mt-1 text-sm text-slate-500">Gom nhóm quyền theo module để gán nhanh cho vai trò.</p></div>
        <button class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">+ Thêm nhóm quyền</button>
    </div>
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        @foreach ($rows as $r)
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-center gap-2">
                    <span class="grid h-9 w-9 place-items-center rounded-lg bg-blue-50 text-blue-600"><svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 7.125C2.25 6.504 2.754 6 3.375 6h6c.621 0 1.125.504 1.125 1.125v3.75c0 .621-.504 1.125-1.125 1.125h-6a1.125 1.125 0 0 1-1.125-1.125v-3.75ZM14.25 8.625c0-.621.504-1.125 1.125-1.125h5.25c.621 0 1.125.504 1.125 1.125v8.25c0 .621-.504 1.125-1.125 1.125h-5.25a1.125 1.125 0 0 1-1.125-1.125v-8.25ZM3.75 16.125c0-.621.504-1.125 1.125-1.125h5.25c.621 0 1.125.504 1.125 1.125v2.25c0 .621-.504 1.125-1.125 1.125h-5.25a1.125 1.125 0 0 1-1.125-1.125v-2.25Z"/></svg></span>
                    <span class="rounded bg-slate-100 px-2 py-0.5 text-[11px] font-medium text-slate-500">{{ $r['module'] }}</span>
                </div>
                <div class="mt-3 font-title text-sm font-bold text-slate-900">{{ $r['name'] }}</div>
                <p class="mt-1 text-xs text-slate-500">{{ $r['desc'] }}</p>
                <div class="mt-3 flex items-center justify-between border-t border-slate-100 pt-2 text-xs">
                    <span class="text-slate-500">{{ $r['perms'] }} quyền</span>
                    <span class="text-slate-500">{{ $r['roles'] }} vai trò</span>
                </div>
            </div>
        @endforeach
    </div>
</div>
</x-filament-panels::page>
