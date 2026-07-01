@php
    $toneBar = ['amber' => 'bg-x2-amber', 'blue' => 'bg-x2-blue', 'green' => 'bg-x2-green', 'red' => 'bg-x2-red'];
    $prioTone = ['urgent' => 'bg-x2-red/10 text-x2-red', 'high' => 'bg-x2-amber/10 text-x2-amber', 'normal' => 'bg-slate-100 text-slate-500', 'low' => 'bg-slate-100 text-slate-400'];
@endphp

<x-filament-panels::page>
    <x-x2.action-bar
        title="Bảng công việc"
        subtitle="Kéo-thả thẻ để đổi trạng thái · checklist · nghiệm thu (phạm vi dự án của bạn)." />

    {{-- Đăng ký modal cho các action theo thẻ (mountAction). --}}
    <div class="hidden">
        {{ $this->detailWorkOrderAction }}{{ $this->assignWorkOrderAction }}{{ $this->checklistWorkOrderAction }}{{ $this->signoffWorkOrderAction }}
    </div>

    <div x-data="{ dragId: null }" class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
        @foreach ($columns as $col)
            <div class="flex flex-col rounded-2xl border border-slate-100 bg-slate-50/60"
                 x-on:dragover.prevent
                 x-on:drop="if (dragId) { $wire.moveCard(dragId, '{{ $col['status'] }}'); dragId = null }">
                <div class="flex items-center justify-between border-b border-slate-100 px-3 py-2.5">
                    <div class="flex items-center gap-2">
                        <span class="h-2.5 w-2.5 rounded-full {{ $toneBar[$col['tone']] ?? 'bg-slate-300' }}"></span>
                        <span class="text-sm font-semibold text-slate-700">{{ $col['label'] }}</span>
                    </div>
                    <span class="rounded-full bg-white px-2 py-0.5 text-xs font-semibold tabular-nums text-slate-500">{{ $col['count'] }}</span>
                </div>

                <div class="flex-1 space-y-2 p-2" style="min-height: 120px">
                    @forelse ($col['items'] as $wo)
                        @php $items = $wo->checklists->flatMap->items; $done = $items->where('is_done', true)->count(); $total = $items->count(); @endphp
                        <div draggable="true" x-on:dragstart="dragId = {{ $wo->id }}" x-on:dragend="dragId = null"
                             class="cursor-grab rounded-xl border border-slate-200 bg-white p-3 shadow-sm active:cursor-grabbing">
                            <div class="flex items-start justify-between gap-2">
                                <span class="text-xs font-semibold text-x2-navy">{{ $wo->code }}</span>
                                <span class="rounded px-1.5 py-0.5 text-[10px] font-semibold {{ $prioTone[$wo->priority] ?? 'bg-slate-100 text-slate-500' }}">{{ $wo->priority }}</span>
                            </div>
                            <div class="mt-1 line-clamp-2 text-sm text-slate-700">{{ $wo->title }}</div>
                            <div class="mt-2 flex items-center justify-between text-xs text-slate-400">
                                <span>{{ $wo->assignee?->name ?? 'Chưa giao' }}</span>
                                @if ($total > 0)
                                    <span class="tabular-nums {{ $done === $total ? 'text-x2-green' : '' }}">✓ {{ $done }}/{{ $total }}</span>
                                @endif
                            </div>
                            <div class="mt-2 flex flex-wrap gap-1 border-t border-slate-50 pt-2">
                                <button type="button" wire:click="mountAction('detailWorkOrder', { id: {{ $wo->id }} })" class="rounded px-1.5 py-0.5 text-[11px] font-medium text-x2-blue hover:bg-x2-blue/10">Chi tiết</button>
                                <button type="button" wire:click="mountAction('assignWorkOrder', { id: {{ $wo->id }} })" class="rounded px-1.5 py-0.5 text-[11px] font-medium text-slate-500 hover:bg-slate-100">Giao</button>
                                <button type="button" wire:click="mountAction('checklistWorkOrder', { id: {{ $wo->id }} })" class="rounded px-1.5 py-0.5 text-[11px] font-medium text-slate-500 hover:bg-slate-100">Checklist</button>
                                @if ($wo->status->value !== 'done')
                                    <button type="button" wire:click="mountAction('signoffWorkOrder', { id: {{ $wo->id }} })" class="rounded px-1.5 py-0.5 text-[11px] font-medium text-x2-green hover:bg-x2-green/10">Nghiệm thu</button>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="grid place-items-center py-6 text-xs text-slate-300">Kéo thẻ vào đây</div>
                    @endforelse
                </div>
            </div>
        @endforeach
    </div>
</x-filament-panels::page>
