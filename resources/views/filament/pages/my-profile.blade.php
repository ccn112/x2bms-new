<x-filament-panels::page>
    <div class="grid grid-cols-1 gap-6 xl:grid-cols-[320px_1fr]">
        {{-- Summary card --}}
        <div class="space-y-4">
            <div class="rounded-2xl border border-slate-200 bg-white p-5 text-center shadow-sm dark:border-white/10 dark:bg-gray-900">
                <span class="mx-auto grid h-20 w-20 place-items-center rounded-full bg-x2-navy text-2xl font-bold text-white">
                    {{ \Illuminate\Support\Str::of($user->name)->explode(' ')->map(fn ($w) => mb_substr($w, 0, 1))->take(2)->implode('') }}
                </span>
                <h2 class="mt-3 text-lg font-bold text-slate-900 dark:text-white">{{ $user->name }}</h2>
                <p class="text-sm text-slate-500">{{ $user->title ?: ($roles[0] ?? '—') }}</p>
                <div class="mt-3 flex flex-wrap justify-center gap-1.5">
                    @foreach ($roles as $role)
                        <span class="rounded-md bg-x2-primary/10 px-2 py-0.5 text-xs font-medium text-x2-primary">{{ $role }}</span>
                    @endforeach
                </div>
                <dl class="mt-4 space-y-2 border-t border-slate-100 pt-4 text-left text-sm dark:border-white/10">
                    <div class="flex justify-between gap-2"><dt class="text-slate-400">Email</dt><dd class="truncate font-medium text-slate-700 dark:text-slate-200">{{ $user->email }}</dd></div>
                    <div class="flex justify-between gap-2"><dt class="text-slate-400">Điện thoại</dt><dd class="font-medium text-slate-700 dark:text-slate-200">{{ $user->phone ?: '—' }}</dd></div>
                    <div class="flex justify-between gap-2"><dt class="text-slate-400">Dự án</dt><dd class="truncate font-medium text-slate-700 dark:text-slate-200">{{ $project?->name ?? '—' }}</dd></div>
                    <div class="flex justify-between gap-2"><dt class="text-slate-400">Phạm vi</dt><dd class="font-medium text-slate-700 dark:text-slate-200">{{ $buildingLabel ?: '—' }}</dd></div>
                </dl>
                <a href="{{ url('/admin/security') }}" class="mt-4 flex items-center justify-center gap-2 rounded-xl border border-slate-200 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50 dark:border-white/10 dark:text-slate-200">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3l7 3v5c0 4.5-3 8-7 10-4-2-7-5.5-7-10V6l7-3Z"/></svg>
                    Đổi mật khẩu & Bảo mật
                </a>
            </div>
        </div>

        {{-- Forms --}}
        <div class="space-y-6">
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-gray-900">
                <h3 class="text-base font-semibold text-slate-900 dark:text-white">Thông tin cá nhân & liên hệ</h3>
                <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-600 dark:text-slate-300">Họ và tên</label>
                        <input type="text" wire:model="name" class="w-full rounded-lg border-slate-200 text-sm focus:border-x2-primary focus:ring-x2-primary" />
                        @error('name') <p class="mt-1 text-xs text-x2-red">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-600 dark:text-slate-300">Chức danh</label>
                        <input type="text" wire:model="title_field" disabled class="w-full rounded-lg border-slate-200 bg-slate-50 text-sm text-slate-500" />
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-600 dark:text-slate-300">Số điện thoại</label>
                        <input type="text" wire:model="phone" class="w-full rounded-lg border-slate-200 text-sm focus:border-x2-primary focus:ring-x2-primary" />
                        @error('phone') <p class="mt-1 text-xs text-x2-red">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-600 dark:text-slate-300">Email</label>
                        <input type="email" wire:model="email" class="w-full rounded-lg border-slate-200 text-sm focus:border-x2-primary focus:ring-x2-primary" />
                        @error('email') <p class="mt-1 text-xs text-x2-red">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>

            {{-- Notification preferences --}}
            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-white/10 dark:bg-gray-900">
                <div class="border-b border-slate-100 px-5 py-3 dark:border-white/10">
                    <h3 class="text-base font-semibold text-slate-900 dark:text-white">Tùy chọn nhận thông báo</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[520px] text-sm">
                        <thead><tr class="border-b border-slate-100 text-left text-xs uppercase tracking-wide text-slate-400 dark:border-white/10">
                            <th class="px-5 py-2.5 font-medium">Danh mục</th>
                            @foreach (\App\Filament\Pages\MyProfile::NOTIF_CHANNELS as $ck => $cl)
                                <th class="px-3 py-2.5 text-center font-medium">{{ $cl }}</th>
                            @endforeach
                        </tr></thead>
                        <tbody class="divide-y divide-slate-50 dark:divide-white/5">
                            @foreach (\App\Filament\Pages\MyProfile::NOTIF_ROWS as $rk => $rl)
                                <tr>
                                    <td class="px-5 py-3 font-medium text-slate-700 dark:text-slate-200">{{ $rl }}</td>
                                    @foreach (array_keys(\App\Filament\Pages\MyProfile::NOTIF_CHANNELS) as $ck)
                                        <td class="px-3 py-3 text-center">
                                            <input type="checkbox" wire:model="notif.{{ $rk }}.{{ $ck }}" class="h-4 w-4 rounded border-slate-300 text-x2-primary focus:ring-x2-primary" />
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="flex items-center justify-end gap-3">
                <a href="{{ url('/admin/dashboard') }}" class="rounded-xl border border-slate-200 px-5 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-50 dark:border-white/10 dark:text-slate-200">Hủy</a>
                <button type="button" wire:click="save" class="rounded-xl bg-x2-primary px-5 py-2.5 text-sm font-semibold text-white hover:bg-x2-primary-600">Lưu thay đổi</button>
            </div>
        </div>
    </div>
</x-filament-panels::page>
