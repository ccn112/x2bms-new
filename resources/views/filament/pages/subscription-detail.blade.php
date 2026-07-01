<div class="space-y-5 text-sm">
    <div class="grid grid-cols-2 gap-4">
        <div class="rounded-xl border border-slate-100 p-4">
            <p class="mb-2 font-semibold text-slate-700">Thuê bao</p>
            <dl class="space-y-1.5 text-slate-600">
                <div class="flex justify-between"><dt>Công ty</dt><dd class="font-medium text-slate-800">{{ $record->tenant?->name }}</dd></div>
                <div class="flex justify-between"><dt>Gói</dt><dd>{{ $record->plan?->name ?? '—' }}</dd></div>
                <div class="flex justify-between"><dt>Chu kỳ</dt><dd>{{ ['monthly'=>'Tháng','quarterly'=>'Quý','yearly'=>'Năm'][$record->billing_cycle] ?? $record->billing_cycle }}</dd></div>
                <div class="flex justify-between"><dt>MRR / ARR</dt><dd>{{ number_format($record->mrr) }} / {{ number_format($record->arr) }}đ</dd></div>
                <div class="flex justify-between"><dt>Trạng thái</dt><dd>{{ ($statusMap[$record->status] ?? [$record->status])[0] }}</dd></div>
                <div class="flex justify-between"><dt>Tự gia hạn</dt><dd>{{ $record->auto_renew ? 'Có' : 'Không' }}</dd></div>
            </dl>
        </div>
        <div class="rounded-xl border border-slate-100 p-4">
            <p class="mb-2 font-semibold text-slate-700">Hợp đồng</p>
            <dl class="space-y-1.5 text-slate-600">
                <div class="flex justify-between"><dt>Số HĐ</dt><dd class="font-medium text-slate-800">{{ $record->contract?->contract_no ?? '—' }}</dd></div>
                <div class="flex justify-between"><dt>Trạng thái HĐ</dt><dd>{{ $record->contract?->status ?? '—' }}</dd></div>
                <div class="flex justify-between"><dt>Hiệu lực</dt><dd>{{ $record->start_date?->format('d/m/Y') }} → {{ $record->end_date?->format('d/m/Y') }}</dd></div>
                <div class="flex justify-between"><dt>Giá trị năm</dt><dd>{{ number_format((float) ($record->contract->annual_value ?? 0)) }}đ</dd></div>
            </dl>
        </div>
    </div>

    <div class="grid grid-cols-2 gap-4">
        <div class="rounded-xl border border-slate-100 p-4">
            <p class="mb-2 font-semibold text-slate-700">Add-on ({{ $record->addons->count() }})</p>
            @forelse ($record->addons as $a)
                <div class="flex items-center justify-between border-b border-slate-50 py-1.5 last:border-0">
                    <span>{{ $a->name }}</span><span class="text-xs text-slate-400">{{ number_format($a->mrr) }}đ · {{ $a->status }}</span>
                </div>
            @empty
                <p class="text-slate-400">Chưa có add-on.</p>
            @endforelse
        </div>
        <div class="rounded-xl border border-slate-100 p-4">
            <p class="mb-2 font-semibold text-slate-700">Cấu thành</p>
            @forelse ($record->items as $it)
                <div class="flex items-center justify-between border-b border-slate-50 py-1.5 last:border-0">
                    <span>{{ $it->name }}</span><span class="text-xs text-slate-400">{{ number_format($it->amount) }}đ</span>
                </div>
            @empty
                <p class="text-slate-400">—</p>
            @endforelse
        </div>
    </div>

    <div class="rounded-xl border border-slate-100 p-4">
        <p class="mb-2 font-semibold text-slate-700">Hóa đơn ({{ $invoices->count() }})</p>
        @forelse ($invoices as $inv)
            <div class="flex items-center justify-between border-b border-slate-50 py-1.5 last:border-0">
                <span>{{ $inv->invoice_no }} · {{ $inv->period }}</span>
                <span class="text-xs text-slate-400">{{ number_format($inv->total_amount) }}đ · {{ $inv->status }}</span>
            </div>
        @empty
            <p class="text-slate-400">Chưa có hóa đơn.</p>
        @endforelse
    </div>
</div>
