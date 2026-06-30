@php
    $initials = \Illuminate\Support\Str::of($r->full_name)->explode(' ')->map(fn ($w) => mb_substr($w, 0, 1))->take(-2)->implode('');
    $primary = $r->primaryRelation();
    $apartmentIds = $r->apartmentRelations->pluck('apartment_id')->all() ?: [0];
    $vehicleCount = \App\Models\Vehicle::where('resident_id', $r->id)->count();
    $cardCount = \App\Models\AccessCard::where('resident_id', $r->id)->count();
    $overdue = \App\Models\Debt::whereIn('apartment_id', $apartmentIds)->where('is_overdue', true)->sum('amount');
    $statuses = ['active' => ['Hoạt động', 'green'], 'pending' => ['Chờ duyệt', 'amber'], 'inactive' => ['Đã khóa', 'red']];
    $roles = ['owner' => 'Chủ sở hữu', 'tenant' => 'Người thuê', 'member' => 'Thành viên'];
    [$stLabel, $stTone] = $statuses[$r->status] ?? [$r->status, 'slate'];
    $audits = \App\Models\AuditLog::where('building_id', $r->building_id)->latest()->take(5)->get();
@endphp

<div class="space-y-4">
    {{-- Summary --}}
    <div class="flex items-start gap-3">
        <span class="grid h-14 w-14 shrink-0 place-items-center rounded-2xl bg-x2-navy text-lg font-bold text-white">{{ $initials }}</span>
        <div>
            <div class="flex items-center gap-2">
                <span class="font-title text-base font-bold text-slate-900">{{ $r->full_name }}</span>
                <x-x2.status-badge :label="$stLabel" :tone="$stTone" />
            </div>
            <div class="text-xs text-slate-500">{{ $roles[$primary?->role] ?? '—' }} · {{ $r->code }}</div>
        </div>
    </div>

    {{-- Contact / location --}}
    <dl class="grid grid-cols-2 gap-x-4 gap-y-2 rounded-xl bg-slate-50 px-3 py-3 text-sm">
        <div><dt class="text-[11px] text-slate-500">Điện thoại</dt><dd class="text-slate-800">{{ $r->phone ?? '—' }}</dd></div>
        <div><dt class="text-[11px] text-slate-500">Email</dt><dd class="truncate text-slate-800">{{ $r->email ?? '—' }}</dd></div>
        <div><dt class="text-[11px] text-slate-500">Căn hộ</dt><dd class="text-slate-800">{{ $primary?->apartment?->code ?? '—' }}</dd></div>
        <div><dt class="text-[11px] text-slate-500">Tòa</dt><dd class="text-slate-800">{{ $r->building?->name ?? '—' }}</dd></div>
    </dl>

    {{-- Mini stats --}}
    <div class="grid grid-cols-3 gap-2 text-center">
        <div class="rounded-xl border border-slate-100 py-2"><div class="font-title text-lg font-bold text-slate-900">{{ $vehicleCount }}</div><div class="text-[11px] text-slate-500">Phương tiện</div></div>
        <div class="rounded-xl border border-slate-100 py-2"><div class="font-title text-lg font-bold text-slate-900">{{ $cardCount }}</div><div class="text-[11px] text-slate-500">Thẻ</div></div>
        <div class="rounded-xl border border-slate-100 py-2"><div class="font-title text-lg font-bold {{ $overdue > 0 ? 'text-x2-red' : 'text-x2-green' }}">{{ number_format($overdue / 1e6, 1) }}tr</div><div class="text-[11px] text-slate-500">Công nợ</div></div>
    </div>

    {{-- Recent activity --}}
    <div>
        <div class="mb-1 text-xs font-semibold uppercase tracking-wide text-slate-400">Hoạt động gần đây</div>
        <ul class="space-y-2">
            @forelse ($audits as $a)
                <li class="flex items-start gap-2 text-sm">
                    <span class="mt-1.5 h-1.5 w-1.5 shrink-0 rounded-full bg-x2-primary"></span>
                    <div><div class="text-slate-700">{{ $a->description }}</div><div class="text-[11px] text-slate-400">{{ $a->created_at?->diffForHumans() }}</div></div>
                </li>
            @empty
                <li class="py-2 text-center text-xs text-slate-400">Chưa có hoạt động</li>
            @endforelse
        </ul>
    </div>

    <a href="{{ url('/admin/residents/'.$r->id.'/detail') }}" class="flex items-center justify-center gap-2 rounded-xl bg-x2-primary py-2.5 text-sm font-medium text-white hover:bg-x2-primary-600">
        Xem hồ sơ đầy đủ
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14m-6-6 6 6-6 6"/></svg>
    </a>
</div>
