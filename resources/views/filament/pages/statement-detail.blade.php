<x-filament-panels::page>
    <div class="grid grid-cols-1 gap-6 xl:grid-cols-[320px_1fr_320px]">
        {{-- Left: apartment/resident + related period + payment status --}}
        <div class="space-y-6">
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h3 class="mb-3 text-sm font-semibold text-slate-900">Thông tin căn hộ &amp; cư dân</h3>
                <div class="flex items-center gap-3">
                    <span class="grid h-12 w-12 place-items-center rounded-full bg-slate-100 font-semibold text-slate-500">{{ mb_substr($resident, 0, 1) }}</span>
                    <div>
                        <div class="font-semibold text-slate-800">{{ $resident }}</div>
                        <span class="rounded-full bg-blue-50 px-2 py-0.5 text-[11px] font-medium text-x2-blue">Cư dân chính</span>
                    </div>
                </div>
                <div class="mt-4 rounded-xl bg-slate-50 p-3 text-sm">
                    <div class="font-medium text-slate-800">{{ $apt?->code ?? '—' }} @if($apt?->building) – {{ $apt->building->name }} @endif</div>
                    <div class="text-xs text-slate-400">Diện tích: {{ $apt?->area_sqm ?? '—' }} m² · {{ $apt?->type ?? '' }}</div>
                </div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h3 class="mb-3 text-sm font-semibold text-slate-900">Kỳ phí liên quan</h3>
                <dl class="space-y-2 text-sm">
                    <div class="flex justify-between"><dt class="text-slate-500">Kỳ phí</dt><dd class="font-medium text-slate-700">{{ $period?->label ?? '—' }}</dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500">Mã bảng kê</dt><dd class="font-medium text-slate-700">{{ $s->code ?? '—' }}</dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500">Ngày phát hành</dt><dd class="text-slate-700">{{ $s->published_at?->format('d/m/Y') ?? '—' }}</dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500">Hạn thanh toán</dt><dd class="font-medium text-x2-red">{{ $due }}</dd></div>
                </dl>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="mb-3 flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-slate-900">Trạng thái thanh toán</h3>
                    <x-x2.status-badge :label="$statusLabel" :tone="$statusTone" />
                </div>
                <dl class="space-y-2 text-sm">
                    <div class="flex justify-between"><dt class="text-slate-500">Tổng phải thu</dt><dd class="font-semibold text-slate-800">{{ $total }} đ</dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500">Đã thanh toán</dt><dd class="text-x2-green">{{ $paid }} đ</dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500">Còn nợ</dt><dd class="font-semibold {{ $remainingRaw > 0 ? 'text-x2-red' : 'text-slate-400' }}">{{ $remaining }} đ</dd></div>
                </dl>
            </div>
        </div>

        {{-- Middle: line breakdown + timeline --}}
        <div class="space-y-6">
            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-100 px-5 py-3"><h3 class="text-base font-semibold text-slate-900">Chi tiết bảng kê</h3></div>
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[520px] text-sm">
                        <thead>
                            <tr class="border-b border-slate-100 text-left text-xs uppercase tracking-wide text-slate-400">
                                <th class="px-4 py-2.5 font-medium">STT</th>
                                <th class="px-4 py-2.5 font-medium">Khoản mục phí</th>
                                <th class="px-4 py-2.5 text-right font-medium">Chỉ số / SL</th>
                                <th class="px-4 py-2.5 text-right font-medium">Đơn giá</th>
                                <th class="px-4 py-2.5 text-right font-medium">Thành tiền</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            @forelse ($lines as $l)
                                <tr>
                                    <td class="px-4 py-3 text-slate-400">{{ $l['no'] }}</td>
                                    <td class="px-4 py-3 text-slate-700">{{ $l['name'] }}</td>
                                    <td class="px-4 py-3 text-right text-slate-500">{{ $l['qty'] }}</td>
                                    <td class="px-4 py-3 text-right text-slate-500">{{ $l['unit_price'] }}</td>
                                    <td class="px-4 py-3 text-right font-medium text-slate-800">{{ $l['amount'] }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="px-4 py-8 text-center text-sm text-slate-400">Chưa có dòng phí.</td></tr>
                            @endforelse
                        </tbody>
                        <tfoot class="text-sm">
                            <tr class="border-t border-slate-100"><td colspan="4" class="px-4 py-2 text-right text-slate-500">Tổng trước VAT</td><td class="px-4 py-2 text-right text-slate-700">{{ $preVat }}</td></tr>
                            <tr><td colspan="4" class="px-4 py-2 text-right text-slate-500">VAT (8%)</td><td class="px-4 py-2 text-right text-slate-700">{{ $vat }}</td></tr>
                            <tr class="border-t border-slate-200 bg-slate-50/60"><td colspan="4" class="px-4 py-3 text-right font-semibold text-slate-800">TỔNG CỘNG</td><td class="px-4 py-3 text-right text-base font-bold text-x2-primary">{{ $total }}</td></tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h3 class="mb-4 text-base font-semibold text-slate-900">Lịch sử phát hành &amp; nhắc nợ</h3>
                <ul class="space-y-4">
                    @forelse ($timeline as $t)
                        <li class="flex gap-3">
                            <span class="mt-0.5 grid h-7 w-7 shrink-0 place-items-center rounded-full bg-slate-100 text-slate-500">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            </span>
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center justify-between gap-2">
                                    <span class="font-medium text-slate-800">{{ $t['title'] }}</span>
                                    <span class="whitespace-nowrap text-xs text-slate-400">{{ $t['at'] }}</span>
                                </div>
                                <p class="text-sm text-slate-500">{{ $t['desc'] }}</p>
                                <p class="text-xs text-slate-400">{{ $t['by'] }}</p>
                            </div>
                        </li>
                    @empty
                        <li class="py-6 text-center text-sm text-slate-400">Chưa có lịch sử.</li>
                    @endforelse
                </ul>
            </div>
        </div>

        {{-- Right: actions + checklist --}}
        <div class="space-y-6">
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h3 class="mb-3 text-sm font-semibold text-slate-900">Thao tác</h3>
                <div class="space-y-2">
                    <button class="w-full rounded-lg bg-x2-primary px-4 py-2 text-sm font-semibold text-white hover:opacity-90">Phát hành</button>
                    <button class="w-full rounded-lg border border-slate-200 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Điều chỉnh</button>
                    <button class="w-full rounded-lg border border-slate-200 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Gửi lại</button>
                    <button class="w-full rounded-lg border border-slate-200 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">In PDF</button>
                </div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h3 class="mb-3 text-sm font-semibold text-slate-900">Checklist xử lý</h3>
                <ul class="space-y-2 text-sm">
                    @foreach ([['Đã kiểm tra chỉ số & công nợ', true], ['Đã đối soát khoản thu', true], ['Đã phát hành bảng kê', $published], ['Đã gửi thông báo cư dân', (bool) $s->viewed_at], ['Xác nhận thanh toán', $remainingRaw <= 0]] as [$label, $done])
                        <li class="flex items-center gap-2">
                            <span class="grid h-5 w-5 place-items-center rounded-full {{ $done ? 'bg-x2-green/15 text-x2-green' : 'bg-slate-100 text-slate-300' }}">
                                <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            </span>
                            <span class="{{ $done ? 'text-slate-700' : 'text-slate-400' }}">{{ $label }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>

            <a href="{{ url('/admin/statements') }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-slate-500 hover:text-x2-primary">← Quay lại danh sách bảng kê</a>
        </div>
    </div>
</x-filament-panels::page>
