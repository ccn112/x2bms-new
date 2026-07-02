<x-filament-panels::page>
    <a href="{{ url('/admin/security') }}" class="mb-1 inline-flex items-center gap-1.5 text-sm font-medium text-x2-primary hover:underline">
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
        Bảo mật & 2FA
    </a>

    {{-- Current session --}}
    @if ($current)
        <div class="flex flex-wrap items-center gap-4 rounded-2xl border border-green-200 bg-green-50 p-5 dark:border-green-500/20 dark:bg-green-500/10">
            <span class="grid h-11 w-11 place-items-center rounded-xl bg-x2-green/15 text-x2-green">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
            </span>
            <div class="flex-1">
                <div class="font-semibold text-slate-900 dark:text-white">Phiên hiện tại · {{ $current['device'] }} · {{ $current['browser'] }}</div>
                <div class="text-sm text-slate-500">IP {{ $current['ip'] }} · Hoạt động {{ $current['last'] }}</div>
            </div>
            @if ($otherCount)
                <button type="button" wire:click="revokeOthers" wire:confirm="Đăng xuất khỏi tất cả thiết bị khác?"
                    class="rounded-xl border border-x2-red/30 bg-white px-4 py-2 text-sm font-medium text-x2-red hover:bg-x2-red/5">Đăng xuất thiết bị khác ({{ $otherCount }})</button>
            @endif
        </div>
    @endif

    {{-- Sessions table --}}
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-white/10 dark:bg-gray-900">
        <div class="border-b border-slate-100 px-5 py-3 dark:border-white/10">
            <h3 class="text-base font-semibold text-slate-900 dark:text-white">Thiết bị & phiên gần đây</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[640px] text-sm">
                <thead><tr class="border-b border-slate-100 text-left text-xs uppercase tracking-wide text-slate-400 dark:border-white/10">
                    <th class="px-5 py-2.5 font-medium">Thiết bị</th>
                    <th class="px-5 py-2.5 font-medium">Trình duyệt</th>
                    <th class="px-5 py-2.5 font-medium">IP</th>
                    <th class="px-5 py-2.5 font-medium">Hoạt động cuối</th>
                    <th class="px-5 py-2.5 font-medium">Trạng thái</th>
                    <th class="px-5 py-2.5 text-right font-medium">Thao tác</th>
                </tr></thead>
                <tbody class="divide-y divide-slate-50 dark:divide-white/5">
                    @forelse ($sessions as $s)
                        <tr class="hover:bg-slate-50/70 dark:hover:bg-white/5">
                            <td class="px-5 py-3 font-medium text-slate-800 dark:text-slate-100">{{ $s['device'] }}</td>
                            <td class="px-5 py-3 text-slate-600 dark:text-slate-300">{{ $s['browser'] }}</td>
                            <td class="px-5 py-3 text-slate-500">{{ $s['ip'] }}</td>
                            <td class="px-5 py-3 text-slate-500">{{ $s['last'] }}</td>
                            <td class="px-5 py-3">
                                @if ($s['current'])
                                    <x-x2.status-badge label="Hiện tại" tone="green" />
                                @else
                                    <x-x2.status-badge label="Đã đăng nhập" tone="slate" />
                                @endif
                            </td>
                            <td class="px-5 py-3 text-right">
                                @if ($s['current'])
                                    <span class="text-xs text-slate-400">—</span>
                                @else
                                    <button type="button" wire:click="revoke('{{ $s['id'] }}')" wire:confirm="Thu hồi phiên này?"
                                        class="rounded-md border border-x2-red/30 px-2.5 py-1 text-xs font-medium text-x2-red hover:bg-x2-red/5">Thu hồi</button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-5 py-12 text-center text-sm text-slate-400">Chỉ có phiên hiện tại.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-filament-panels::page>
