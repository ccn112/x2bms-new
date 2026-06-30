@php
    $initials = \Illuminate\Support\Str::of($r->full_name)->explode(' ')->map(fn ($w) => mb_substr($w, 0, 1))->take(-2)->implode('');
    $fmt = fn ($v) => number_format((float) $v, 0, ',', '.');
    $row = fn ($label, $value) => '<div><dt class="text-xs text-slate-500">'.$label.'</dt><dd class="mt-0.5 text-sm font-medium text-slate-800">'.($value !== null && $value !== '' ? e($value) : '—').'</dd></div>';
@endphp

<x-filament-panels::page>
    {{-- ===== Header summary ===== --}}
    <div class="rounded-2xl border border-slate-100 bg-white p-5 shadow-sm">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="flex items-start gap-4">
                <span class="grid h-[72px] w-[72px] shrink-0 place-items-center rounded-full bg-x2-navy text-2xl font-bold text-white">{{ $initials }}</span>
                <div>
                    <div class="flex items-center gap-2">
                        <h2 class="font-title text-xl font-bold text-slate-900">{{ $r->full_name }}</h2>
                        <x-x2.status-badge :label="$status[0]" :tone="$status[1]" />
                    </div>
                    <div class="mt-1 space-y-1 text-sm text-slate-600">
                        <div class="flex items-center gap-1.5"><svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7"><path stroke-linecap="round" stroke-linejoin="round" d="M3 21h18M5 21V7l7-4 7 4v14"/></svg>{{ $roleLabel }}</div>
                        <div class="flex flex-wrap items-center gap-x-6 gap-y-1">
                            <span class="flex items-center gap-1.5"><svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7"><path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 0 1 2-2h2l2 5-2 1a11 11 0 0 0 5 5l1-2 5 2v2a2 2 0 0 1-2 2A16 16 0 0 1 3 5Z"/></svg>{{ $r->phone ?? '—' }}</span>
                            <span class="flex items-center gap-1.5"><svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16v12H4zM4 7l8 6 8-6"/></svg>{{ $r->email ?? '—' }}</span>
                        </div>
                        <div class="flex items-start gap-1.5"><svg class="mt-0.5 h-4 w-4 shrink-0 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21s7-5.6 7-11a7 7 0 1 0-14 0c0 5.4 7 11 7 11Z"/><circle cx="12" cy="10" r="2.5"/></svg><span>{{ $r->contact_address ?? ($apartment?->code.' · '.$r->building?->name) }}</span></div>
                    </div>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ url('/fila/residents/'.$r->id.'/edit') }}" class="flex items-center gap-1.5 rounded-lg bg-x2-primary px-3.5 py-2 text-sm font-medium text-white hover:bg-x2-primary-600">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="m16 4 4 4-11 11H5v-4L16 4Z"/></svg>Chỉnh sửa
                </a>
                <a href="#" class="flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3.5 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M16 14a4 4 0 1 0-8 0M12 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6Zm6 6 2 2 4-4"/></svg>Phân quyền
                </a>
                @if ($r->status === 'inactive')
                    <button type="button" wire:click="unlock" wire:confirm="Mở khóa tài khoản cư dân này?" class="flex items-center gap-1.5 rounded-lg bg-x2-green px-3.5 py-2 text-sm font-medium text-white hover:opacity-90">Mở khóa</button>
                @else
                    <button type="button" wire:click="lock" wire:confirm="Khóa tài khoản cư dân này?" class="flex items-center gap-1.5 rounded-lg border border-x2-red/30 bg-white px-3.5 py-2 text-sm font-medium text-x2-red hover:bg-x2-red/5">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M6 11V8a6 6 0 1 1 12 0v3m-13 0h14v9a1 1 0 0 1-1 1H6a1 1 0 0 1-1-1v-9Z"/></svg>Khóa tài khoản
                    </button>
                @endif
                <a href="{{ url('/admin/residents') }}" title="Quay lại danh sách" class="grid h-9 w-9 place-items-center rounded-lg border border-slate-200 text-slate-500 hover:bg-slate-50">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v.01M12 12v.01M12 19v.01"/></svg>
                </a>
            </div>
        </div>

        {{-- ===== Overview cards ===== --}}
        <div class="mt-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-7">
            @php
                $overviewCards = [
                    ['Mã cư dân', $r->code, 'blue', null],
                    ['Ngày tham gia', $r->join_date?->format('d/m/Y') ?? ($r->created_at?->format('d/m/Y') ?? '—'), 'teal', null],
                    ['Trạng thái tài khoản', $status[0], $status[1], null, true],
                    ['Tổng số thành viên', $overview['members'].' người', 'blue', null],
                    ['Tổng số phản ánh', $overview['feedback'], 'amber', '#'],
                    ['Tổng số hóa đơn', $overview['invoices'], 'teal', '#'],
                    ['Công nợ hiện tại', $fmt($overview['overdueDebt']).' đ', 'red', '#'],
                ];
            @endphp
            @foreach ($overviewCards as $c)
                <div class="rounded-xl border border-slate-100 bg-white px-3.5 py-3">
                    <div class="text-[11px] text-slate-500">{{ $c[0] }}</div>
                    <div class="mt-1 font-title text-lg font-bold {{ $c[2] === 'red' ? 'text-x2-red' : ($c[2] === 'green' ? 'text-x2-green' : 'text-slate-900') }}">
                        @if (!empty($c[4]))
                            <span class="inline-flex items-center gap-1.5"><span class="h-2 w-2 rounded-full {{ $c[2] === 'green' ? 'bg-x2-green' : ($c[2] === 'red' ? 'bg-x2-red' : 'bg-x2-amber') }}"></span>{{ $c[1] }}</span>
                        @else
                            {{ $c[1] }}
                        @endif
                    </div>
                    @if (!empty($c[3]))
                        <a href="{{ $c[3] }}" class="mt-0.5 inline-block text-[11px] font-medium text-x2-primary hover:underline">Xem chi tiết</a>
                    @endif
                </div>
            @endforeach
        </div>
    </div>

    {{-- ===== Tabs ===== --}}
    <div x-data="{ tab: 'general' }" class="space-y-4">
        <div class="flex flex-wrap gap-1 border-b border-slate-200">
            @foreach ([
                'general' => 'Thông tin chung',
                'account' => 'Tài khoản',
                'vehicles' => 'Phương tiện',
                'invoices' => 'Hóa đơn',
                'feedback' => 'Phản ánh',
                'activity' => 'Nhật ký',
            ] as $key => $label)
                <button type="button" x-on:click="tab = '{{ $key }}'"
                    x-bind:class="tab === '{{ $key }}' ? 'border-x2-primary text-x2-primary' : 'border-transparent text-slate-500 hover:text-slate-700'"
                    class="-mb-px border-b-2 px-3.5 py-2.5 text-sm font-medium transition">{{ $label }}</button>
            @endforeach
        </div>

        {{-- ----- Thông tin chung ----- --}}
        <div x-show="tab === 'general'" x-cloak class="space-y-4">
            <div class="grid gap-4 lg:grid-cols-3">
                <x-x2.section-card title="Thông tin cá nhân" class="lg:col-span-2">
                    <div class="grid gap-x-10 gap-y-4 sm:grid-cols-2">
                        <dl class="space-y-4">
                            {!! $row('Họ và tên', $r->full_name) !!}
                            {!! $row('Ngày sinh', $r->dob?->format('d/m/Y')) !!}
                            {!! $row('Giới tính', $r->gender) !!}
                            {!! $row('Số CMND/CCCD', $r->id_no) !!}
                            {!! $row('Ngày cấp', $r->id_issued_date?->format('d/m/Y')) !!}
                            {!! $row('Nơi cấp', $r->id_issued_place) !!}
                            {!! $row('Quốc tịch', $r->nationality) !!}
                            {!! $row('Tình trạng hôn nhân', $r->marital_status) !!}
                        </dl>
                        <dl class="space-y-4">
                            {!! $row('Số điện thoại', $r->phone) !!}
                            {!! $row('Email', $r->email) !!}
                            {!! $row('Địa chỉ liên hệ', $r->contact_address) !!}
                            {!! $row('Địa chỉ nhận thư', $r->mailing_address) !!}
                            {!! $row('Ghi chú', $r->note) !!}
                        </dl>
                    </div>
                </x-x2.section-card>

                <x-x2.section-card title="Thông tin căn hộ">
                    <dl class="space-y-4">
                        {!! $row('Mã căn hộ', $apartment?->code) !!}
                        {!! $row('Tòa / Tháp', $r->building?->name) !!}
                        {!! $row('Diện tích', $apartment?->area_sqm ? number_format((float) $apartment->area_sqm, 1).' m²' : null) !!}
                        {!! $row('Loại căn hộ', $apartment?->type) !!}
                        {!! $row('Hình thức sở hữu', $apartment?->ownership_type) !!}
                        {!! $row('Ngày nhận bàn giao', $apartment?->handover_date?->format('d/m/Y')) !!}
                        {!! $row('Phí quản lý (m²)', $apartment?->management_fee ? $fmt($apartment->management_fee).' đ/m²' : null) !!}
                        <div><dt class="text-xs text-slate-500">Trạng thái</dt><dd class="mt-0.5"><x-x2.status-badge label="Đang ở" tone="green" /></dd></div>
                    </dl>
                </x-x2.section-card>
            </div>

            <x-x2.section-card title="Thông tin liên hệ khẩn cấp">
                <x-x2.data-table
                    :columns="[
                        ['key' => 'name', 'label' => 'Họ và tên'],
                        ['key' => 'rel', 'label' => 'Mối quan hệ'],
                        ['key' => 'phone', 'label' => 'Số điện thoại'],
                        ['key' => 'email', 'label' => 'Email'],
                        ['key' => 'note', 'label' => 'Ghi chú'],
                    ]"
                    :rows="$emergencyContacts->map(fn ($c) => [
                        'name' => '<span class=\'font-medium text-slate-800\'>'.e($c->full_name).'</span>',
                        'rel' => e($c->relationship ?? '—'),
                        'phone' => e($c->phone ?? '—'),
                        'email' => '<span class=\'text-slate-500\'>'.e($c->email ?? '—').'</span>',
                        'note' => e($c->note ?? '—'),
                    ])->all()"
                    empty="Chưa có liên hệ khẩn cấp" />
            </x-x2.section-card>
        </div>

        {{-- ----- Tài khoản ----- --}}
        <div x-show="tab === 'account'" x-cloak>
            <x-x2.section-card title="Tài khoản đăng nhập">
                <dl class="grid gap-x-10 gap-y-4 sm:grid-cols-2">
                    {!! $row('Email đăng nhập', $r->email) !!}
                    <div><dt class="text-xs text-slate-500">Trạng thái tài khoản</dt><dd class="mt-0.5"><x-x2.status-badge :label="$status[0]" :tone="$status[1]" /></dd></div>
                    {!! $row('Đã kích hoạt', $r->user_id ? 'Rồi' : 'Chưa kích hoạt') !!}
                    {!! $row('Vai trò', $roleLabel) !!}
                </dl>
            </x-x2.section-card>
        </div>

        {{-- ----- Phương tiện ----- --}}
        <div x-show="tab === 'vehicles'" x-cloak class="grid gap-4 lg:grid-cols-2">
            <x-x2.section-card title="Phương tiện" :subtitle="$vehicles->count().' xe'">
                @forelse ($vehicles as $v)
                    <div class="flex items-center justify-between border-b border-slate-50 py-2 text-sm last:border-0">
                        <span class="font-medium text-slate-800">{{ $v->plate_no }}</span>
                        <span class="text-slate-500">{{ $v->type->label() }} · {{ $v->parking_card_no ?? '—' }}</span>
                    </div>
                @empty
                    <p class="py-4 text-center text-sm text-slate-400">Không có phương tiện</p>
                @endforelse
            </x-x2.section-card>
            <x-x2.section-card title="Thẻ ra vào" :subtitle="$cards->count().' thẻ'">
                @forelse ($cards as $c)
                    <div class="flex items-center justify-between border-b border-slate-50 py-2 text-sm last:border-0">
                        <span class="font-medium text-slate-800">{{ $c->card_no }}</span>
                        <span class="text-slate-500">{{ $c->is_biometric ? 'Sinh trắc' : 'RFID' }} · HL {{ $c->valid_to?->format('d/m/Y') ?? '—' }}</span>
                    </div>
                @empty
                    <p class="py-4 text-center text-sm text-slate-400">Không có thẻ</p>
                @endforelse
            </x-x2.section-card>
        </div>

        {{-- ----- Hóa đơn ----- --}}
        <div x-show="tab === 'invoices'" x-cloak>
            <x-x2.section-card title="Hóa đơn & Công nợ">
                <div class="grid gap-3 sm:grid-cols-3">
                    <div class="rounded-xl bg-slate-50 px-4 py-3"><div class="text-xs text-slate-500">Tổng đã phát hành</div><div class="font-title text-lg font-bold text-slate-900">{{ $fmt($overview['billed']) }} đ</div></div>
                    <div class="rounded-xl bg-slate-50 px-4 py-3"><div class="text-xs text-slate-500">Số hóa đơn</div><div class="font-title text-lg font-bold text-slate-900">{{ $overview['invoices'] }}</div></div>
                    <div class="rounded-xl bg-slate-50 px-4 py-3"><div class="text-xs text-slate-500">Công nợ quá hạn</div><div class="font-title text-lg font-bold {{ $overview['overdueDebt'] > 0 ? 'text-x2-red' : 'text-x2-green' }}">{{ $fmt($overview['overdueDebt']) }} đ</div></div>
                </div>
            </x-x2.section-card>
        </div>

        {{-- ----- Phản ánh ----- --}}
        <div x-show="tab === 'feedback'" x-cloak>
            <x-x2.section-card title="Phản ánh">
                <p class="py-6 text-center text-sm text-slate-500">Tổng <span class="font-semibold text-slate-800">{{ $overview['feedback'] }}</span> phản ánh liên quan tới căn hộ của cư dân.</p>
            </x-x2.section-card>
        </div>

        {{-- ----- Nhật ký ----- --}}
        <div x-show="tab === 'activity'" x-cloak>
            <x-x2.section-card title="Nhật ký hoạt động">
                <ul class="space-y-3">
                    @forelse ($audits as $a)
                        <li class="flex items-start gap-3">
                            <span class="mt-1.5 h-2 w-2 shrink-0 rounded-full bg-x2-primary"></span>
                            <div class="text-sm"><div class="text-slate-700">{{ $a->description }}</div><div class="text-xs text-slate-400">{{ $a->actor_name }} · {{ $a->created_at?->format('d/m/Y H:i') }}</div></div>
                        </li>
                    @empty
                        <li class="py-4 text-center text-sm text-slate-400">Chưa có hoạt động</li>
                    @endforelse
                </ul>
            </x-x2.section-card>
        </div>
    </div>
</x-filament-panels::page>
