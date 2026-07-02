<x-filament-panels::page>
    {{-- Sub-nav (Khoản thu area) + top actions --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="inline-flex rounded-lg border border-slate-200 bg-white p-1 text-sm">
            <span class="rounded-md bg-x2-primary px-3 py-1.5 font-semibold text-white">Biểu phí</span>
            <a href="{{ url('/admin/fees/cycles') }}" class="rounded-md px-3 py-1.5 font-medium text-slate-500 hover:text-slate-700">Chu kỳ phí &amp; đợt thu</a>
        </div>
        <div class="flex flex-wrap items-center gap-3">
        <button type="button" class="inline-flex items-center gap-2 rounded-lg bg-x2-primary px-4 py-2 text-sm font-semibold text-white shadow-sm hover:opacity-90">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
            Tạo biểu phí
        </button>
        <button type="button" class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5-5 5 5M12 5v12"/></svg>
            Nhập Excel
        </button>
        <button type="button" class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M4 8v-2a2 2 0 012-2h12a2 2 0 012 2v2M7 14l5 5 5-5M12 19V7"/></svg>
            Xuất cấu hình
        </button>
        </div>
    </div>

    <x-x2.kpi-row :cols="5">
        @foreach ($kpis as $kpi)
            <x-x2.kpi-card :label="$kpi['label']" :value="$kpi['value']" :accent="$kpi['accent']" />
        @endforeach
    </x-x2.kpi-row>

    {{-- Filters --}}
    <div class="grid grid-cols-1 gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:grid-cols-2 lg:grid-cols-6">
        @foreach (['Tòa nhà', 'Nhóm phí', 'Tần suất', 'Đối tượng áp dụng', 'Trạng thái'] as $f)
            <label class="block">
                <span class="mb-1 block text-xs font-medium text-slate-500">{{ $f }}</span>
                <select class="w-full rounded-lg border-slate-200 text-sm text-slate-600 focus:border-x2-primary focus:ring-x2-primary">
                    <option>Tất cả</option>
                </select>
            </label>
        @endforeach
        <label class="block">
            <span class="mb-1 block text-xs font-medium text-slate-500">&nbsp;</span>
            <div class="relative">
                <input type="text" placeholder="Tìm kiếm mã, tên biểu phí..." class="w-full rounded-lg border-slate-200 pl-9 text-sm focus:border-x2-primary focus:ring-x2-primary" />
                <svg class="pointer-events-none absolute left-3 top-2.5 h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M11 18a7 7 0 100-14 7 7 0 000 14z"/></svg>
            </div>
        </label>
    </div>

    {{-- Fee catalogue table --}}
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[1000px] text-sm">
                <thead>
                    <tr class="border-b border-slate-100 text-left text-xs uppercase tracking-wide text-slate-400">
                        <th class="px-4 py-3 font-medium">Mã biểu phí</th>
                        <th class="px-4 py-3 font-medium">Tên biểu phí</th>
                        <th class="px-4 py-3 font-medium">Nhóm phí</th>
                        <th class="px-4 py-3 font-medium">Đối tượng áp dụng</th>
                        <th class="px-4 py-3 font-medium">Công thức tính</th>
                        <th class="px-4 py-3 font-medium">Chu kỳ thu</th>
                        <th class="px-4 py-3 font-medium">VAT</th>
                        <th class="px-4 py-3 font-medium">Hiệu lực</th>
                        <th class="px-4 py-3 font-medium">Trạng thái</th>
                        <th class="px-4 py-3 font-medium text-right">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @forelse ($rows as $r)
                        <tr class="hover:bg-slate-50/70">
                            <td class="whitespace-nowrap px-4 py-3 font-semibold text-slate-800">{{ $r['code'] }}</td>
                            <td class="px-4 py-3 text-slate-700">{{ $r['name'] }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-slate-600">{{ $r['category'] }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-slate-600">{{ $r['applies_to'] }}</td>
                            <td class="px-4 py-3 text-slate-500">{{ $r['formula'] }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-slate-600">{{ $r['frequency'] }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-slate-600">{{ $r['vat'] }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-slate-600">{{ $r['effective'] }}</td>
                            <td class="whitespace-nowrap px-4 py-3">
                                <x-x2.status-badge :label="$r['status_label']" :tone="$r['status_tone']" />
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-right">
                                <button type="button" class="rounded p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-600">
                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M12 8a2 2 0 100-4 2 2 0 000 4zm0 6a2 2 0 100-4 2 2 0 000 4zm0 6a2 2 0 100-4 2 2 0 000 4z"/></svg>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="10" class="px-4 py-12 text-center text-sm text-slate-400">Chưa có biểu phí nào trong phạm vi hiện tại.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="flex items-center justify-between border-t border-slate-100 px-4 py-3 text-sm text-slate-500">
            <span>{{ count($rows) }} biểu phí</span>
        </div>
    </div>

    {{-- Notable rules gallery --}}
    @if (count($notable))
        <div>
            <h3 class="mb-3 text-base font-semibold text-slate-900">Quy tắc tính nổi bật</h3>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-5">
                @foreach ($notable as $n)
                    <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                        <span class="grid h-10 w-10 place-items-center rounded-lg bg-x2-blue/10 text-x2-blue">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7"><path stroke-linecap="round" stroke-linejoin="round" d="M9 7h6m-6 4h6m-6 4h4M5 3h14a2 2 0 012 2v14a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2z"/></svg>
                        </span>
                        <div class="mt-3 text-sm font-semibold text-slate-800">{{ $n['name'] }}</div>
                        <p class="mt-1 line-clamp-2 text-xs text-slate-500">{{ $n['formula'] }}</p>
                        <div class="mt-3 text-xs font-medium text-x2-primary">Xem chi tiết →</div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</x-filament-panels::page>
