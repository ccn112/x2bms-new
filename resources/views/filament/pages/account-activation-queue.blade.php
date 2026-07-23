@php
    $toneClass = [
        'red' => 'bg-red-50 text-red-600 dark:bg-red-500/10 dark:text-red-300',
        'amber' => 'bg-amber-50 text-amber-600 dark:bg-amber-500/10 dark:text-amber-300',
        'slate' => 'bg-slate-100 text-slate-500 dark:bg-white/10 dark:text-slate-300',
    ];
    $idStatus = [
        'verified' => ['Đã xác thực', 'green'], 'phone_verified' => ['Xác thực SĐT', 'blue'],
        'email_verified' => ['Xác thực email', 'blue'], 'unverified' => ['Chưa xác thực', 'amber'],
    ];
@endphp

<x-filament-panels::page>
    <x-x2.kpi-row :cols="4">
        @foreach ($kpis as $kpi)
            <x-x2.kpi-card :label="$kpi['label']" :value="$kpi['value']" :accent="$kpi['accent']" />
        @endforeach
    </x-x2.kpi-row>

    {{-- Filter bar --}}
    <div class="flex flex-wrap items-center gap-3">
        <div class="flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-1.5 dark:border-white/10 dark:bg-gray-900">
            <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.3-4.3M11 19a8 8 0 1 1 0-16 8 8 0 0 1 0 16Z"/></svg>
            <input type="text" wire:model.live.debounce.400ms="search" placeholder="Tìm tên / SĐT / email…" class="w-64 border-0 p-0 text-sm focus:ring-0 dark:bg-transparent" />
        </div>
        <select wire:model.live="statusFilter" class="rounded-lg border-slate-200 text-sm focus:border-x2-primary focus:ring-x2-primary dark:bg-gray-900 dark:border-white/10">
            <option value="all">Tất cả trạng thái</option>
            <option value="unverified">Chưa xác thực</option>
            <option value="verified">Đã xác thực</option>
            <option value="suspended">Đang khóa</option>
        </select>
        <span class="text-sm text-slate-400">{{ number_format($page->total()) }} tài khoản</span>
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-white/10 dark:bg-gray-900">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[820px] text-sm">
                <thead>
                    <tr class="border-b border-slate-100 text-left text-xs uppercase tracking-wide text-slate-400 dark:border-white/10">
                        <th class="px-5 py-2.5 font-medium">Tài khoản</th>
                        <th class="px-5 py-2.5 font-medium">Định danh</th>
                        <th class="px-5 py-2.5 font-medium text-center">Thiết bị</th>
                        <th class="px-5 py-2.5 font-medium">Cảnh báo</th>
                        <th class="px-5 py-2.5 text-right font-medium">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50 dark:divide-white/5">
                    @forelse ($rows as $r)
                        <tr class="hover:bg-slate-50/70 dark:hover:bg-white/5">
                            <td class="px-5 py-3">
                                <div class="font-medium text-slate-800 dark:text-slate-100">{{ $r['name'] }}</div>
                                <div class="text-xs text-slate-400">{{ $r['phone'] ?: '—' }}@if ($r['email']) · {{ $r['email'] }}@endif</div>
                            </td>
                            <td class="px-5 py-3">
                                @php [$lbl, $tone] = $idStatus[$r['identity_status']] ?? [$r['identity_status'] ?? '—', 'slate']; @endphp
                                <x-x2.status-badge :label="$lbl" :tone="$tone" />
                                @if ($r['account_status'] === 'suspended')
                                    <span class="ml-1 inline-flex rounded-md bg-red-50 px-2 py-0.5 text-xs font-medium text-red-600 dark:bg-red-500/10 dark:text-red-300">Đã khóa</span>
                                @endif
                            </td>
                            <td class="px-5 py-3 text-center">
                                <span class="inline-flex items-center gap-1 {{ $r['devices'] > 0 ? 'text-slate-700 dark:text-slate-200' : 'text-slate-300' }}">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7"><path stroke-linecap="round" stroke-linejoin="round" d="M9 17H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2h-2m-3 0v4m-2 0h4"/></svg>
                                    {{ $r['devices'] }}
                                </span>
                            </td>
                            <td class="px-5 py-3">
                                <div class="flex flex-wrap gap-1">
                                    @forelse ($r['risks'] as $risk)
                                        <span class="inline-flex rounded-md px-2 py-0.5 text-xs font-medium {{ $toneClass[$risk['tone']] ?? $toneClass['slate'] }}">{{ $risk['label'] }}</span>
                                    @empty
                                        <span class="text-xs text-x2-green">Sẵn sàng kích hoạt</span>
                                    @endforelse
                                </div>
                            </td>
                            <td class="px-5 py-3">
                                <div class="flex items-center justify-end gap-2">
                                    <button type="button" wire:click="invite({{ $r['id'] }})"
                                        class="rounded-lg border border-x2-primary/30 bg-x2-primary/5 px-2.5 py-1 text-xs font-medium text-x2-primary hover:bg-x2-primary/10">
                                        {{ $r['invited'] ? 'Gửi lại lời mời' : 'Mời kích hoạt' }}
                                    </button>
                                    @if ($r['account_status'] === 'suspended')
                                        <button type="button" wire:click="unlock({{ $r['id'] }})" wire:confirm="Mở khóa tài khoản này?"
                                            class="rounded-lg border border-green-200 bg-green-50 px-2.5 py-1 text-xs font-medium text-green-600 hover:bg-green-100">Mở khóa</button>
                                    @else
                                        <button type="button" wire:click="lock({{ $r['id'] }})" wire:confirm="Khóa tài khoản này?"
                                            class="rounded-lg border border-red-200 bg-red-50 px-2.5 py-1 text-xs font-medium text-red-600 hover:bg-red-100">Khóa</button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-5 py-14 text-center text-sm text-slate-400">Không có tài khoản nào trong phạm vi.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($page->hasPages())
            <div class="border-t border-slate-100 px-4 py-3 dark:border-white/10">{{ $page->links() }}</div>
        @endif
    </div>
</x-filament-panels::page>
