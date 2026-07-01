<div class="space-y-5 text-sm">
    <div class="grid grid-cols-2 gap-3">
        <div><div class="text-xs text-slate-400">Client ID</div><div class="font-mono text-slate-700">{{ $record->client_id }}</div></div>
        <div><div class="text-xs text-slate-400">Secret (masked)</div><div class="font-mono text-slate-700">{{ $record->metadata_json['secret_masked'] ?? '••••' }}</div></div>
        <div><div class="text-xs text-slate-400">Môi trường</div><div class="text-slate-700">{{ strtoupper($record->environment) }}</div></div>
        <div><div class="text-xs text-slate-400">Rate limit</div><div class="text-slate-700">{{ $record->rate_limit_per_minute }}/phút</div></div>
        <div><div class="text-xs text-slate-400">HMAC / IP allowlist</div><div class="text-slate-700">{{ $record->require_hmac ? 'Có' : 'Không' }} · {{ $record->require_ip_allowlist ? 'Có' : 'Không' }}</div></div>
        <div><div class="text-xs text-slate-400">Hết hạn / Last used</div><div class="text-slate-700">{{ $record->expires_at?->format('d/m/Y') ?? '—' }} · {{ $record->last_used_at?->diffForHumans() ?? '—' }}</div></div>
    </div>

    <div>
        <div class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-400">Scopes</div>
        <div class="flex flex-wrap gap-2">
            @forelse ($record->scopes as $sc)
                <span class="rounded-full bg-slate-100 px-2.5 py-1 font-mono text-[11px] text-slate-600">{{ $sc->scope_code }}</span>
            @empty
                <span class="text-slate-400">Chưa gán scope.</span>
            @endforelse
        </div>
    </div>

    @if ($record->allowed_ips_json)
        <div>
            <div class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-400">IP allowlist</div>
            <div class="flex flex-wrap gap-2">
                @foreach ($record->allowed_ips_json as $ip)
                    <span class="rounded-full bg-blue-50 px-2.5 py-1 font-mono text-[11px] text-blue-600">{{ $ip }}</span>
                @endforeach
            </div>
        </div>
    @endif

    <div>
        <div class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-400">Lịch sử rotate</div>
        <ul class="divide-y divide-slate-50 rounded-lg border border-slate-100">
            @forelse ($record->rotations as $rot)
                <li class="flex items-center justify-between px-3 py-2">
                    <span class="text-slate-600">{{ $rot->reason ?? 'rotation' }}</span>
                    <span class="text-[11px] text-slate-400">{{ $rot->rotated_at?->format('d/m/Y H:i') }}</span>
                </li>
            @empty
                <li class="px-3 py-2 text-slate-400">Chưa rotate lần nào.</li>
            @endforelse
        </ul>
    </div>
</div>
