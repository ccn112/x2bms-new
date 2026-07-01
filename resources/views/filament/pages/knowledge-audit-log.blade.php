<x-filament-panels::page>
    <x-x2.action-bar
        title="Nhật ký kế thừa & truy xuất AI"
        subtitle="Chia sẻ · clone · index AI · duyệt gắn căn · retrieval AI. Chi tiết retrieval kèm tài liệu dùng/bị chặn + token." />

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ($kpis as $kpi)
            <x-x2.kpi-card :label="$kpi['label']" :value="$kpi['value']" :accent="$kpi['accent']" />
        @endforeach
    </div>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-12">
        <div class="xl:col-span-8">
            <x-x2.section-card title="Sự kiện governance">
                <div class="rounded-xl">{{ $this->table }}</div>
            </x-x2.section-card>
        </div>

        <aside class="space-y-4 xl:col-span-4">
            <x-x2.section-card title="Truy xuất AI gần đây" subtitle="Nhấn để xem tài liệu dùng / bị chặn">
                <ul class="space-y-2">
                    @forelse ($retrievals as $r)
                        <li>
                            <button type="button" wire:click="mountAction('retrievalDetail', { id: {{ $r->id }} })"
                                    class="w-full rounded-xl border border-slate-100 p-3 text-left hover:bg-slate-50">
                                <p class="truncate text-sm font-medium text-slate-700">{{ \Illuminate\Support\Str::limit($r->question, 48) ?: 'Truy vấn #'.$r->id }}</p>
                                <p class="mt-0.5 text-xs text-slate-400">
                                    {{ $r->user?->name ?? 'Hệ thống' }} ·
                                    {{ count($r->retrieved_document_ids_json ?? []) }} lấy /
                                    <span class="{{ count($r->blocked_document_ids_json ?? []) ? 'text-red-500' : '' }}">{{ count($r->blocked_document_ids_json ?? []) }} chặn</span>
                                    · {{ number_format($r->token_input + $r->token_output) }} tok
                                </p>
                            </button>
                        </li>
                    @empty
                        <li class="text-sm text-slate-400">Chưa có lượt truy xuất AI.</li>
                    @endforelse
                </ul>
            </x-x2.section-card>
        </aside>
    </div>

    {{-- Đăng ký modal cho mountAction --}}
    {{ $this->retrievalDetailAction }}
</x-filament-panels::page>
