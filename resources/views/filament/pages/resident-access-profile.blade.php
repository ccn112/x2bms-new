<x-filament-panels::page>
    {{-- Resident picker --}}
    <div class="flex flex-wrap items-center gap-3">
        <label class="text-sm font-medium text-slate-500">Cư dân:</label>
        <select wire:model.live="residentId" class="min-w-[260px] rounded-lg border-slate-200 text-sm focus:border-x2-primary focus:ring-x2-primary">
            @foreach ($residentOptions as $id => $name)
                <option value="{{ $id }}">{{ $name }}</option>
            @endforeach
        </select>
    </div>

    @if (! $resident)
        <div class="rounded-2xl border border-dashed border-slate-200 bg-white py-16 text-center text-sm text-slate-400 dark:border-white/10 dark:bg-gray-900">Chọn một cư dân để xem hồ sơ truy cập.</div>
    @else
        {{-- Header + summary --}}
        <div class="grid grid-cols-1 gap-4 xl:grid-cols-[1fr_auto]">
            <div class="flex items-center gap-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-gray-900">
                <span class="grid h-16 w-16 shrink-0 place-items-center rounded-full bg-x2-navy text-xl font-bold text-white">
                    {{ \Illuminate\Support\Str::of($resident->full_name)->explode(' ')->map(fn ($w) => mb_substr($w, 0, 1))->take(2)->implode('') }}
                </span>
                <div class="min-w-0">
                    <div class="flex items-center gap-2">
                        <h2 class="text-lg font-bold text-slate-900 dark:text-white">{{ $resident->full_name }}</h2>
                        <x-x2.status-badge :label="$resident->status === 'active' ? 'Hoạt động' : ($resident->status ?? '—')" :tone="$resident->status === 'active' ? 'green' : 'slate'" />
                    </div>
                    <div class="mt-1 grid grid-cols-1 gap-x-8 gap-y-0.5 text-sm text-slate-500 sm:grid-cols-2">
                        <span>Căn hộ: <span class="font-medium text-slate-700 dark:text-slate-200">{{ $apartment?->code ?? '—' }}</span>@if ($apartment?->building) · {{ $apartment->building->name }}@endif</span>
                        <span>Điện thoại: <span class="font-medium text-slate-700 dark:text-slate-200">{{ $resident->phone ?? '—' }}</span></span>
                        <span>Email: <span class="font-medium text-slate-700 dark:text-slate-200">{{ $resident->email ?? '—' }}</span></span>
                        <span>CCCD: <span class="font-medium text-slate-700 dark:text-slate-200">{{ $resident->id_no ?? '—' }}</span></span>
                    </div>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-3 lg:grid-cols-4 xl:w-[560px]">
                @foreach ($summary as $s)
                    <x-x2.kpi-card :label="$s['label']" :value="$s['value']" :sub="$s['sub']" :accent="$s['accent']" />
                @endforeach
            </div>
        </div>

        <div class="grid grid-cols-1 gap-6 xl:grid-cols-[1fr_360px]">
            {{-- Left: vehicles + cards --}}
            <div class="space-y-6">
                {{-- Vehicles --}}
                <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-white/10 dark:bg-gray-900">
                    <div class="flex items-center justify-between border-b border-slate-100 px-5 py-3 dark:border-white/10">
                        <h3 class="text-base font-semibold text-slate-900 dark:text-white">Xe liên kết ({{ count($vehicles) }})</h3>
                        <a href="{{ url('/admin/access/vehicle-requests') }}" class="text-xs font-medium text-x2-primary hover:underline">Quản lý →</a>
                    </div>
                    <table class="w-full text-sm">
                        <thead><tr class="border-b border-slate-100 text-left text-xs uppercase tracking-wide text-slate-400 dark:border-white/10">
                            <th class="px-5 py-2.5 font-medium">Biển số</th><th class="px-5 py-2.5 font-medium">Loại</th><th class="px-5 py-2.5 font-medium">Khu đỗ</th><th class="px-5 py-2.5 font-medium">Trạng thái</th>
                        </tr></thead>
                        <tbody class="divide-y divide-slate-50 dark:divide-white/5">
                            @forelse ($vehicles as $v)
                                <tr>
                                    <td class="px-5 py-3 font-medium text-slate-800 dark:text-slate-100">{{ $v['plate_no'] }}</td>
                                    <td class="px-5 py-3 text-slate-500">{{ $v['type'] }}</td>
                                    <td class="px-5 py-3 text-slate-500">{{ $v['parking'] ?: '—' }}</td>
                                    <td class="px-5 py-3"><x-x2.status-badge :label="$v['status'] === 'active' ? 'Đang hoạt động' : $v['status']" :tone="$v['status'] === 'active' ? 'green' : 'red'" /></td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="px-5 py-8 text-center text-sm text-slate-400">Chưa có xe liên kết.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Cards --}}
                <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-white/10 dark:bg-gray-900">
                    <div class="flex items-center justify-between border-b border-slate-100 px-5 py-3 dark:border-white/10">
                        <h3 class="text-base font-semibold text-slate-900 dark:text-white">Thẻ truy cập ({{ count($cards) }})</h3>
                        <a href="{{ url('/admin/access/cards') }}" class="text-xs font-medium text-x2-primary hover:underline">Cấp thẻ mới →</a>
                    </div>
                    <table class="w-full text-sm">
                        <thead><tr class="border-b border-slate-100 text-left text-xs uppercase tracking-wide text-slate-400 dark:border-white/10">
                            <th class="px-5 py-2.5 font-medium">Mã thẻ</th><th class="px-5 py-2.5 font-medium">Loại</th><th class="px-5 py-2.5 font-medium">Hiệu lực</th><th class="px-5 py-2.5 font-medium">Trạng thái</th>
                        </tr></thead>
                        <tbody class="divide-y divide-slate-50 dark:divide-white/5">
                            @forelse ($cards as $c)
                                <tr>
                                    <td class="px-5 py-3 font-medium text-slate-800 dark:text-slate-100">{{ $c['card_no'] }}@if ($c['is_biometric']) <span class="ml-1 rounded bg-x2-teal/10 px-1 text-[10px] font-semibold text-x2-teal">ST</span>@endif</td>
                                    <td class="px-5 py-3 text-slate-500">{{ $c['type'] }}</td>
                                    <td class="px-5 py-3 text-slate-500">{{ $c['valid_to'] ?? 'Không thời hạn' }}</td>
                                    <td class="px-5 py-3"><x-x2.status-badge :label="$c['status'] === 'active' ? 'Đang hoạt động' : 'Đã thu hồi'" :tone="$c['status'] === 'active' ? 'green' : 'red'" /></td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="px-5 py-8 text-center text-sm text-slate-400">Chưa có thẻ truy cập.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Right: activity + warnings --}}
            <div class="space-y-4">
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-gray-900">
                    <h3 class="text-base font-semibold text-slate-900 dark:text-white">Hoạt động gần đây</h3>
                    <ul class="mt-4 space-y-3">
                        @forelse ($logs as $l)
                            <li class="flex items-center gap-3 text-sm">
                                <x-x2.status-badge :label="$l['direction'][0]" :tone="$l['direction'][1]" />
                                <div class="min-w-0 flex-1">
                                    <div class="truncate text-slate-700 dark:text-slate-200">{{ $l['gate'] }}</div>
                                    <div class="text-xs text-slate-400">{{ $l['method'] }}</div>
                                </div>
                                <span class="shrink-0 text-xs text-slate-400">{{ $l['time'] }}</span>
                            </li>
                        @empty
                            <li class="py-6 text-center text-sm text-slate-400">Chưa có hoạt động ra/vào.</li>
                        @endforelse
                    </ul>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-gray-900">
                    <h3 class="text-base font-semibold text-slate-900 dark:text-white">Cảnh báo & lưu ý</h3>
                    <ul class="mt-3 space-y-2.5">
                        @forelse ($warnings as $w)
                            <li class="flex items-start gap-2.5 rounded-lg border border-amber-100 bg-amber-50 p-2.5 text-sm dark:border-amber-500/20 dark:bg-amber-500/10">
                                <svg class="mt-0.5 h-4 w-4 shrink-0 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z"/></svg>
                                <div><div class="font-medium text-amber-700 dark:text-amber-300">{{ $w['text'] }}</div><div class="text-xs text-amber-600 dark:text-amber-400">{{ $w['detail'] }}</div></div>
                            </li>
                        @empty
                            <li class="flex items-center gap-2 rounded-lg border border-green-100 bg-green-50 p-2.5 text-sm text-green-700 dark:border-green-500/20 dark:bg-green-500/10">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m5 13 4 4L19 7"/></svg>
                                Hồ sơ truy cập hợp lệ, không có cảnh báo.
                            </li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>
    @endif
</x-filament-panels::page>
