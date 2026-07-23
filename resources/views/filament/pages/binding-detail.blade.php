@php
    $acc = $record->account;
    $ev = data_get($record->evidence_files_json, 'evidence', []);
    $toneClass = [
        'red' => 'border-red-200 bg-red-50 text-red-700',
        'amber' => 'border-amber-200 bg-amber-50 text-amber-700',
        'slate' => 'border-slate-200 bg-slate-50 text-slate-600',
    ];
@endphp

<div class="space-y-5 text-sm">
    {{-- Đánh giá rủi ro (Module 0) --}}
    @if (! empty($risk ?? []))
        <div class="rounded-xl border border-slate-100 p-4">
            <p class="mb-2 font-semibold text-slate-700">Đánh giá rủi ro ({{ count($risk) }})</p>
            <ul class="space-y-2">
                @foreach ($risk as $f)
                    <li class="rounded-lg border p-2.5 {{ $toneClass[$f['tone']] ?? $toneClass['slate'] }}">
                        <p class="font-medium">{{ $f['label'] }}</p>
                        @if (! empty($f['checklist']))
                            <ul class="mt-1 list-disc pl-5 text-xs opacity-80">
                                @foreach ($f['checklist'] as $item)<li>{{ $item }}</li>@endforeach
                            </ul>
                        @endif
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Cảnh báo trùng --}}
    @if ($duplicates->isNotEmpty())
        <div class="rounded-xl border border-amber-200 bg-amber-50 p-3 text-amber-800">
            <p class="font-semibold">⚠ Cảnh báo trùng lặp ({{ $duplicates->count() }} tài khoản)</p>
            <ul class="mt-1 list-disc pl-5">
                @foreach ($duplicates as $d)
                    <li>{{ $d->full_name }} — {{ $d->phone }} · {{ $d->email }}
                        <span class="text-amber-600">(risk {{ $d->risk_score }})</span></li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="grid grid-cols-2 gap-4">
        {{-- Hồ sơ tài khoản --}}
        <div class="rounded-xl border border-slate-100 p-4">
            <p class="mb-2 font-semibold text-slate-700">Hồ sơ tài khoản gốc</p>
            <dl class="space-y-1.5 text-slate-600">
                <div class="flex justify-between"><dt>Họ tên</dt><dd class="font-medium text-slate-800">{{ $acc?->full_name ?? '—' }}</dd></div>
                <div class="flex justify-between"><dt>SĐT</dt><dd>{{ $acc?->phone ?? '—' }}</dd></div>
                <div class="flex justify-between"><dt>Email</dt><dd>{{ $acc?->email ?? '—' }}</dd></div>
                <div class="flex justify-between"><dt>Định danh</dt><dd>{{ $acc?->identity_status ?? '—' }}</dd></div>
                <div class="flex justify-between"><dt>Loại TK</dt><dd>{{ $acc?->account_type ?? '—' }}</dd></div>
                <div class="flex justify-between"><dt>Risk score</dt><dd>{{ $acc?->risk_score ?? 0 }}</dd></div>
            </dl>
        </div>

        {{-- Căn hộ yêu cầu --}}
        <div class="rounded-xl border border-slate-100 p-4">
            <p class="mb-2 font-semibold text-slate-700">Căn hộ & yêu cầu</p>
            <dl class="space-y-1.5 text-slate-600">
                <div class="flex justify-between"><dt>Mã YC</dt><dd class="font-medium text-slate-800">{{ $record->code }}</dd></div>
                <div class="flex justify-between"><dt>Căn hộ</dt><dd>{{ $record->apartment?->code ?? '—' }}</dd></div>
                <div class="flex justify-between"><dt>Vai trò</dt><dd>{{ $roleMap[$record->requested_role] ?? $record->requested_role }}</dd></div>
                <div class="flex justify-between"><dt>Trạng thái</dt><dd>{{ ($statusMap[$record->status] ?? [$record->status])[0] }}</dd></div>
                <div class="flex justify-between"><dt>Gửi lúc</dt><dd>{{ $record->requested_at?->format('d/m/Y') ?? '—' }}</dd></div>
                <div class="flex justify-between"><dt>Người duyệt</dt><dd>{{ $record->reviewer?->name ?? '—' }}</dd></div>
            </dl>
        </div>
    </div>

    {{-- Minh chứng --}}
    <div class="rounded-xl border border-slate-100 p-4">
        <p class="mb-2 font-semibold text-slate-700">Minh chứng ({{ count($ev) }})</p>
        @if ($ev)
            <div class="flex flex-wrap gap-2">
                @foreach ($ev as $file)
                    <span class="inline-flex items-center gap-1 rounded-lg bg-slate-100 px-2.5 py-1 text-slate-600">
                        <x-heroicon-m-paper-clip class="h-4 w-4" />{{ $file }}
                    </span>
                @endforeach
            </div>
        @else
            <p class="text-slate-400">Chưa có tệp minh chứng.</p>
        @endif
    </div>

    {{-- Binding trước đó (1 tài khoản nhiều căn) --}}
    <div class="rounded-xl border border-slate-100 p-4">
        <p class="mb-2 font-semibold text-slate-700">Căn đã gắn trước đó ({{ $previousBindings->count() }})</p>
        @forelse ($previousBindings as $b)
            <div class="flex items-center justify-between border-b border-slate-50 py-1.5 last:border-0">
                <span>{{ $b->apartment?->code ?? '—' }} · {{ $roleMap[$b->role] ?? $b->role }}</span>
                <span class="text-slate-400">{{ $b->status }} · từ {{ $b->starts_at?->format('d/m/Y') }}</span>
            </div>
        @empty
            <p class="text-slate-400">Chưa gắn căn nào — đây sẽ là căn đầu tiên.</p>
        @endforelse
    </div>

    @if ($record->review_note)
        <div class="rounded-xl border border-slate-100 bg-slate-50 p-3 text-slate-600">
            <span class="font-semibold text-slate-700">Ghi chú duyệt:</span> {{ $record->review_note }}
        </div>
    @endif
</div>
