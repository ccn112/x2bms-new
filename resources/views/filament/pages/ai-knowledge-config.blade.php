<x-filament-panels::page>
    <x-x2.action-bar
        title="Cấu hình AI đọc tri thức"
        subtitle="Prompt template · guardrail · phạm vi truy xuất KB. Guardrail: cảnh báo / chặn / cần người duyệt / ghi log." />

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ($kpis as $kpi)
            <x-x2.kpi-card :label="$kpi['label']" :value="$kpi['value']" :accent="$kpi['accent']" />
        @endforeach
    </div>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-12">
        <div class="xl:col-span-8">
            <x-x2.section-card title="Prompt template" subtitle="Mẫu prompt theo use case (hỗ trợ biến)">
                <div class="rounded-xl">
                    {{ $this->table }}
                </div>
            </x-x2.section-card>
        </div>

        <aside class="space-y-4 xl:col-span-4">
            <x-x2.section-card title="Guardrail chính sách">
                <ul class="space-y-2.5">
                    @forelse ($guardrails as $g)
                        <li class="rounded-xl border border-slate-100 p-3">
                            <div class="flex items-start justify-between gap-2">
                                <div>
                                    <p class="text-sm font-medium text-slate-800">{{ $g->name }}</p>
                                    <p class="text-xs text-slate-500">{{ $g->policy_type }} ·
                                        <span class="font-medium">{{ ($guardActionMap[$g->action] ?? [$g->action])[0] }}</span>
                                        · mức {{ $g->severity }}</p>
                                </div>
                                <button wire:click="toggleGuardrail({{ $g->id }})"
                                        class="shrink-0 rounded-lg px-2.5 py-1 text-xs font-medium {{ $g->is_active ? 'bg-green-50 text-green-700' : 'bg-slate-100 text-slate-500' }}">
                                    {{ $g->is_active ? 'Đang bật' : 'Đang tắt' }}
                                </button>
                            </div>
                        </li>
                    @empty
                        <li class="text-sm text-slate-400">Chưa có guardrail.</li>
                    @endforelse
                </ul>
            </x-x2.section-card>
        </aside>
    </div>
</x-filament-panels::page>
