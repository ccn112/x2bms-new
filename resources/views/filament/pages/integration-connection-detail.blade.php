@php
    $credMeta = [
        'valid' => ['Hợp lệ', 'text-emerald-600 bg-emerald-50'], 'expiring' => ['Sắp hết hạn', 'text-amber-600 bg-amber-50'],
        'expired' => ['Hết hạn', 'text-red-600 bg-red-50'], 'revoked' => ['Thu hồi', 'text-red-600 bg-red-50'],
        'rotated' => ['Đã xoay', 'text-slate-500 bg-slate-100'], 'compromised' => ['Rò rỉ', 'text-red-700 bg-red-100'],
    ];
@endphp

<div class="space-y-5 text-sm">
    <div class="grid grid-cols-2 gap-3">
        <div><div class="text-xs text-slate-400">Mã</div><div class="font-mono text-slate-700">{{ $record->code }}</div></div>
        <div><div class="text-xs text-slate-400">Nhóm</div><div class="text-slate-700">{{ $record->category?->name ?? '—' }}</div></div>
        <div><div class="text-xs text-slate-400">Provider</div><div class="text-slate-700">{{ $record->provider_code }}</div></div>
        <div><div class="text-xs text-slate-400">Môi trường</div><div class="text-slate-700">{{ strtoupper($record->environment) }} · {{ $record->api_version }}</div></div>
        <div><div class="text-xs text-slate-400">Base URL</div><div class="truncate text-slate-700">{{ $record->base_url ?? '—' }}</div></div>
        <div><div class="text-xs text-slate-400">Owner</div><div class="text-slate-700">{{ $record->owner?->name ?? '—' }}</div></div>
        <div><div class="text-xs text-slate-400">Timeout / Retry</div><div class="text-slate-700">{{ $record->timeout_seconds }}s · {{ $record->retry_policy ?? '—' }}</div></div>
        <div><div class="text-xs text-slate-400">Success 24h / Latency</div><div class="text-slate-700">{{ $record->success_rate_24h !== null ? number_format($record->success_rate_24h,1).'%' : '—' }} · {{ $record->avg_latency_ms ?? '—' }}ms</div></div>
    </div>

    <div>
        <div class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-400">Credentials (che)</div>
        <ul class="divide-y divide-slate-50 rounded-lg border border-slate-100">
            @forelse ($record->credentials as $cr)
                <li class="flex items-center justify-between px-3 py-2">
                    <span class="font-mono text-slate-600">{{ $cr->credential_type }} · {{ $cr->masked_summary ?? '••••' }}</span>
                    <span class="rounded-full px-2 py-0.5 text-[11px] font-medium {{ $credMeta[$cr->status][1] ?? 'bg-slate-100 text-slate-500' }}">{{ $credMeta[$cr->status][0] ?? $cr->status }}</span>
                </li>
            @empty
                <li class="px-3 py-2 text-slate-400">Chưa có credential.</li>
            @endforelse
        </ul>
    </div>

    @if ($record->mappings->isNotEmpty())
        <div>
            <div class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-400">Mapping nghiệp vụ</div>
            <ul class="divide-y divide-slate-50 rounded-lg border border-slate-100">
                @foreach ($record->mappings as $mp)
                    <li class="flex items-center justify-between px-3 py-2">
                        <span class="text-slate-600">{{ $mp->source_event }} → {{ $mp->target_event }}</span>
                        <span class="text-[11px] text-slate-400">v{{ $mp->version }} · {{ $mp->mapping_type }}</span>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
        <div>
            <div class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-400">Health check gần đây</div>
            <ul class="divide-y divide-slate-50 rounded-lg border border-slate-100">
                @forelse ($record->checks as $ck)
                    <li class="flex items-center justify-between px-3 py-2">
                        <span class="text-slate-600">{{ $ck->checked_at?->format('d/m H:i') }} · HTTP {{ $ck->http_status }}</span>
                        <span class="tabular-nums {{ $ck->status === 'success' ? 'text-emerald-600' : 'text-red-600' }}">{{ $ck->latency_ms }}ms</span>
                    </li>
                @empty
                    <li class="px-3 py-2 text-slate-400">Chưa có lần test.</li>
                @endforelse
            </ul>
        </div>
        <div>
            <div class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-400">Audit</div>
            <ul class="divide-y divide-slate-50 rounded-lg border border-slate-100">
                @forelse ($audits as $a)
                    <li class="flex items-center justify-between px-3 py-2">
                        <span class="text-slate-600">{{ $a->action }}</span>
                        <span class="text-[11px] text-slate-400">{{ $a->created_at?->format('d/m H:i') }}</span>
                    </li>
                @empty
                    <li class="px-3 py-2 text-slate-400">Chưa có audit.</li>
                @endforelse
            </ul>
        </div>
    </div>
</div>
