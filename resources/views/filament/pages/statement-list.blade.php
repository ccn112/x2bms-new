<x-filament-panels::page>
    <div class="flex flex-wrap items-center justify-end gap-3">
        <button type="button" class="inline-flex items-center gap-2 rounded-lg bg-x2-primary px-4 py-2 text-sm font-semibold text-white shadow-sm hover:opacity-90">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
            Phát hành bảng kê
        </button>
        <button type="button" class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M22 2L11 13M22 2l-7 20-4-9-9-4 20-7z"/></svg>
            Gửi thông báo
        </button>
        <button type="button" class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M4 8v-2a2 2 0 012-2h12a2 2 0 012 2v2M7 14l5 5 5-5M12 19V7"/></svg>
            Xuất Excel
        </button>
    </div>

    <x-x2.kpi-row :cols="5">
        @foreach ($kpis as $kpi)
            <x-x2.kpi-card :label="$kpi['label']" :value="$kpi['value']" :accent="$kpi['accent']" />
        @endforeach
    </x-x2.kpi-row>

    <div class="grid grid-cols-1 gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:grid-cols-2 lg:grid-cols-6">
        @foreach (['Tòa nhà', 'Kỳ phí', 'Trạng thái', 'Kênh gửi', 'Nhân viên phụ trách'] as $f)
            <label class="block">
                <span class="mb-1 block text-xs font-medium text-slate-500">{{ $f }}</span>
                <select class="w-full rounded-lg border-slate-200 text-sm text-slate-600 focus:border-x2-primary focus:ring-x2-primary">
                    <option>{{ $f === 'Kỳ phí' ? '07/2026' : 'Tất cả' }}</option>
                </select>
            </label>
        @endforeach
        <label class="block">
            <span class="mb-1 block text-xs font-medium text-slate-500">&nbsp;</span>
            <div class="relative">
                <input type="text" placeholder="Tìm mã, căn hộ, cư dân..." class="w-full rounded-lg border-slate-200 pl-9 text-sm focus:border-x2-primary focus:ring-x2-primary" />
                <svg class="pointer-events-none absolute left-3 top-2.5 h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M11 18a7 7 0 100-14 7 7 0 000 14z"/></svg>
            </div>
        </label>
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[1100px] text-sm">
                <thead>
                    <tr class="border-b border-slate-100 text-left text-xs uppercase tracking-wide text-slate-400">
                        <th class="px-4 py-3 font-medium">Mã bảng kê</th>
                        <th class="px-4 py-3 font-medium">Căn hộ / Cư dân</th>
                        <th class="px-4 py-3 font-medium">Kỳ phí</th>
                        <th class="px-4 py-3 text-center font-medium">Số khoản phí</th>
                        <th class="px-4 py-3 text-right font-medium">Phải thu</th>
                        <th class="px-4 py-3 text-right font-medium">Đã thanh toán</th>
                        <th class="px-4 py-3 text-right font-medium">Còn nợ</th>
                        <th class="px-4 py-3 font-medium">Ngày phát hành</th>
                        <th class="px-4 py-3 font-medium">Hạn thanh toán</th>
                        <th class="px-4 py-3 font-medium">Trạng thái</th>
                        <th class="px-4 py-3 text-right font-medium">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @forelse ($statements as $r)
                        <tr class="hover:bg-slate-50/70">
                            <td class="whitespace-nowrap px-4 py-3 font-semibold text-x2-primary">{{ $r['code'] }}</td>
                            <td class="px-4 py-3">
                                <div class="font-medium text-slate-800">{{ $r['apartment'] }}</div>
                                <div class="text-xs text-slate-400">{{ $r['resident'] }}</div>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-slate-600">{{ $r['period'] }}</td>
                            <td class="px-4 py-3 text-center text-slate-600">{{ $r['lines'] }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-right font-medium text-slate-800">{{ $r['total'] }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-right text-x2-green">{{ $r['paid'] }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-right {{ $r['remaining_raw'] > 0 ? 'text-x2-red font-medium' : 'text-slate-400' }}">{{ $r['remaining'] }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-slate-600">{{ $r['issued_at'] }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-slate-600">{{ $r['due_at'] }}</td>
                            <td class="whitespace-nowrap px-4 py-3"><x-x2.status-badge :label="$r['status_label']" :tone="$r['status_tone']" /></td>
                            <td class="whitespace-nowrap px-4 py-3 text-right">
                                <a href="{{ url('/admin/statements/'.$r['id']) }}" class="rounded p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-600" title="Chi tiết bảng kê">
                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M12 8a2 2 0 100-4 2 2 0 000 4zm0 6a2 2 0 100-4 2 2 0 000 4zm0 6a2 2 0 100-4 2 2 0 000 4z"/></svg>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="11" class="px-4 py-12 text-center text-sm text-slate-400">Chưa có bảng kê trong kỳ hiện tại.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-slate-100 px-4 py-3">
            {{ $statements->links() }}
        </div>
    </div>
</x-filament-panels::page>
