<div class="space-y-5 text-sm">
    <div class="grid grid-cols-2 gap-4">
        <div class="rounded-xl border border-slate-100 p-4">
            <p class="mb-2 font-semibold text-slate-700">Hồ sơ đối tác</p>
            <dl class="space-y-1.5 text-slate-600">
                <div class="flex justify-between"><dt>Mã</dt><dd class="font-medium text-slate-800">{{ $record->code }}</dd></div>
                <div class="flex justify-between"><dt>Danh mục</dt><dd>{{ $record->category?->name ?? '—' }}</dd></div>
                <div class="flex justify-between"><dt>Tên pháp lý</dt><dd>{{ $record->legal_name ?? '—' }}</dd></div>
                <div class="flex justify-between"><dt>MST</dt><dd>{{ $record->tax_code ?? '—' }}</dd></div>
                <div class="flex justify-between"><dt>Liên hệ</dt><dd>{{ $record->contact_name ?? '—' }} · {{ $record->phone ?? '—' }}</dd></div>
                <div class="flex justify-between"><dt>Khu vực</dt><dd>{{ $record->service_area ?? '—' }}</dd></div>
            </dl>
        </div>
        <div class="rounded-xl border border-slate-100 p-4">
            <p class="mb-2 font-semibold text-slate-700">Đánh giá & trạng thái</p>
            <dl class="space-y-1.5 text-slate-600">
                <div class="flex justify-between"><dt>Trạng thái</dt><dd>{{ ($verificationMap[$record->verification_status] ?? [$record->verification_status])[0] }}</dd></div>
                <div class="flex justify-between"><dt>Đánh giá TB</dt><dd>{{ number_format((float) $record->rating_avg, 1) }} ★</dd></div>
                <div class="flex justify-between"><dt>Điểm KPI</dt><dd>{{ number_format((float) $record->kpi_score, 2) }}</dd></div>
                <div class="flex justify-between"><dt>Đang hoạt động</dt><dd>{{ $record->is_active ? 'Có' : 'Không' }}</dd></div>
            </dl>
            @if ($record->description)<p class="mt-2 text-slate-500">{{ $record->description }}</p>@endif
        </div>
    </div>

    @if ($record->certifications->isNotEmpty())
        <div class="rounded-xl border border-slate-100 p-4">
            <p class="mb-2 font-semibold text-slate-700">Chứng chỉ ({{ $record->certifications->count() }})</p>
            @foreach ($record->certifications as $ct)
                <div class="flex items-center justify-between border-b border-slate-50 py-1.5 last:border-0">
                    <span>{{ $ct->name }} <span class="text-slate-400">{{ $ct->certificate_no }}</span></span>
                    <span class="text-xs text-slate-400">{{ $ct->issued_by }} · HH {{ $ct->expired_at?->format('d/m/Y') ?? '—' }}</span>
                </div>
            @endforeach
        </div>
    @endif

    @if ($record->products->isNotEmpty())
        <div class="rounded-xl border border-slate-100 p-4">
            <p class="mb-2 font-semibold text-slate-700">Sản phẩm / vật tư ({{ $record->products->count() }})</p>
            @foreach ($record->products as $pr)
                <div class="flex items-center justify-between border-b border-slate-50 py-1.5 last:border-0">
                    <span>{{ $pr->name }} <span class="text-slate-400">{{ $pr->sku }}</span></span>
                    <span class="text-xs text-slate-400">{{ number_format((float) $pr->reference_price) }}đ / {{ $pr->unit ?? 'đv' }} · BH {{ $pr->warranty_months }}th</span>
                </div>
            @endforeach
        </div>
    @endif

    <div class="rounded-xl border border-slate-100 p-4">
        <p class="mb-2 font-semibold text-slate-700">Công ty đã gán ({{ $assignments->count() }})</p>
        @forelse ($assignments as $as)
            <div class="flex items-center justify-between border-b border-slate-50 py-1.5 last:border-0">
                <span>Tenant #{{ $as->tenant_id }}</span>
                <span class="text-xs text-slate-400">{{ $as->assignment_type }}</span>
            </div>
        @empty
            <p class="text-slate-400">Chưa gán cho công ty nào.</p>
        @endforelse
    </div>
</div>
