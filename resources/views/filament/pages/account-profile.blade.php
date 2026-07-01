<div class="space-y-5 text-sm">
    <div class="grid grid-cols-2 gap-4">
        <div class="rounded-xl border border-slate-100 p-4">
            <p class="mb-2 font-semibold text-slate-700">Thông tin định danh</p>
            <dl class="space-y-1.5 text-slate-600">
                <div class="flex justify-between"><dt>Họ tên</dt><dd class="font-medium text-slate-800">{{ $account->full_name }}</dd></div>
                <div class="flex justify-between"><dt>SĐT</dt><dd>{{ $account->phone ?? '—' }}</dd></div>
                <div class="flex justify-between"><dt>Email</dt><dd>{{ $account->email ?? '—' }}</dd></div>
                <div class="flex justify-between"><dt>Loại tài khoản</dt><dd>{{ $typeMap[$account->account_type] ?? $account->account_type }}</dd></div>
                <div class="flex justify-between"><dt>Định danh</dt><dd>{{ ($identityMap[$account->identity_status] ?? [$account->identity_status])[0] }}</dd></div>
                <div class="flex justify-between"><dt>Trạng thái</dt><dd>{{ $account->account_status === 'active' ? 'Hoạt động' : 'Đã khoá' }}</dd></div>
            </dl>
        </div>
        <div class="rounded-xl border border-slate-100 p-4">
            <p class="mb-2 font-semibold text-slate-700">Hoạt động & rủi ro</p>
            <dl class="space-y-1.5 text-slate-600">
                <div class="flex justify-between"><dt>Đăng ký</dt><dd>{{ $account->first_registered_at?->format('d/m/Y') ?? '—' }}</dd></div>
                <div class="flex justify-between"><dt>Đăng nhập gần nhất</dt><dd>{{ $account->last_login_at?->format('d/m/Y') ?? '—' }}</dd></div>
                <div class="flex justify-between"><dt>Risk score</dt><dd class="font-medium {{ $account->risk_score >= 60 ? 'text-red-600' : 'text-slate-800' }}">{{ $account->risk_score }}</dd></div>
                <div class="flex justify-between"><dt>Nhóm nghi trùng</dt><dd>{{ $account->duplicate_group_id ?? '—' }}</dd></div>
            </dl>
        </div>
    </div>

    @if ($duplicates->isNotEmpty())
        <div class="rounded-xl border border-amber-200 bg-amber-50 p-3 text-amber-800">
            <p class="font-semibold">⚠ Tài khoản nghi trùng ({{ $duplicates->count() }})</p>
            <ul class="mt-1 list-disc pl-5">
                @foreach ($duplicates as $d)
                    <li>{{ $d->full_name }} — {{ $d->phone }} · {{ $d->email }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="rounded-xl border border-slate-100 p-4">
        <p class="mb-2 font-semibold text-slate-700">Căn đã gắn ({{ $bindings->count() }})</p>
        @forelse ($bindings as $b)
            <div class="flex items-center justify-between border-b border-slate-50 py-1.5 last:border-0">
                <span>{{ $b->apartment?->code ?? '—' }} · {{ $roleMap[$b->role] ?? $b->role }}</span>
                <span class="text-slate-400">{{ $b->status }} · từ {{ $b->starts_at?->format('d/m/Y') }}</span>
            </div>
        @empty
            <p class="text-slate-400">Chưa gắn căn nào.</p>
        @endforelse
    </div>

    <div class="rounded-xl border border-slate-100 p-4">
        <p class="mb-2 font-semibold text-slate-700">Yêu cầu gắn căn ({{ $requests->count() }})</p>
        @forelse ($requests as $r)
            <div class="flex items-center justify-between border-b border-slate-50 py-1.5 last:border-0">
                <span>{{ $r->code }} · {{ $r->apartment?->code ?? '—' }}</span>
                <span class="text-slate-400">{{ $r->status }} · {{ $r->requested_at?->format('d/m/Y') }}</span>
            </div>
        @empty
            <p class="text-slate-400">Chưa có yêu cầu.</p>
        @endforelse
    </div>
</div>
