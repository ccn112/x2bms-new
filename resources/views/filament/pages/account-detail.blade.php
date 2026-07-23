@php
    $toneClass = [
        'red' => 'bg-red-50 text-red-600 dark:bg-red-500/10 dark:text-red-300',
        'amber' => 'bg-amber-50 text-amber-600 dark:bg-amber-500/10 dark:text-amber-300',
        'slate' => 'bg-slate-100 text-slate-500 dark:bg-white/10 dark:text-slate-300',
    ];
    $idStatus = [
        'verified' => ['Đã xác thực', 'green'], 'phone_verified' => ['Xác thực SĐT', 'blue'],
        'email_verified' => ['Xác thực email', 'blue'], 'unverified' => ['Chưa xác thực', 'amber'],
    ];
    [$idLbl, $idTone] = $idStatus[$a->identity_status] ?? [$a->identity_status ?? '—', 'slate'];
@endphp

<x-filament-panels::page>
    <a href="{{ url('/admin/resident-accounts/activations') }}" class="mb-2 inline-flex items-center gap-1.5 text-sm font-medium text-x2-primary hover:underline">
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
        Quay lại hàng đợi kích hoạt
    </a>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-[320px_1fr_320px]">
        {{-- Left: profile + actions --}}
        <div class="space-y-4">
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-gray-900">
                <div class="flex items-center gap-3">
                    <span class="grid h-12 w-12 place-items-center rounded-full bg-x2-navy text-lg font-bold text-white">
                        {{ \Illuminate\Support\Str::of($a->full_name ?: 'TK')->explode(' ')->map(fn ($w) => mb_substr($w, 0, 1))->take(2)->implode('') }}
                    </span>
                    <div class="min-w-0">
                        <div class="truncate font-semibold text-slate-900 dark:text-white">{{ $a->full_name ?: '—' }}</div>
                        <x-x2.status-badge :label="$idLbl" :tone="$idTone" />
                    </div>
                </div>
                <dl class="mt-4 space-y-2.5 border-t border-slate-100 pt-4 text-sm dark:border-white/10">
                    <div class="flex justify-between gap-2"><dt class="text-slate-400">SĐT</dt><dd class="font-medium text-slate-700 dark:text-slate-200">{{ $a->phone ?: '—' }}</dd></div>
                    <div class="flex justify-between gap-2"><dt class="text-slate-400">Email</dt><dd class="font-medium text-slate-700 dark:text-slate-200">{{ $a->email ?: '—' }}</dd></div>
                    <div class="flex justify-between gap-2"><dt class="text-slate-400">Loại TK</dt><dd class="font-medium text-slate-700 dark:text-slate-200">{{ $a->account_type }}</dd></div>
                    <div class="flex justify-between gap-2"><dt class="text-slate-400">Điểm rủi ro</dt><dd class="font-semibold {{ $a->risk_score >= 50 ? 'text-x2-red' : 'text-slate-700 dark:text-slate-200' }}">{{ $a->risk_score }}</dd></div>
                    <div class="flex justify-between gap-2"><dt class="text-slate-400">Đăng nhập gần nhất</dt><dd class="font-medium text-slate-700 dark:text-slate-200">{{ $a->last_login_at?->format('d/m/Y H:i') ?? '—' }}</dd></div>
                    <div class="flex justify-between gap-2"><dt class="text-slate-400">Trạng thái</dt><dd>@if ($a->account_status === 'suspended')<span class="font-semibold text-x2-red">Đang khóa</span>@else<span class="font-medium text-x2-green">Hoạt động</span>@endif</dd></div>
                </dl>
                <div class="mt-4 flex flex-col gap-2">
                    <button type="button" wire:click="invite" class="w-full rounded-lg border border-x2-primary/30 bg-x2-primary/5 px-3 py-2 text-sm font-medium text-x2-primary hover:bg-x2-primary/10">
                        {{ filled(($a->metadata_json ?? [])['activation_invited_at'] ?? null) ? 'Gửi lại lời mời kích hoạt' : 'Mời kích hoạt' }}
                    </button>
                    @if ($a->account_status === 'suspended')
                        <button type="button" wire:click="unlock" wire:confirm="Mở khóa tài khoản này?" class="w-full rounded-lg border border-green-200 bg-green-50 px-3 py-2 text-sm font-medium text-green-600 hover:bg-green-100">Mở khóa tài khoản</button>
                    @else
                        <button type="button" wire:click="lock" wire:confirm="Khóa tài khoản này?" class="w-full rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm font-medium text-red-600 hover:bg-red-100">Khóa tài khoản</button>
                    @endif
                </div>
            </div>
        </div>

        {{-- Center: bindings + devices --}}
        <div class="space-y-4">
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-gray-900">
                <h3 class="text-base font-semibold text-slate-900 dark:text-white">Căn hộ đã gắn ({{ count($bindings) }})</h3>
                <div class="mt-3 space-y-2">
                    @forelse ($bindings as $b)
                        <div class="flex items-center justify-between rounded-xl border border-slate-100 px-4 py-2.5 dark:border-white/10">
                            <div>
                                <div class="font-medium text-slate-800 dark:text-slate-100">{{ $b['apartment'] }}</div>
                                <div class="text-xs text-slate-400">{{ $b['building'] }}@if ($b['starts_at']) · từ {{ $b['starts_at']->format('d/m/Y') }}@endif</div>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="text-xs text-slate-500">{{ $b['role'] }}</span>
                                <x-x2.status-badge :label="$b['status']" :tone="$b['status'] === 'active' ? 'green' : 'slate'" />
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-slate-400">Chưa gắn căn hộ nào.</p>
                    @endforelse
                </div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-gray-900">
                <h3 class="text-base font-semibold text-slate-900 dark:text-white">Thiết bị đăng nhập ({{ count($devices) }})</h3>
                <div class="mt-3 space-y-2">
                    @forelse ($devices as $d)
                        <div class="flex items-center justify-between rounded-xl border border-slate-100 px-4 py-2.5 dark:border-white/10">
                            <div>
                                <div class="font-medium text-slate-800 dark:text-slate-100">{{ ucfirst($d['platform'] ?? '—') }}@if ($d['app_version']) <span class="text-xs font-normal text-slate-400">v{{ $d['app_version'] }}</span>@endif</div>
                                <div class="text-xs text-slate-400">Hoạt động: {{ $d['last_seen_at']?->diffForHumans() ?? '—' }}</div>
                            </div>
                            @if ($d['revoked'])
                                <span class="inline-flex rounded-md bg-slate-100 px-2 py-0.5 text-xs text-slate-500 dark:bg-white/10">Đã thu hồi</span>
                            @else
                                <span class="inline-flex rounded-md bg-green-50 px-2 py-0.5 text-xs font-medium text-green-600 dark:bg-green-500/10 dark:text-green-300">Hoạt động</span>
                            @endif
                        </div>
                    @empty
                        <p class="text-sm text-slate-400">Chưa có thiết bị nào đăng nhập app.</p>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Right: risk --}}
        <div class="space-y-4">
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-gray-900">
                <h3 class="text-sm font-semibold text-slate-900 dark:text-white">Đánh giá rủi ro</h3>
                @if (empty($risk))
                    <p class="mt-3 text-sm text-x2-green">Không có cảnh báo — sẵn sàng kích hoạt.</p>
                @else
                    <ul class="mt-3 space-y-2.5">
                        @foreach ($risk as $f)
                            <li class="rounded-xl p-3 {{ $toneClass[$f['tone']] ?? $toneClass['slate'] }}">
                                <p class="text-sm font-medium">{{ $f['label'] }}</p>
                                @if (! empty($f['checklist']))
                                    <ul class="mt-1.5 space-y-1 text-xs opacity-80">
                                        @foreach ($f['checklist'] as $item)
                                            <li class="flex gap-1.5"><span>•</span><span>{{ $item }}</span></li>
                                        @endforeach
                                    </ul>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    </div>
</x-filament-panels::page>
