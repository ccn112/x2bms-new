@php
    $roleLabels = ['owner' => 'Chủ sở hữu', 'tenant' => 'Người thuê', 'member' => 'Thành viên'];
@endphp
<x-filament-panels::page>
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ($kpis as $kpi)
            <x-x2.kpi-card :label="$kpi['label']" :value="$kpi['value']" :accent="$kpi['accent']" />
        @endforeach
    </div>

    <x-x2.section-card title="Hồ sơ chờ duyệt" :subtitle="$requests->count().' hồ sơ'">
        <div class="space-y-3">
            @forelse ($requests as $r)
                <div class="rounded-xl border border-slate-200 p-4" wire:key="req-{{ $r->id }}">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        {{-- Applicant --}}
                        <div class="flex items-start gap-3">
                            <span class="grid h-10 w-10 place-items-center rounded-full bg-x2-navy text-sm font-semibold text-white">
                                {{ \Illuminate\Support\Str::of($r->full_name)->explode(' ')->map(fn ($w) => mb_substr($w, 0, 1))->take(-2)->implode('') }}
                            </span>
                            <div>
                                <div class="font-medium text-slate-800">{{ $r->full_name }}</div>
                                <div class="text-xs text-slate-500">{{ $r->phone }} · {{ $r->email }}</div>
                                <div class="mt-1 flex items-center gap-2 text-xs">
                                    <x-x2.status-badge :label="$roleLabels[$r->requested_role] ?? $r->requested_role" tone="blue" />
                                    <span class="text-slate-500">Căn đề nghị: <span class="font-medium text-slate-700">{{ $r->apartment?->code ?? '—' }}</span></span>
                                    <span class="text-slate-400">· {{ $r->document_count }} giấy tờ</span>
                                    <span class="text-slate-400">· {{ $r->submitted_at?->format('d/m/Y') }}</span>
                                </div>
                            </div>
                        </div>

                        {{-- Match score --}}
                        <div class="w-40">
                            <div class="mb-1 flex items-center justify-between text-xs">
                                <span class="text-slate-500">Độ khớp dữ liệu</span>
                                <span class="font-semibold {{ $r->match_score >= 80 ? 'text-x2-green' : ($r->match_score >= 60 ? 'text-x2-amber' : 'text-x2-red') }}">{{ $r->match_score }}%</span>
                            </div>
                            <div class="h-2 w-full overflow-hidden rounded-full bg-slate-100">
                                <div class="h-full rounded-full {{ $r->match_score >= 80 ? 'bg-x2-green' : ($r->match_score >= 60 ? 'bg-x2-amber' : 'bg-x2-red') }}" style="width: {{ $r->match_score }}%"></div>
                            </div>
                        </div>

                        {{-- Decisions --}}
                        <div class="flex items-center gap-2">
                            <a href="{{ url('/admin/residents/approvals/'.$r->id) }}" class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-sm font-medium text-x2-primary hover:bg-slate-50">Chi tiết</a>
                            <button type="button" wire:click="needMore({{ $r->id }})" class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-sm text-slate-600 hover:bg-slate-50">Bổ sung</button>
                            <button type="button" wire:click="reject({{ $r->id }})" class="rounded-lg border border-x2-red/30 bg-white px-3 py-1.5 text-sm font-medium text-x2-red hover:bg-x2-red/5">Từ chối</button>
                            <button type="button" wire:click="approve({{ $r->id }})" class="rounded-lg bg-x2-green px-3 py-1.5 text-sm font-medium text-white hover:opacity-90">Duyệt</button>
                        </div>
                    </div>
                </div>
            @empty
                <div class="py-10 text-center text-sm text-slate-400">Không còn hồ sơ chờ duyệt 🎉</div>
            @endforelse
        </div>
    </x-x2.section-card>
</x-filament-panels::page>
