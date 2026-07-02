<x-filament-panels::page>
    <div class="grid grid-cols-1 gap-6 xl:grid-cols-[1fr_340px]">
        {{-- Main state card --}}
        <div class="flex flex-col items-center rounded-2xl border border-slate-200 bg-white px-6 py-12 text-center shadow-sm dark:border-white/10 dark:bg-gray-900">
            {{-- Shield illustration --}}
            <div class="relative mb-8 grid h-40 w-full max-w-md place-items-center">
                <div class="absolute inset-0 rounded-3xl bg-gradient-to-b from-blue-50 to-transparent dark:from-white/5"></div>
                <svg class="relative h-28 w-28 text-x2-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.4">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 3l7 3v5c0 4.5-3 8-7 10-4-2-7-5.5-7-10V6l7-3Z"/>
                    <circle cx="12" cy="11" r="2.2" fill="currentColor" stroke="none"/>
                    <path stroke-linecap="round" d="M12 13v2.5"/>
                </svg>
            </div>

            <h2 class="text-2xl font-bold text-slate-900 dark:text-white">Không thể truy cập dữ liệu</h2>
            <p class="mt-2 max-w-lg text-sm text-slate-500">
                @if ($building && $project)
                    Bạn không có quyền truy cập vào <span class="font-semibold text-slate-700 dark:text-slate-200">{{ $building->name }} — {{ $project->name }}</span>.
                @else
                    Ngữ cảnh làm việc hiện tại không xác định hoặc bạn chưa được gán dự án.
                @endif
                <br>Ngữ cảnh hiện tại đã bị thu hồi hoặc không còn hiệu lực.
            </p>

            {{-- Reason chips --}}
            <div class="mt-6 flex flex-wrap items-center justify-center gap-3">
                <span class="inline-flex items-center gap-1.5 rounded-lg border border-red-200 bg-red-50 px-3 py-1.5 text-sm font-medium text-red-600">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M8 11V7a4 4 0 1 1 8 0v4m-9 0h10a1 1 0 0 1 1 1v7a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1v-7a1 1 0 0 1 1-1Z"/></svg>
                    Thiếu quyền
                </span>
                <span class="inline-flex items-center gap-1.5 rounded-lg border border-amber-200 bg-amber-50 px-3 py-1.5 text-sm font-medium text-amber-600">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z"/></svg>
                    Context không hợp lệ
                </span>
                <span class="inline-flex items-center gap-1.5 rounded-lg border border-blue-200 bg-blue-50 px-3 py-1.5 text-sm font-medium text-blue-600">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h6M9 12h6"/></svg>
                    Yêu cầu chọn lại tòa
                </span>
            </div>

            {{-- Primary actions --}}
            <div class="mt-8 flex w-full max-w-lg flex-col items-center gap-3 border-t border-slate-100 pt-8 dark:border-white/10 sm:flex-row sm:justify-center">
                <a href="{{ url('/admin/dashboard') }}#context"
                   class="inline-flex items-center gap-2 rounded-xl bg-x2-primary px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-x2-primary-600">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M5.25 3v18m13.5-18v18M9 6.75h6M9 12h6"/></svg>
                    Chọn lại project / tòa
                </a>
                <a href="{{ url('/admin/dashboard') }}"
                   class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-5 py-2.5 text-sm font-semibold text-slate-600 transition hover:bg-slate-50 dark:border-white/10 dark:bg-transparent dark:text-slate-200">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3 9.5 12 3l9 6.5M5 10v9a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-9"/></svg>
                    Quay về Dashboard
                </a>
            </div>
            <a href="mailto:support@x2bms.vn" class="mt-3 inline-flex items-center gap-2 rounded-xl px-4 py-2 text-sm font-medium text-x2-primary hover:underline">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M18 8.5a6 6 0 1 0-12 0v3.5a3 3 0 0 0 3 3M15 19h1a3 3 0 0 0 3-3v-1"/></svg>
                Liên hệ quản trị
            </a>
        </div>

        {{-- Right: user context panel --}}
        <div class="space-y-4">
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-gray-900">
                <h3 class="text-base font-semibold text-slate-900 dark:text-white">Thông tin của bạn</h3>
                <div class="mt-4 flex items-center gap-3">
                    <span class="grid h-12 w-12 place-items-center rounded-full bg-x2-navy text-lg font-bold text-white">
                        {{ \Illuminate\Support\Str::of($user?->name ?? 'U')->explode(' ')->map(fn ($w) => mb_substr($w, 0, 1))->take(2)->implode('') }}
                    </span>
                    <div class="min-w-0">
                        <div class="truncate font-semibold text-slate-800 dark:text-slate-100">{{ $user?->name }}</div>
                        <div class="truncate text-xs text-slate-500">{{ $user?->email }}</div>
                    </div>
                </div>

                <div class="mt-5 space-y-4 border-t border-slate-100 pt-4 dark:border-white/10">
                    <div class="flex items-start gap-3">
                        <svg class="mt-0.5 h-5 w-5 shrink-0 text-x2-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3l7 3v5c0 4.5-3 8-7 10-4-2-7-5.5-7-10V6l7-3Z"/></svg>
                        <div>
                            <div class="text-xs font-medium uppercase tracking-wide text-slate-400">Vai trò hiện tại</div>
                            <div class="font-semibold text-slate-800 dark:text-slate-100">{{ $roleLabel }}</div>
                        </div>
                    </div>
                    <div class="flex items-start gap-3">
                        <svg class="mt-0.5 h-5 w-5 shrink-0 text-x2-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18"/></svg>
                        <div>
                            <div class="text-xs font-medium uppercase tracking-wide text-slate-400">Ngữ cảnh gần nhất</div>
                            <div class="font-semibold text-slate-800 dark:text-slate-100">{{ $project?->name ?? 'Chưa chọn dự án' }}</div>
                            <div class="text-sm text-slate-500">{{ $building?->name ?? 'Chưa chọn tòa' }}</div>
                            <div class="mt-1 text-xs text-slate-400">{{ now()->format('d/m/Y H:i') }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex items-start gap-2.5 rounded-2xl border border-blue-100 bg-blue-50 p-4 text-sm text-blue-700 dark:border-blue-500/20 dark:bg-blue-500/10 dark:text-blue-300">
                <svg class="mt-0.5 h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M12 3a9 9 0 1 0 0 18 9 9 0 0 0 0-18Z"/></svg>
                Nếu bạn cho rằng đây là nhầm lẫn, vui lòng liên hệ quản trị hệ thống.
            </div>
        </div>
    </div>
</x-filament-panels::page>
