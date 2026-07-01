<div class="space-y-5 text-sm">
    <div class="grid grid-cols-2 gap-4">
        <div class="rounded-xl border border-slate-100 p-4">
            <p class="mb-2 font-semibold text-slate-700">Hóa đơn</p>
            <dl class="space-y-1.5 text-slate-600">
                <div class="flex justify-between"><dt>Số HĐ</dt><dd class="font-medium text-slate-800">{{ $record->invoice_no }}</dd></div>
                <div class="flex justify-between"><dt>Công ty</dt><dd>{{ $record->tenant?->name }}</dd></div>
                <div class="flex justify-between"><dt>Kỳ</dt><dd>{{ $record->period }}</dd></div>
                <div class="flex justify-between"><dt>Trạng thái</dt><dd>{{ ($statusMap[$record->status] ?? [$record->status])[0] }}</dd></div>
                <div class="flex justify-between"><dt>Phát hành</dt><dd>{{ $record->issue_date?->format('d/m/Y') ?? '—' }}</dd></div>
                <div class="flex justify-between"><dt>Hạn</dt><dd>{{ $record->due_date?->format('d/m/Y') ?? '—' }}</dd></div>
            </dl>
        </div>
        <div class="rounded-xl border border-slate-100 p-4">
            <p class="mb-2 font-semibold text-slate-700">Số tiền</p>
            <dl class="space-y-1.5 text-slate-600">
                <div class="flex justify-between"><dt>Tạm tính</dt><dd>{{ number_format($record->subtotal) }}đ</dd></div>
                <div class="flex justify-between"><dt>Chiết khấu</dt><dd>-{{ number_format($record->discount_total) }}đ</dd></div>
                <div class="flex justify-between"><dt>Thuế</dt><dd>{{ number_format($record->tax_total) }}đ</dd></div>
                <div class="flex justify-between border-t border-slate-100 pt-1 font-semibold text-slate-800"><dt>Tổng</dt><dd>{{ number_format($record->total_amount) }}đ</dd></div>
                <div class="flex justify-between text-green-600"><dt>Đã trả</dt><dd>{{ number_format($record->paid_amount) }}đ</dd></div>
                <div class="flex justify-between text-red-600"><dt>Còn lại</dt><dd>{{ number_format($record->remaining_amount) }}đ</dd></div>
            </dl>
        </div>
    </div>

    <div class="rounded-xl border border-slate-100 p-4">
        <p class="mb-2 font-semibold text-slate-700">Dòng hóa đơn ({{ $record->lines->count() }})</p>
        @forelse ($record->lines as $l)
            <div class="flex items-center justify-between border-b border-slate-50 py-1.5 last:border-0">
                <span><span class="rounded bg-slate-100 px-1.5 py-0.5 text-xs text-slate-500">{{ $l->line_type }}</span> {{ $l->description }}</span>
                <span class="tabular-nums {{ $l->amount < 0 ? 'text-red-600' : 'text-slate-700' }}">{{ number_format($l->amount) }}đ</span>
            </div>
        @empty
            <p class="text-slate-400">Chưa có dòng.</p>
        @endforelse
    </div>

    <div class="rounded-xl border border-slate-100 p-4">
        <p class="mb-2 font-semibold text-slate-700">Thanh toán ({{ $record->payments->count() }})</p>
        @forelse ($record->payments as $p)
            <div class="flex items-center justify-between border-b border-slate-50 py-1.5 last:border-0">
                <span>{{ $p->paid_at?->format('d/m/Y') }} · {{ $p->payment_method }} · {{ $p->transaction_ref }}</span>
                <span class="text-green-600">{{ number_format($p->amount) }}đ</span>
            </div>
        @empty
            <p class="text-slate-400">Chưa có thanh toán.</p>
        @endforelse
    </div>
</div>
