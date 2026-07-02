<x-filament-panels::page>
    <div class="flex flex-wrap items-center justify-end gap-3">
        <button type="button" class="inline-flex items-center gap-2 rounded-lg bg-x2-primary px-4 py-2 text-sm font-semibold text-white shadow-sm hover:opacity-90">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
            Tạo nhắc nợ
        </button>
        <button type="button" class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M18 8a3 3 0 10-6 0M9 20H4v-2a4 4 0 014-4h.5M15 14a4 4 0 014 4v2h-5m1-8a3 3 0 100-6"/></svg>
            Phân công thu hồi
        </button>
        <button type="button" class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M4 8v-2a2 2 0 012-2h12a2 2 0 012 2v2M7 14l5 5 5-5M12 19V7"/></svg>
            Xuất báo cáo
        </button>
    </div>

    <x-x2.kpi-row :cols="5">
        @foreach ($kpis as $kpi)
            <x-x2.kpi-card :label="$kpi['label']" :value="$kpi['value']" :accent="$kpi['accent']" />
        @endforeach
    </x-x2.kpi-row>

    <div class="grid grid-cols-1 gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:grid-cols-2 lg:grid-cols-6">
        @foreach (['Tòa nhà', 'Block', 'Tuổi nợ', 'Loại phí', 'Trạng thái thu hồi', 'Nhân viên phụ trách'] as $f)
            <label class="block">
                <span class="mb-1 block text-xs font-medium text-slate-500">{{ $f }}</span>
                <select class="w-full rounded-lg border-slate-200 text-sm text-slate-600 focus:border-x2-primary focus:ring-x2-primary">
                    <option>Tất cả</option>
                </select>
            </label>
        @endforeach
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[1100px] text-sm">
                <thead>
                    <tr class="border-b border-slate-100 text-left text-xs uppercase tracking-wide text-slate-400">
                        <th class="px-4 py-3 font-medium">Mã công nợ</th>
                        <th class="px-4 py-3 font-medium">Căn hộ / Cư dân</th>
                        <th class="px-4 py-3 font-medium">Kỳ gần nhất</th>
                        <th class="px-4 py-3 text-right font-medium">Tổng nợ</th>
                        <th class="px-4 py-3 text-right font-medium">0-30</th>
                        <th class="px-4 py-3 text-right font-medium">31-60</th>
                        <th class="px-4 py-3 text-right font-medium">61-90</th>
                        <th class="px-4 py-3 text-right font-medium">&gt;90</th>
                        <th class="px-4 py-3 font-medium">Mức rủi ro</th>
                        <th class="px-4 py-3 font-medium">Người phụ trách</th>
                        <th class="px-4 py-3 font-medium">Trạng thái thu hồi</th>
                        <th class="px-4 py-3 text-right font-medium">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @forelse ($rows as $r)
                        <tr class="hover:bg-slate-50/70">
                            <td class="whitespace-nowrap px-4 py-3 font-semibold text-x2-primary">{{ $r['code'] }}</td>
                            <td class="px-4 py-3">
                                <div class="font-medium text-slate-800">{{ $r['apartment'] }}</div>
                                <div class="text-xs text-slate-400">{{ $r['resident'] }}</div>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-slate-600">{{ $r['last_period'] }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-right font-semibold text-slate-800">{{ $r['amount'] }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-right text-slate-600">{{ $r['b0_30'] }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-right text-slate-600">{{ $r['b31_60'] }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-right text-slate-600">{{ $r['b61_90'] }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-right font-medium text-x2-red">{{ $r['bover90'] }}</td>
                            <td class="whitespace-nowrap px-4 py-3"><x-x2.status-badge :label="$r['risk_label']" :tone="$r['risk_tone']" /></td>
                            <td class="whitespace-nowrap px-4 py-3 text-slate-600">{{ $r['assignee'] }}</td>
                            <td class="whitespace-nowrap px-4 py-3"><x-x2.status-badge :label="$r['rec_label']" :tone="$r['rec_tone']" /></td>
                            <td class="whitespace-nowrap px-4 py-3 text-right">
                                <a href="{{ url('/admin/debts/'.$r['id']) }}" class="rounded p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-600" title="Xem sổ công nợ">
                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M12 8a2 2 0 100-4 2 2 0 000 4zm0 6a2 2 0 100-4 2 2 0 000 4zm0 6a2 2 0 100-4 2 2 0 000 4z"/></svg>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="12" class="px-4 py-12 text-center text-sm text-slate-400">Không có công nợ trong phạm vi hiện tại.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="flex flex-wrap items-center gap-2 border-t border-slate-100 px-4 py-3 text-sm">
            <span class="text-slate-500">Đã chọn 0 / {{ count($rows) }}</span>
            <span class="mx-2 text-slate-300">|</span>
            <button class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-medium text-slate-600 hover:bg-slate-50">Gửi nhắc nợ</button>
            <button class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-medium text-slate-600 hover:bg-slate-50">Giao xử lý</button>
            <button class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-medium text-x2-red hover:bg-red-50">Đề nghị khóa tiện ích</button>
            <button class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-medium text-slate-600 hover:bg-slate-50">Xuất danh sách</button>
        </div>
    </div>
</x-filament-panels::page>
