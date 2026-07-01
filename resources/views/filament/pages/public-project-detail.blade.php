@php $amen = $record->amenities_json ?: []; @endphp
<div class="space-y-5 text-sm">
    <div class="grid grid-cols-2 gap-4">
        <div class="rounded-xl border border-slate-100 p-4">
            <p class="mb-2 font-semibold text-slate-700">Tổng quan</p>
            <dl class="space-y-1.5 text-slate-600">
                <div class="flex justify-between"><dt>Mã</dt><dd class="font-medium text-slate-800">{{ $record->code }}</dd></div>
                <div class="flex justify-between"><dt>Chủ đầu tư</dt><dd>{{ $record->developer_name ?? '—' }}</dd></div>
                <div class="flex justify-between"><dt>Địa điểm</dt><dd>{{ $record->province ?? '—' }}</dd></div>
                <div class="flex justify-between"><dt>Trạng thái</dt><dd>{{ ($statusMap[$record->status] ?? [$record->status])[0] }}</dd></div>
                <div class="flex justify-between"><dt>Block / Căn</dt><dd>{{ $record->blocks }} / {{ $record->apartments }}</dd></div>
                <div class="flex justify-between"><dt>Public</dt><dd>{{ $record->is_public ? 'Có' : 'Ẩn' }}</dd></div>
            </dl>
        </div>
        <div class="rounded-xl border border-slate-100 p-4">
            <p class="mb-2 font-semibold text-slate-700">Tiện ích</p>
            @if ($amen)
                <div class="flex flex-wrap gap-1.5">
                    @foreach ($amen as $a)
                        <span class="rounded-lg bg-slate-100 px-2 py-0.5 text-xs text-slate-600">{{ is_array($a) ? ($a['name'] ?? '') : $a }}</span>
                    @endforeach
                </div>
            @else
                <p class="text-slate-400">Chưa khai báo tiện ích.</p>
            @endif
            @if ($record->description)
                <p class="mt-3 text-slate-600">{{ $record->description }}</p>
            @endif
        </div>
    </div>

    <div class="rounded-xl border border-slate-100 p-4">
        <p class="mb-2 font-semibold text-slate-700">Media ({{ $record->media->count() }})</p>
        @forelse ($record->media->sortBy('sort_order') as $m)
            <div class="flex items-center justify-between border-b border-slate-50 py-1.5 last:border-0">
                <span class="flex items-center gap-1.5"><x-heroicon-m-photo class="h-4 w-4 text-slate-400" />{{ $m->title ?? $m->file_url }}</span>
                <span class="text-xs text-slate-400">{{ $m->media_type }}</span>
            </div>
        @empty
            <p class="text-slate-400">Chưa có media.</p>
        @endforelse
    </div>

    <div class="rounded-xl border border-slate-100 p-4">
        <p class="mb-2 font-semibold text-slate-700">Công ty đã liên kết ({{ $links->count() }})</p>
        @forelse ($links as $l)
            <div class="flex items-center justify-between border-b border-slate-50 py-1.5 last:border-0">
                <span>{{ $l->tenant?->name ?? ('Tenant #'.$l->tenant_id) }}</span>
                <span class="text-xs text-slate-400">{{ $l->linked_at?->format('d/m/Y') }}</span>
            </div>
        @empty
            <p class="text-slate-400">Chưa liên kết công ty nào.</p>
        @endforelse
    </div>
</div>
