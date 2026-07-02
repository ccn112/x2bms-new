@php
    $icons = [
        'doc' => 'M8 3h6l4 4v14H6V5a2 2 0 0 1 2-2Zm6 0v4h4M9 13h6M9 17h4',
        'statement' => 'M7 3h10a1 1 0 0 1 1 1v17l-3-2-2 2-2-2-2 2-2-2-3 2V4a1 1 0 0 1 1-1Zm2 5h6M9 12h6',
        'user' => 'M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm-7 8a7 7 0 0 1 14 0',
        'cash' => 'M3 7h18v10H3V7Zm9 2.5A2.5 2.5 0 1 1 12 14.5 2.5 2.5 0 0 1 12 9.5Z',
        'clipboard' => 'M9 5h6a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H9a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2Zm0 0V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v1',
        'chat' => 'M8 10h8M8 14h5m-9 6 3.5-2.1A2 2 0 0 1 11 18h6a3 3 0 0 0 3-3V8a3 3 0 0 0-3-3H7a3 3 0 0 0-3 3v12Z',
        'alert' => 'M12 9v4m0 4h.01M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z',
        'check' => 'M5 13l4 4L19 7',
    ];
    $prioBadge = ['urgent' => 'bg-red-50 text-red-600', 'high' => 'bg-amber-50 text-amber-600', 'normal' => 'bg-blue-50 text-blue-600'];
    $prioLabel = ['urgent' => 'Khẩn cấp', 'high' => 'Cao', 'normal' => 'Bình thường'];
    $typeLabel = ['approval' => 'Phê duyệt', 'statement' => 'Bảng kê', 'resident' => 'Cư dân', 'payment' => 'Thanh toán', 'workorder' => 'Công việc', 'feedback' => 'Phản ánh', 'sla' => 'SLA', 'system' => 'Hệ thống', 'audit' => 'Đã xử lý'];
    $activeFilters = collect([$priority !== 'all', $typeFilter !== 'all', $search !== ''])->filter()->count();
@endphp

<x-filament-panels::page>
    <x-x2.action-bar>
        <button type="button" class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-sm font-medium text-slate-600 hover:bg-slate-50">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v12m0 0-4-4m4 4 4-4M4 21h16"/></svg>
            Xuất báo cáo
        </button>
        <button type="button" wire:click="$refresh" class="inline-flex items-center gap-1.5 rounded-lg bg-x2-primary px-3 py-1.5 text-sm font-medium text-white hover:bg-x2-primary-600">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v6h6M20 20v-6h-6M20 9a8 8 0 0 0-14.9-2M4 15a8 8 0 0 0 14.9 2"/></svg>
            Làm mới
        </button>
    </x-x2.action-bar>

    {{-- Tabs --}}
    <div class="flex flex-wrap items-center gap-1 border-b border-slate-200 dark:border-white/10">
        @foreach (\App\Filament\Pages\MyWork::TABS as $key => $label)
            <button type="button" wire:click="setTab('{{ $key }}')"
                class="flex items-center gap-2 border-b-2 px-4 py-2.5 text-sm font-medium transition
                    {{ $tab === $key ? 'border-x2-primary text-x2-primary' : 'border-transparent text-slate-500 hover:text-slate-700' }}">
                {{ $label }}
                <span class="rounded-full px-2 py-0.5 text-xs font-semibold {{ $tab === $key ? 'bg-x2-primary/10 text-x2-primary' : 'bg-slate-100 text-slate-500' }}">{{ $tabCounts[$key] ?? 0 }}</span>
            </button>
        @endforeach
    </div>

    {{-- Priority summary cards (literal classes — no interpolated Tailwind) --}}
    @php
        $prioCards = [
            'urgent' => ['label' => 'Khẩn cấp', 'iconBg' => 'bg-red-50 text-red-500', 'ring' => 'border-red-300 ring-1 ring-red-200', 'alert' => true],
            'high' => ['label' => 'Ưu tiên cao', 'iconBg' => 'bg-amber-50 text-amber-500', 'ring' => 'border-amber-300 ring-1 ring-amber-200', 'alert' => true],
            'normal' => ['label' => 'Bình thường', 'iconBg' => 'bg-blue-50 text-blue-500', 'ring' => 'border-blue-300 ring-1 ring-blue-200', 'alert' => false],
        ];
    @endphp
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        @foreach ($prioCards as $pk => $pc)
            <button type="button" wire:click="$set('priority', '{{ $priority === $pk ? 'all' : $pk }}')"
                class="flex items-center gap-3 rounded-xl border bg-white p-4 text-left shadow-sm transition hover:shadow dark:bg-gray-900 dark:border-white/10 {{ $priority === $pk ? $pc['ring'] : 'border-slate-200' }}">
                <span class="grid h-11 w-11 place-items-center rounded-lg {{ $pc['iconBg'] }}">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $pc['alert'] ? 'M12 9v4m0 4h.01M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z' : 'M12 8v4l3 2m6-2a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z' }}"/></svg>
                </span>
                <div>
                    <div class="text-sm text-slate-500">{{ $pc['label'] }}</div>
                    <div class="text-2xl font-bold text-slate-900 dark:text-white">{{ $prioCounts[$pk] ?? 0 }} <span class="text-sm font-medium text-slate-400">việc</span></div>
                </div>
            </button>
        @endforeach
    </div>

    <div class="grid grid-cols-1 gap-5 xl:grid-cols-[240px_1fr]">
        {{-- Left filter panel --}}
        <div class="space-y-4 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-white/10 dark:bg-gray-900">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold uppercase tracking-wide text-slate-400">Bộ lọc @if ($activeFilters) <span class="text-x2-primary">({{ $activeFilters }})</span> @endif</span>
                <button type="button" wire:click="resetFilters" class="text-xs font-medium text-x2-primary hover:underline">Xóa lọc</button>
            </div>

            <div>
                <label class="mb-1 block text-xs font-medium text-slate-500">Dự án</label>
                <div class="truncate rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-600">{{ $projectName }}</div>
            </div>

            <div>
                <label class="mb-1 block text-xs font-medium text-slate-500">Loại việc</label>
                <select wire:model.live="typeFilter" class="w-full rounded-lg border-slate-200 text-sm focus:border-x2-primary focus:ring-x2-primary">
                    <option value="all">Tất cả</option>
                    @foreach ($typeOptions as $t)
                        <option value="{{ $t }}">{{ $typeLabel[$t] ?? $t }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="mb-1 block text-xs font-medium text-slate-500">Ưu tiên</label>
                <select wire:model.live="priority" class="w-full rounded-lg border-slate-200 text-sm focus:border-x2-primary focus:ring-x2-primary">
                    <option value="all">Tất cả</option>
                    <option value="urgent">Khẩn cấp</option>
                    <option value="high">Cao</option>
                    <option value="normal">Bình thường</option>
                </select>
            </div>

            <div>
                <label class="mb-1 block text-xs font-medium text-slate-500">Từ khóa</label>
                <input type="text" wire:model.live.debounce.400ms="search" placeholder="Nhập tiêu đề, mã…"
                    class="w-full rounded-lg border-slate-200 text-sm focus:border-x2-primary focus:ring-x2-primary" />
            </div>
        </div>

        {{-- Main table --}}
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-white/10 dark:bg-gray-900">
            <div class="flex items-center justify-between gap-3 border-b border-slate-100 px-4 py-3 dark:border-white/10">
                <span class="text-sm font-medium text-slate-500">{{ count($rows) }} mục</span>
                <div class="flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-1.5">
                    <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.3-4.3M11 19a8 8 0 1 1 0-16 8 8 0 0 1 0 16Z"/></svg>
                    <input type="text" wire:model.live.debounce.400ms="search" placeholder="Tìm trong danh sách…" class="w-48 border-0 p-0 text-sm focus:ring-0" />
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full min-w-[820px] text-sm">
                    <thead>
                        <tr class="border-b border-slate-100 text-left text-xs uppercase tracking-wide text-slate-400 dark:border-white/10">
                            <th class="px-4 py-3 font-medium">Loại</th>
                            <th class="px-4 py-3 font-medium">Tiêu đề</th>
                            <th class="px-4 py-3 font-medium">Tòa</th>
                            <th class="px-4 py-3 font-medium">Ưu tiên</th>
                            <th class="px-4 py-3 font-medium">Hạn xử lý</th>
                            <th class="px-4 py-3 font-medium">Trạng thái</th>
                            <th class="px-4 py-3 font-medium">Giao bởi</th>
                            <th class="px-4 py-3 text-right font-medium">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50 dark:divide-white/5">
                        @forelse ($rows as $r)
                            <tr class="hover:bg-slate-50/70 dark:hover:bg-white/5">
                                <td class="px-4 py-3">
                                    <span class="grid h-8 w-8 place-items-center rounded-lg {{ $prioBadge[$r['priority']] ?? 'bg-slate-100 text-slate-500' }}">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icons[$r['icon']] ?? $icons['doc'] }}"/></svg>
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="font-medium text-slate-800 dark:text-slate-100">{{ $r['title'] }}</div>
                                    <div class="text-xs text-slate-400">{{ $r['code'] }}</div>
                                </td>
                                <td class="px-4 py-3 text-slate-600 dark:text-slate-300">{{ $r['building'] }}</td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex rounded-md px-2 py-0.5 text-xs font-semibold {{ $prioBadge[$r['priority']] ?? '' }}">{{ $prioLabel[$r['priority']] ?? $r['priority'] }}</span>
                                </td>
                                <td class="px-4 py-3">
                                    @if ($r['due_at'])
                                        <span class="{{ $r['due_at']->isPast() ? 'font-medium text-red-600' : 'text-slate-600 dark:text-slate-300' }}">{{ $r['due_at']->format('d/m/Y H:i') }}</span>
                                    @else
                                        <span class="text-slate-300">—</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <x-x2.status-badge :label="$r['status'][0]" :tone="$r['status'][1]" />
                                </td>
                                <td class="px-4 py-3">
                                    <div class="text-slate-700 dark:text-slate-200">{{ $r['by'][0] }}</div>
                                    <div class="text-xs text-slate-400">{{ $r['by'][1] }}</div>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-end gap-1.5">
                                        @if (in_array('approve', $r['actions']))
                                            <button type="button" wire:click="decide('{{ $r['key'] }}', 'approve')" wire:confirm="Xác nhận phê duyệt mục này?"
                                                class="rounded-md bg-green-50 px-2.5 py-1 text-xs font-semibold text-green-600 hover:bg-green-100">Duyệt</button>
                                        @endif
                                        @if (in_array('reject', $r['actions']))
                                            <button type="button" wire:click="decide('{{ $r['key'] }}', 'reject')" wire:confirm="Từ chối mục này?"
                                                class="rounded-md bg-red-50 px-2.5 py-1 text-xs font-semibold text-red-600 hover:bg-red-100">Từ chối</button>
                                        @endif
                                        <button type="button" class="rounded-md border border-slate-200 px-2.5 py-1 text-xs font-medium text-slate-600 hover:bg-slate-50">Mở</button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-4 py-16 text-center">
                                    <svg class="mx-auto h-12 w-12 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.4"><path stroke-linecap="round" stroke-linejoin="round" d="M3 7h18v10H3zM3 12h6a3 3 0 0 0 6 0h6"/></svg>
                                    <p class="mt-3 text-sm font-medium text-slate-500">Không có mục nào</p>
                                    <p class="text-xs text-slate-400">Không có việc phù hợp bộ lọc hiện tại.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Tip banner --}}
    <div class="flex items-center gap-2.5 rounded-xl border border-blue-100 bg-blue-50 px-4 py-3 text-sm text-blue-700 dark:border-blue-500/20 dark:bg-blue-500/10 dark:text-blue-300">
        <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M12 3a9 9 0 1 0 0 18 9 9 0 0 0 0-18Z"/></svg>
        Mẹo: dùng bộ lọc bên trái để nhanh chóng tìm việc theo loại, ưu tiên hoặc từ khóa. Bấm vào thẻ ưu tiên để lọc nhanh.
    </div>
</x-filament-panels::page>
