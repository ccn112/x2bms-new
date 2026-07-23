@php
    $statusMeta = [
        'pending' => ['Chờ duyệt', 'amber'], 'approved' => ['Đã duyệt', 'blue'],
        'applied' => ['Đã áp dụng', 'green'], 'rejected' => ['Từ chối', 'red'],
    ];
@endphp

<x-filament-panels::page>
    <x-x2.kpi-row :cols="4">
        @foreach ($kpis as $kpi)
            <x-x2.kpi-card :label="$kpi['label']" :value="$kpi['value']" :accent="$kpi['accent']" />
        @endforeach
    </x-x2.kpi-row>

    <div class="flex items-center gap-3">
        <select wire:model.live="statusFilter" class="rounded-lg border-slate-200 text-sm focus:border-x2-primary focus:ring-x2-primary dark:bg-gray-900 dark:border-white/10">
            <option value="pending">Chờ duyệt</option>
            <option value="approved">Đã duyệt</option>
            <option value="applied">Đã áp dụng</option>
            <option value="rejected">Từ chối</option>
            <option value="all">Tất cả</option>
        </select>
        <span class="text-sm text-slate-400">{{ number_format($page->total()) }} yêu cầu</span>
    </div>

    <div class="space-y-3">
        @forelse ($rows as $r)
            @php [$sl, $st] = $statusMeta[$r['status']] ?? [$r['status'], 'slate']; @endphp
            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-white/10 dark:bg-gray-900">
                <div class="flex flex-wrap items-start justify-between gap-3 border-b border-slate-100 pb-3 dark:border-white/10">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="font-semibold text-slate-900 dark:text-white">{{ $r['resident'] }}</span>
                            <x-x2.status-badge :label="$sl" :tone="$st" />
                        </div>
                        @if ($r['reason'])<div class="mt-0.5 text-xs text-slate-400">Lý do: {{ $r['reason'] }}</div>@endif
                    </div>
                    <div class="flex items-center gap-2">
                        @if ($r['status'] === 'pending')
                            <button type="button" wire:click="approve({{ $r['id'] }})" class="rounded-lg border border-blue-200 bg-blue-50 px-2.5 py-1 text-xs font-medium text-blue-600 hover:bg-blue-100">Duyệt</button>
                            <button type="button" wire:click="reject({{ $r['id'] }})" wire:confirm="Từ chối yêu cầu này?" class="rounded-lg border border-red-200 bg-red-50 px-2.5 py-1 text-xs font-medium text-red-600 hover:bg-red-100">Từ chối</button>
                        @elseif ($r['status'] === 'approved')
                            <button type="button" wire:click="apply({{ $r['id'] }})" wire:confirm="Áp dụng thay đổi vào hồ sơ cư dân?" class="rounded-lg border border-green-200 bg-green-50 px-2.5 py-1 text-xs font-medium text-green-600 hover:bg-green-100">Áp dụng</button>
                            <button type="button" wire:click="reject({{ $r['id'] }})" class="rounded-lg border border-slate-200 px-2.5 py-1 text-xs font-medium text-slate-500 hover:bg-slate-50">Hủy duyệt</button>
                        @elseif ($r['status'] === 'applied')
                            <span class="text-xs text-slate-400">Áp dụng {{ $r['applied_at']?->format('d/m/Y H:i') }}</span>
                        @endif
                    </div>
                </div>

                {{-- before / after --}}
                <div class="mt-3 space-y-1.5">
                    @forelse ($r['diffs'] as $d)
                        <div class="flex flex-wrap items-center gap-2 text-sm">
                            <span class="w-32 shrink-0 text-slate-400">{{ $d['label'] }}</span>
                            <span class="rounded bg-slate-100 px-2 py-0.5 text-slate-500 line-through dark:bg-white/5">{{ $d['old'] ?: '—' }}</span>
                            <svg class="h-4 w-4 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                            <span class="rounded bg-green-50 px-2 py-0.5 font-medium text-green-700 dark:bg-green-500/10 dark:text-green-300">{{ $d['new'] ?: '—' }}</span>
                        </div>
                    @empty
                        <p class="text-sm text-slate-400">Không có trường thay đổi hợp lệ.</p>
                    @endforelse
                </div>
            </div>
        @empty
            <div class="rounded-2xl border border-dashed border-slate-200 bg-white py-16 text-center dark:border-white/10 dark:bg-gray-900">
                <p class="text-sm font-medium text-slate-500">Không có yêu cầu đổi thông tin</p>
            </div>
        @endforelse
    </div>

    @if ($page->hasPages())
        <div>{{ $page->links() }}</div>
    @endif
</x-filament-panels::page>
