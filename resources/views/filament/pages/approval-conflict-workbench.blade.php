<x-filament-panels::page>
    <x-x2.kpi-row :cols="3">
        @foreach ($kpis as $kpi)
            <x-x2.kpi-card :label="$kpi['label']" :value="$kpi['value']" :accent="$kpi['accent']" />
        @endforeach
    </x-x2.kpi-row>

    <p class="text-sm text-slate-500 dark:text-slate-400">
        Xung đột phát hiện trực tiếp từ dữ liệu. Sửa ở màn tương ứng → xung đột tự biến mất. "Ghi nhận" chỉ lưu vết AuditLog.
    </p>

    <div class="space-y-4">
        @forelse ($conflicts as $c)
            <div class="rounded-2xl border border-red-200 bg-white shadow-sm dark:border-red-500/30 dark:bg-gray-900">
                <div class="flex items-center justify-between border-b border-slate-100 px-5 py-3 dark:border-white/10">
                    <div class="flex items-center gap-2.5">
                        <span class="inline-flex rounded-md bg-red-50 px-2 py-0.5 text-xs font-semibold text-red-600 dark:bg-red-500/10 dark:text-red-300">{{ $c['type_label'] }}</span>
                        <h3 class="text-base font-semibold text-slate-900 dark:text-white">{{ $c['title'] }}</h3>
                    </div>
                    <button type="button" wire:click="acknowledge('{{ $c['type'] }}', '{{ $c['key'] }}')"
                        class="rounded-lg border border-slate-200 px-2.5 py-1 text-xs font-medium text-slate-600 hover:bg-slate-50 dark:border-white/10 dark:text-slate-300">Ghi nhận xử lý</button>
                </div>
                <div class="grid gap-3 px-5 py-4 sm:grid-cols-2">
                    @foreach ($c['parties'] as $p)
                        <a href="{{ $p['url'] }}" class="flex items-center justify-between rounded-xl border border-slate-100 px-3 py-2 hover:bg-slate-50/70 dark:border-white/10 dark:hover:bg-white/5">
                            <div class="min-w-0">
                                <div class="truncate font-medium text-slate-800 dark:text-slate-100">{{ $p['name'] }}</div>
                                <div class="truncate text-xs text-slate-400">{{ $p['sub'] ?: '—' }}</div>
                            </div>
                            <svg class="h-4 w-4 shrink-0 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                        </a>
                    @endforeach
                </div>
                <div class="border-t border-slate-100 px-5 py-2.5 text-xs text-slate-500 dark:border-white/10 dark:text-slate-400">💡 {{ $c['action_hint'] }}</div>
            </div>
        @empty
            <div class="rounded-2xl border border-dashed border-slate-200 bg-white py-16 text-center dark:border-white/10 dark:bg-gray-900">
                <p class="text-sm font-medium text-x2-green">Không có xung đột nào 🎉</p>
                <p class="text-xs text-slate-400">Không phát hiện trùng danh tính hay căn tranh chấp trong phạm vi.</p>
            </div>
        @endforelse
    </div>
</x-filament-panels::page>
