<x-filament-panels::page>
    <div x-data="{ open: false, step: 1 }">
        {{-- Sub-nav + primary action --}}
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="inline-flex rounded-lg border border-slate-200 bg-white p-1 text-sm">
                <a href="{{ url('/admin/fees/catalog') }}" class="rounded-md px-3 py-1.5 font-medium text-slate-500 hover:text-slate-700">Biểu phí</a>
                <span class="rounded-md bg-x2-primary px-3 py-1.5 font-semibold text-white">Chu kỳ phí &amp; đợt thu</span>
            </div>
            <button type="button" @click="open = true" class="inline-flex items-center gap-2 rounded-lg bg-x2-primary px-4 py-2 text-sm font-semibold text-white shadow-sm hover:opacity-90">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                Tạo kỳ phí
            </button>
        </div>

        <x-x2.kpi-row :cols="4" class="mt-4">
            @foreach ($kpis as $kpi)
                <x-x2.kpi-card :label="$kpi['label']" :value="$kpi['value']" :accent="$kpi['accent']" />
            @endforeach
        </x-x2.kpi-row>

        {{-- Filters --}}
        <div class="mt-4 grid grid-cols-1 gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:grid-cols-3">
            @foreach ([['Tòa nhà', 'Tất cả'], ['Loại phí', 'Tất cả'], ['Kỳ thu', '07/2026']] as [$label, $val])
                <label class="block">
                    <span class="mb-1 block text-xs font-medium text-slate-500">{{ $label }}</span>
                    <select class="w-full rounded-lg border-slate-200 text-sm text-slate-600 focus:border-x2-primary focus:ring-x2-primary"><option>{{ $val }}</option></select>
                </label>
            @endforeach
        </div>

        {{-- Cycle table --}}
        <div class="mt-4 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[900px] text-sm">
                    <thead>
                        <tr class="border-b border-slate-100 text-left text-xs uppercase tracking-wide text-slate-400">
                            <th class="w-10 px-4 py-3"><input type="checkbox" class="rounded border-slate-300" /></th>
                            <th class="px-4 py-3 font-medium">Mã kỳ phí</th>
                            <th class="px-4 py-3 font-medium">Tên kỳ</th>
                            <th class="px-4 py-3 font-medium">Loại phí</th>
                            <th class="px-4 py-3 font-medium">Phạm vi áp dụng</th>
                            <th class="px-4 py-3 font-medium">Kỳ thu</th>
                            <th class="px-4 py-3 font-medium">Trạng thái</th>
                            <th class="px-4 py-3 text-right font-medium">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        @forelse ($rows as $r)
                            <tr class="hover:bg-slate-50/70">
                                <td class="px-4 py-3"><input type="checkbox" class="rounded border-slate-300" /></td>
                                <td class="whitespace-nowrap px-4 py-3 font-semibold text-x2-primary">{{ $r['code'] }}</td>
                                <td class="px-4 py-3 text-slate-700">{{ $r['name'] }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-slate-600">{{ $r['fee_category'] }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-slate-600">{{ $r['scope'] }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-slate-600">{{ $r['period'] }}</td>
                                <td class="whitespace-nowrap px-4 py-3"><x-x2.status-badge :label="$r['status_label']" :tone="$r['status_tone']" /></td>
                                <td class="whitespace-nowrap px-4 py-3 text-right">
                                    <button type="button" class="rounded p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-600">
                                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M12 8a2 2 0 100-4 2 2 0 000 4zm0 6a2 2 0 100-4 2 2 0 000 4zm0 6a2 2 0 100-4 2 2 0 000 4z"/></svg>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="px-4 py-12 text-center text-sm text-slate-400">Chưa có kỳ phí nào.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="flex flex-wrap items-center gap-2 border-t border-slate-100 px-4 py-3 text-sm">
                <span class="text-slate-500">0 dòng được chọn</span>
                <span class="mx-2 text-slate-300">|</span>
                <button class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-medium text-slate-600 hover:bg-slate-50">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Chốt kỳ đã chọn
                </button>
                <button class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-medium text-slate-600 hover:bg-slate-50">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M22 2L11 13M22 2l-7 20-4-9-9-4 20-7z"/></svg>
                    Phát hành bảng kê
                </button>
            </div>
        </div>

        {{-- ============================ SETUP DRAWER ============================ --}}
        <div x-cloak x-show="open" class="fixed inset-0 z-[100]" style="display:none">
            <div x-show="open" x-transition.opacity class="absolute inset-0 bg-slate-900/40" @click="open = false"></div>

            <div x-show="open"
                 x-transition:enter="transition ease-out duration-300" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0"
                 x-transition:leave="transition ease-in duration-200" x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full"
                 class="absolute right-0 top-0 flex h-full w-full max-w-4xl flex-col bg-white shadow-2xl">

                {{-- Drawer header --}}
                <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
                    <h2 class="text-lg font-semibold text-slate-900">Thiết lập kỳ phí</h2>
                    <div class="flex items-center gap-2">
                        <span class="grid h-8 w-8 place-items-center rounded-full text-slate-400 hover:bg-slate-100"><svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7"><path stroke-linecap="round" stroke-linejoin="round" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></span>
                        <button @click="open = false" class="grid h-8 w-8 place-items-center rounded-full text-slate-400 hover:bg-slate-100"><svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg></button>
                    </div>
                </div>

                {{-- Step indicator --}}
                <div class="flex items-center gap-1 overflow-x-auto border-b border-slate-100 px-6 py-3 text-sm">
                    @foreach (['Thông tin kỳ phí', 'Phạm vi áp dụng', 'Nguồn dữ liệu & quy tắc tính', 'Lịch chạy', 'Xem trước kết quả'] as $i => $stepLabel)
                        <button @click="step = {{ $i + 1 }}; $refs['sec{{ $i + 1 }}']?.scrollIntoView({behavior:'smooth', block:'start'})"
                                class="flex shrink-0 items-center gap-2 whitespace-nowrap rounded-full px-2.5 py-1"
                                :class="step === {{ $i + 1 }} ? 'text-x2-primary font-semibold' : 'text-slate-400'">
                            <span class="grid h-5 w-5 place-items-center rounded-full text-[11px]"
                                  :class="step === {{ $i + 1 }} ? 'bg-x2-primary text-white' : 'bg-slate-100 text-slate-500'">{{ $i + 1 }}</span>
                            {{ $stepLabel }}
                        </button>
                        @if (! $loop->last)<span class="text-slate-200">·</span>@endif
                    @endforeach
                </div>

                {{-- Drawer body: form + summary --}}
                <div class="grid flex-1 grid-cols-1 gap-6 overflow-y-auto p-6 lg:grid-cols-[1fr_300px]">
                    {{-- Left: form --}}
                    <div class="space-y-8">
                        {{-- 1 --}}
                        <section x-ref="sec1">
                            <h3 class="mb-4 flex items-center gap-2 text-base font-semibold text-slate-900"><span class="grid h-6 w-6 place-items-center rounded-full bg-x2-primary/10 text-xs text-x2-primary">1</span> Thông tin kỳ phí</h3>
                            <div class="grid grid-cols-2 gap-4">
                                <label class="block">
                                    <span class="mb-1 block text-xs font-medium text-slate-500">Mã kỳ phí <span class="text-x2-red">*</span></span>
                                    <input type="text" value="{{ $draft['code'] }}" class="w-full rounded-lg border-slate-200 bg-slate-50 text-sm" />
                                    <span class="mt-1 block text-[11px] text-slate-400">Tự động tạo theo quy tắc</span>
                                </label>
                                <label class="block">
                                    <span class="mb-1 block text-xs font-medium text-slate-500">Tên kỳ <span class="text-x2-red">*</span></span>
                                    <input type="text" value="{{ $draft['name'] }}" class="w-full rounded-lg border-slate-200 text-sm" />
                                </label>
                            </div>
                            <div class="mt-4 grid grid-cols-3 gap-4">
                                <label class="block"><span class="mb-1 block text-xs font-medium text-slate-500">Kỳ thu <span class="text-x2-red">*</span></span><select class="w-full rounded-lg border-slate-200 text-sm"><option>{{ $draft['period'] }}</option></select></label>
                                <label class="block"><span class="mb-1 block text-xs font-medium text-slate-500">Loại phí <span class="text-x2-red">*</span></span><select class="w-full rounded-lg border-slate-200 text-sm"><option>{{ $draft['fee_type'] }}</option></select></label>
                                <label class="block"><span class="mb-1 block text-xs font-medium text-slate-500">Đơn vị tính</span><select class="w-full rounded-lg border-slate-200 text-sm"><option>VND</option></select></label>
                            </div>
                            <label class="mt-4 block"><span class="mb-1 block text-xs font-medium text-slate-500">Ghi chú</span><textarea rows="2" class="w-full rounded-lg border-slate-200 text-sm">Phí quản lý định kỳ tháng 07/2026</textarea></label>
                        </section>

                        {{-- 2 --}}
                        <section x-ref="sec2">
                            <h3 class="mb-4 flex items-center gap-2 text-base font-semibold text-slate-900"><span class="grid h-6 w-6 place-items-center rounded-full bg-x2-primary/10 text-xs text-x2-primary">2</span> Phạm vi áp dụng</h3>
                            <div class="grid grid-cols-3 gap-4">
                                <label class="block"><span class="mb-1 block text-xs font-medium text-slate-500">Tòa nhà <span class="text-x2-red">*</span></span><select class="w-full rounded-lg border-slate-200 text-sm"><option>Sunshine Garden</option></select></label>
                                <label class="block"><span class="mb-1 block text-xs font-medium text-slate-500">Block</span><select class="w-full rounded-lg border-slate-200 text-sm"><option>Tất cả</option></select></label>
                                <label class="block"><span class="mb-1 block text-xs font-medium text-slate-500">Tầng</span><select class="w-full rounded-lg border-slate-200 text-sm"><option>Tất cả</option></select></label>
                            </div>
                            <div class="mt-4 grid grid-cols-2 gap-4">
                                <label class="block"><span class="mb-1 block text-xs font-medium text-slate-500">Loại căn</span><select class="w-full rounded-lg border-slate-200 text-sm"><option>Tất cả</option></select></label>
                                <label class="block"><span class="mb-1 block text-xs font-medium text-slate-500">Số căn dự kiến</span><input type="text" value="{{ $draft['units'] }}" class="w-full rounded-lg border-slate-200 text-sm" /><span class="mt-1 block text-[11px] text-slate-400">Ước tính theo phạm vi lọc</span></label>
                            </div>
                        </section>

                        {{-- 3 --}}
                        <section x-ref="sec3">
                            <h3 class="mb-4 flex items-center gap-2 text-base font-semibold text-slate-900"><span class="grid h-6 w-6 place-items-center rounded-full bg-x2-primary/10 text-xs text-x2-primary">3</span> Nguồn dữ liệu &amp; quy tắc tính</h3>
                            <div class="grid grid-cols-2 gap-4">
                                <label class="block">
                                    <span class="mb-1 flex items-center justify-between text-xs font-medium text-slate-500">Biểu phí áp dụng <span class="text-x2-red">*</span> <a class="text-x2-primary">Xem chi tiết</a></span>
                                    <select class="w-full rounded-lg border-slate-200 text-sm"><option>Biểu phí quản lý 2026 (V1.0)</option></select>
                                </label>
                                <label class="block"><span class="mb-1 block text-xs font-medium text-slate-500">Chỉ số đồng hồ</span><select class="w-full rounded-lg border-slate-200 text-sm"><option>Không sử dụng</option></select></label>
                            </div>
                            <label class="mt-4 block">
                                <span class="mb-1 flex items-center justify-between text-xs font-medium text-slate-500">Công thức phân bổ <span class="text-x2-red">*</span></span>
                                <div class="flex gap-2">
                                    <input type="text" value="Diện tích thông thủy × Đơn giá theo biểu phí" class="w-full rounded-lg border-slate-200 text-sm" />
                                    <button class="shrink-0 rounded-lg border border-slate-200 px-3 text-sm font-medium text-x2-primary hover:bg-slate-50">Sửa</button>
                                </div>
                            </label>
                            <div class="mt-4 grid grid-cols-2 gap-4">
                                <label class="block"><span class="mb-1 block text-xs font-medium text-slate-500">Làm tròn</span><select class="w-full rounded-lg border-slate-200 text-sm"><option>Làm tròn đến đơn vị nghìn (1.000 VND)</option></select></label>
                                <label class="block"><span class="mb-1 block text-xs font-medium text-slate-500">VAT</span><select class="w-full rounded-lg border-slate-200 text-sm"><option>Không chịu VAT (0%)</option></select></label>
                            </div>
                        </section>

                        {{-- 4 --}}
                        <section x-ref="sec4">
                            <h3 class="mb-4 flex items-center gap-2 text-base font-semibold text-slate-900"><span class="grid h-6 w-6 place-items-center rounded-full bg-x2-primary/10 text-xs text-x2-primary">4</span> Lịch chạy</h3>
                            <div class="grid grid-cols-4 gap-3">
                                @foreach ([['Ngày chốt dữ liệu', '30/06/2026'], ['Ngày chạy tính phí', '01/07/2026'], ['Ngày phát hành bảng kê', '02/07/2026'], ['Hạn thanh toán', '20/07/2026']] as [$l, $v])
                                    <label class="block"><span class="mb-1 block text-xs font-medium text-slate-500">{{ $l }} <span class="text-x2-red">*</span></span>
                                        <div class="relative"><input type="text" value="{{ $v }}" class="w-full rounded-lg border-slate-200 pr-8 text-sm" /><svg class="pointer-events-none absolute right-2 top-2.5 h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg></div>
                                    </label>
                                @endforeach
                            </div>
                        </section>

                        {{-- 5 --}}
                        <section x-ref="sec5">
                            <h3 class="mb-4 flex items-center gap-2 text-base font-semibold text-slate-900"><span class="grid h-6 w-6 place-items-center rounded-full bg-x2-primary/10 text-xs text-x2-primary">5</span> Xem trước kết quả</h3>
                            <div class="grid grid-cols-4 gap-3">
                                <div class="rounded-xl bg-x2-green/10 p-3"><div class="text-xs text-slate-500">Số căn áp dụng</div><div class="mt-1 text-lg font-bold text-slate-800">{{ $draft['units'] }} căn</div></div>
                                <div class="rounded-xl bg-x2-blue/10 p-3"><div class="text-xs text-slate-500">Số dòng phí dự kiến</div><div class="mt-1 text-lg font-bold text-slate-800">{{ $draft['units'] }} dòng</div></div>
                                <div class="rounded-xl bg-x2-teal/10 p-3"><div class="text-xs text-slate-500">Tổng phải thu dự kiến</div><div class="mt-1 text-base font-bold text-slate-800">{{ $draft['expected'] }} VND</div></div>
                                <div class="rounded-xl bg-x2-amber/10 p-3"><div class="text-xs text-slate-500">Cảnh báo dữ liệu</div><div class="mt-1 text-lg font-bold text-slate-800">0 cảnh báo</div></div>
                            </div>
                            <p class="mt-2 text-[11px] text-slate-400">Số liệu dự kiến được tính theo dữ liệu hiện tại và biểu phí đã chọn.</p>
                        </section>
                    </div>

                    {{-- Right: summary --}}
                    <aside class="space-y-4">
                        <div class="rounded-2xl border border-slate-200 bg-slate-50/60 p-4">
                            <h4 class="mb-3 text-sm font-semibold text-slate-900">Tóm tắt kỳ phí</h4>
                            <dl class="space-y-2 text-sm">
                                <div><dt class="text-[11px] text-slate-400">Mã kỳ</dt><dd class="font-medium text-slate-700">{{ $draft['code'] }}</dd></div>
                                <div><dt class="text-[11px] text-slate-400">Tên kỳ</dt><dd class="font-medium text-slate-700">{{ $draft['name'] }}</dd></div>
                                <div><dt class="text-[11px] text-slate-400">Kỳ thu</dt><dd class="text-slate-700">{{ $draft['period'] }}</dd></div>
                                <div><dt class="text-[11px] text-slate-400">Loại phí</dt><dd class="text-slate-700">{{ $draft['fee_type'] }}</dd></div>
                                <div>
                                    <dt class="text-[11px] text-slate-400">Phạm vi áp dụng</dt>
                                    <dd class="text-slate-700">Sunshine Garden</dd>
                                    <ul class="mt-1 space-y-0.5 pl-3 text-xs text-slate-500">
                                        <li>• Block: Tất cả</li><li>• Tầng: Tất cả</li><li>• Loại căn: Tất cả</li><li>• Số căn dự kiến: {{ $draft['units'] }} căn</li>
                                    </ul>
                                </div>
                                <div><dt class="text-[11px] text-slate-400">Biểu phí</dt><dd class="text-slate-700">Biểu phí quản lý 2026 (V1.0)</dd></div>
                                <div><dt class="text-[11px] text-slate-400">Công thức tính</dt><dd class="text-slate-700">Diện tích thông thủy × Đơn giá</dd></div>
                                <div><dt class="text-[11px] text-slate-400">Tổng phải thu dự kiến</dt><dd class="font-semibold text-x2-primary">{{ $draft['expected'] }} VND</dd></div>
                            </dl>
                            <div class="mt-3 flex items-center gap-2 border-t border-slate-200 pt-3">
                                <span class="grid h-8 w-8 place-items-center rounded-full bg-slate-200 text-xs font-semibold text-slate-500">{{ mb_substr($draft['creator'], 0, 1) }}</span>
                                <div><div class="text-sm font-medium text-slate-700">{{ $draft['creator'] }}</div><div class="text-[11px] text-slate-400">Trưởng BQL · 02/07/2026 09:42</div></div>
                            </div>
                        </div>

                        <div class="rounded-2xl border border-blue-100 bg-blue-50/60 p-4 text-xs text-slate-600">
                            <div class="mb-1 flex items-center gap-1.5 font-semibold text-x2-blue"><svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg> Hướng dẫn</div>
                            <ul class="space-y-1 pl-1">
                                <li>• Thiết lập đầy đủ thông tin để hệ thống tính phí chính xác.</li>
                                <li>• Có thể Lưu nháp để hoàn thiện sau.</li>
                                <li>• Chạy thử để kiểm tra số liệu trước khi tạo kỳ phí chính thức.</li>
                            </ul>
                        </div>

                        <div class="rounded-2xl border border-slate-200 p-4">
                            <div class="mb-2 text-xs font-semibold text-x2-red">Kiểm tra trước khi tạo</div>
                            <ul class="space-y-1.5 text-xs">
                                @foreach (['Thông tin bắt buộc', 'Phạm vi áp dụng', 'Biểu phí', 'Lịch chạy'] as $chk)
                                    <li class="flex items-center justify-between"><span class="text-slate-600">{{ $chk }}</span><span class="text-x2-green">✓</span></li>
                                @endforeach
                                <li class="flex items-center justify-between"><span class="text-slate-600">Cảnh báo dữ liệu</span><span class="text-slate-400">Không có</span></li>
                            </ul>
                        </div>
                    </aside>
                </div>

                {{-- Footer --}}
                <div class="flex items-center justify-end gap-3 border-t border-slate-100 px-6 py-4">
                    <button @click="open = false" class="rounded-lg border border-slate-200 px-5 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50">Hủy</button>
                    <button class="inline-flex items-center gap-2 rounded-lg border border-slate-200 px-5 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"><svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg> Lưu nháp</button>
                    <button class="inline-flex items-center gap-2 rounded-lg border border-slate-200 px-5 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"><svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7"><path stroke-linecap="round" stroke-linejoin="round" d="M14.752 11.168l-5.197-3.03A1 1 0 008 9.03v5.94a1 1 0 001.555.832l5.197-3.03a1 1 0 000-1.664z"/></svg> Chạy thử</button>
                    <button wire:click="createDraftCycle" @click="open = false" class="rounded-lg bg-x2-primary px-6 py-2 text-sm font-semibold text-white shadow-sm hover:opacity-90">Tạo kỳ phí</button>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>
