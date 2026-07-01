@php
    $sevMeta = ['critical' => 'bg-red-100 text-red-700', 'high' => 'bg-red-50 text-red-600', 'medium' => 'bg-amber-50 text-amber-600', 'low' => 'bg-slate-100 text-slate-500'];
@endphp

<x-filament-panels::page>
    <x-x2.action-bar
        title="Sức khỏe tích hợp & Hàng đợi retry"
        subtitle="SLA · latency · error rate · retry queue · dead-letter · sự cố · secret sắp hết hạn." />

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
        @foreach ($kpis as $kpi)
            <x-x2.kpi-card :label="$kpi['label']" :value="$kpi['value']" :accent="$kpi['accent']" />
        @endforeach
    </div>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
        <div class="xl:col-span-2">
            {{ $this->table }}
        </div>
        <x-x2.section-card title="Timeline sự cố">
            <ol class="relative space-y-4 border-l border-slate-100 pl-4 text-sm">
                @forelse ($incidents as $inc)
                    <li class="relative">
                        <span class="absolute -left-[21px] top-1 h-2.5 w-2.5 rounded-full {{ $inc->status === 'resolved' ? 'bg-emerald-400' : 'bg-red-400' }}"></span>
                        <div class="flex items-center justify-between">
                            <span class="font-medium text-slate-700">{{ $inc->title }}</span>
                            <span class="rounded-full px-2 py-0.5 text-[11px] font-medium {{ $sevMeta[$inc->severity] ?? 'bg-slate-100 text-slate-500' }}">{{ $inc->severity }}</span>
                        </div>
                        <div class="mt-0.5 text-[11px] text-slate-400">
                            {{ $inc->source }} · {{ $inc->started_at?->format('d/m H:i') }}
                            @if ($inc->resolved_at)
                                → {{ $inc->resolved_at->format('d/m H:i') }} (MTTR {{ $inc->started_at?->diffInMinutes($inc->resolved_at) }}′)
                            @else
                                · <span class="text-red-500">đang mở</span>
                            @endif
                        </div>
                    </li>
                @empty
                    <li class="text-slate-400">Không có sự cố.</li>
                @endforelse
            </ol>
        </x-x2.section-card>
    </div>
</x-filament-panels::page>
