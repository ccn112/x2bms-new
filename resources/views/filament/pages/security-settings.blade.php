<x-filament-panels::page>
    <a href="{{ url('/admin/my-profile') }}" class="mb-1 inline-flex items-center gap-1.5 text-sm font-medium text-x2-primary hover:underline">
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
        Hồ sơ của tôi
    </a>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
        {{-- Password --}}
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-gray-900">
            <h3 class="text-base font-semibold text-slate-900 dark:text-white">Đổi mật khẩu</h3>
            <div class="mt-4 space-y-4">
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-600 dark:text-slate-300">Mật khẩu hiện tại</label>
                    <input type="password" wire:model="current_password" class="w-full rounded-lg border-slate-200 text-sm focus:border-x2-primary focus:ring-x2-primary" />
                    @error('current_password') <p class="mt-1 text-xs text-x2-red">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-600 dark:text-slate-300">Mật khẩu mới</label>
                    <input type="password" wire:model="new_password" class="w-full rounded-lg border-slate-200 text-sm focus:border-x2-primary focus:ring-x2-primary" />
                    @error('new_password') <p class="mt-1 text-xs text-x2-red">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-600 dark:text-slate-300">Xác nhận mật khẩu mới</label>
                    <input type="password" wire:model="new_password_confirmation" class="w-full rounded-lg border-slate-200 text-sm focus:border-x2-primary focus:ring-x2-primary" />
                </div>
                <button type="button" wire:click="changePassword" class="rounded-xl bg-x2-primary px-4 py-2.5 text-sm font-semibold text-white hover:bg-x2-primary-600">Cập nhật mật khẩu</button>
            </div>
        </div>

        {{-- 2FA --}}
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-gray-900">
            <div class="flex items-start justify-between">
                <div>
                    <h3 class="text-base font-semibold text-slate-900 dark:text-white">Xác thực hai lớp (2FA)</h3>
                    <p class="text-sm text-slate-500">Tăng bảo mật bằng mã OTP từ ứng dụng Authenticator.</p>
                </div>
                <button type="button" wire:click="toggle2fa" role="switch" :aria-checked="@js($twoFa)"
                    class="relative mt-1 h-6 w-11 shrink-0 rounded-full transition {{ $twoFa ? 'bg-x2-green' : 'bg-slate-300' }}">
                    <span class="absolute top-0.5 h-5 w-5 rounded-full bg-white transition-all {{ $twoFa ? 'left-[22px]' : 'left-0.5' }}"></span>
                </button>
            </div>
            @if ($twoFa)
                <div class="mt-4 rounded-xl border border-slate-100 p-4 dark:border-white/10">
                    <div class="flex items-center gap-4">
                        <div class="grid h-24 w-24 shrink-0 place-items-center rounded-lg border-2 border-dashed border-slate-200 text-slate-300">
                            <svg class="h-10 w-10" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.4"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4h6v6H4zM14 4h6v6h-6zM4 14h6v6H4zM14 14h3v3h-3zM19 14h1v1h-1zM17 19h3v1h-3z"/></svg>
                        </div>
                        <div class="text-sm">
                            <p class="text-slate-500">Quét mã QR bằng app Authenticator, hoặc nhập khóa bí mật:</p>
                            <code class="mt-1 inline-block rounded bg-slate-100 px-2 py-1 text-xs font-semibold dark:bg-white/10">X2BMS-{{ strtoupper(\Illuminate\Support\Str::random(12)) }}</code>
                        </div>
                    </div>
                    <div class="mt-4">
                        <p class="mb-1.5 text-xs font-semibold uppercase tracking-wide text-slate-400">Mã khôi phục (lưu nơi an toàn)</p>
                        <div class="grid grid-cols-2 gap-1.5 sm:grid-cols-4">
                            @foreach ($this->recoveryCodes() as $code)
                                <code class="rounded bg-slate-50 px-2 py-1 text-center text-xs text-slate-600 dark:bg-white/5 dark:text-slate-300">{{ $code }}</code>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif
        </div>

        {{-- Login alerts --}}
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-gray-900 xl:col-span-2">
            <h3 class="text-base font-semibold text-slate-900 dark:text-white">Cảnh báo đăng nhập</h3>
            <div class="mt-4 space-y-3">
                <label class="flex items-center justify-between gap-4 rounded-lg border border-slate-100 px-4 py-3 dark:border-white/10">
                    <span class="text-sm text-slate-700 dark:text-slate-200">Thông báo khi có đăng nhập mới</span>
                    <input type="checkbox" wire:model="loginAlert" class="h-4 w-4 rounded border-slate-300 text-x2-primary focus:ring-x2-primary" />
                </label>
                <label class="flex items-center justify-between gap-4 rounded-lg border border-slate-100 px-4 py-3 dark:border-white/10">
                    <span class="text-sm text-slate-700 dark:text-slate-200">Thông báo khi đăng nhập thất bại</span>
                    <input type="checkbox" wire:model="failedLoginAlert" class="h-4 w-4 rounded border-slate-300 text-x2-primary focus:ring-x2-primary" />
                </label>
                <label class="flex items-center justify-between gap-4 rounded-lg border border-slate-100 px-4 py-3 dark:border-white/10">
                    <span class="text-sm text-slate-700 dark:text-slate-200">Tự khóa phiên sau (phút không hoạt động)</span>
                    <input type="number" min="5" max="120" wire:model="sessionTimeout" class="w-24 rounded-lg border-slate-200 text-sm focus:border-x2-primary focus:ring-x2-primary" />
                </label>
                <div class="flex items-center justify-between border-t border-slate-100 pt-3 dark:border-white/10">
                    <a href="{{ url('/admin/sessions') }}" class="text-sm font-medium text-x2-primary hover:underline">Xem phiên đăng nhập →</a>
                    <button type="button" wire:click="saveAlerts" class="rounded-xl bg-x2-primary px-4 py-2 text-sm font-semibold text-white hover:bg-x2-primary-600">Lưu cấu hình</button>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>
