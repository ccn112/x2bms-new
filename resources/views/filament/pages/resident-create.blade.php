@php
    $d = $this->data ?? [];
    $roleLabels = ['owner' => 'Chủ sở hữu', 'tenant' => 'Người thuê', 'member' => 'Thành viên hộ'];
    $statusLabels = ['cho_bo_sung' => 'Chờ bổ sung', 'cho_duyet' => 'Chờ duyệt', 'hoat_dong' => 'Hoạt động', 'tu_choi' => 'Từ chối'];
    $completion = $this->completion();
    $missing = $this->missingRequired();

    $ua = request()->userAgent() ?? '';
    $browser = \Illuminate\Support\Str::contains($ua, 'Edg') ? 'Edge'
        : (\Illuminate\Support\Str::contains($ua, 'Chrome') ? 'Chrome'
        : (\Illuminate\Support\Str::contains($ua, 'Firefox') ? 'Firefox'
        : (\Illuminate\Support\Str::contains($ua, 'Safari') ? 'Safari' : 'Trình duyệt')));
    $os = \Illuminate\Support\Str::contains($ua, 'Windows') ? 'Windows'
        : (\Illuminate\Support\Str::contains($ua, 'Mac') ? 'macOS'
        : (\Illuminate\Support\Str::contains($ua, 'Android') ? 'Android'
        : (\Illuminate\Support\Str::contains($ua, 'Linux') ? 'Linux' : 'Hệ điều hành')));
@endphp

<x-filament-panels::page>
    {{-- Top action bar --}}
    <div class="flex flex-wrap items-center gap-2">
        <x-filament::button color="gray" icon="heroicon-o-document-arrow-down" wire:click="saveDraft">
            Lưu nháp
        </x-filament::button>
        <x-filament::button icon="heroicon-o-check" wire:click="create(false)">
            Lưu
        </x-filament::button>
        <x-filament::button color="success" icon="heroicon-o-paper-airplane" wire:click="create(true)">
            Lưu &amp; gửi duyệt
        </x-filament::button>
        <x-filament::button color="gray" tag="a" icon="heroicon-o-x-mark"
            :href="\App\Filament\Pages\ResidentDirectory::getUrl()">
            Hủy
        </x-filament::button>
    </div>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-12">
        {{-- LEFT: the form --}}
        <div class="xl:col-span-8">
            {{ $this->form }}
        </div>

        {{-- RIGHT: profile summary + control info --}}
        <div class="space-y-4 xl:col-span-4">
            {{-- Hồ sơ cư dân --}}
            <div class="rounded-xl border border-slate-200 bg-white p-4">
                <h3 class="mb-3 font-title text-base font-semibold text-x2-navy">Hồ sơ cư dân</h3>
                <dl class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <dt class="text-slate-500">Mã hồ sơ</dt>
                        <dd class="text-slate-400">Tự động sau khi lưu</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-slate-500">Trạng thái</dt>
                        <dd>
                            <span class="rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-700">
                                {{ $statusLabels[$d['profile_status'] ?? 'cho_bo_sung'] ?? 'Chờ bổ sung' }}
                            </span>
                        </dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-slate-500">Vai trò</dt>
                        <dd class="font-medium text-slate-700">{{ $roleLabels[$d['requested_role'] ?? ''] ?? 'Chưa chọn' }}</dd>
                    </div>
                    <div>
                        <div class="mb-1 flex justify-between">
                            <dt class="text-slate-500">Mức độ hoàn thiện</dt>
                            <dd class="font-semibold {{ $completion === 100 ? 'text-x2-green' : 'text-x2-navy' }}">{{ $completion }}%</dd>
                        </div>
                        <div class="h-2 w-full overflow-hidden rounded-full bg-slate-100">
                            <div class="h-full rounded-full bg-x2-gold transition-all" style="width: {{ $completion }}%"></div>
                        </div>
                    </div>
                    <hr class="my-2 border-slate-100">
                    <div class="flex justify-between"><dt class="text-slate-500">Ngày tạo</dt><dd class="text-slate-400">--</dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500">Người tạo</dt><dd class="font-medium text-slate-700">{{ auth()->user()->name }}</dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500">Cập nhật cuối</dt><dd class="text-slate-400">--</dd></div>
                </dl>
            </div>

            {{-- Thông tin kiểm soát --}}
            <div class="rounded-xl border border-slate-200 bg-white p-4">
                <h3 class="mb-3 font-title text-base font-semibold text-x2-navy">Thông tin kiểm soát</h3>
                <dl class="space-y-2 text-sm">
                    <div class="flex justify-between"><dt class="text-slate-500">Tạo lúc</dt><dd class="text-slate-700">{{ now()->format('d/m/Y H:i') }}</dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500">Cập nhật lúc</dt><dd class="text-slate-400">--</dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500">Địa chỉ IP</dt><dd class="text-slate-700">{{ request()->ip() }}</dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500">Thiết bị</dt><dd class="text-slate-700">{{ $browser }} / {{ $os }}</dd></div>
                </dl>
            </div>

        </div>{{-- /right column (profile + control) --}}
    </div>
</x-filament-panels::page>
