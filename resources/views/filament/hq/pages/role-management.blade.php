<x-filament-panels::page>
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div><h1 class="font-title text-2xl font-bold text-slate-900">Quản lý vai trò</h1>
            <p class="mt-1 text-sm text-slate-500">Danh mục vai trò 3 tầng (Nền tảng → Công ty → Dự án) và số quyền/người dùng theo vai trò.</p></div>
        <button class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">+ Thêm vai trò</button>
    </div>
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @foreach ($rows as $r)
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <span class="grid h-10 w-10 place-items-center rounded-xl bg-violet-50 text-violet-600"><svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z"/></svg></span>
                    <span class="text-xs text-slate-400">{{ $r['key'] }}</span>
                </div>
                <div class="mt-3 font-title text-base font-bold text-slate-900">{{ $r['name'] }}</div>
                <div class="mt-3 flex items-center justify-between text-sm">
                    <span class="text-slate-500">{{ $r['perms'] }} quyền</span>
                    <span class="rounded-md bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-600">{{ $r['users'] }} người dùng</span>
                </div>
            </div>
        @endforeach
    </div>
</div>
</x-filament-panels::page>
