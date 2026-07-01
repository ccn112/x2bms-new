<div class="space-y-3 text-sm">
    <div class="text-slate-500">{{ $record->endpoint_name }} — <span class="font-mono text-xs">{{ $record->url }}</span></div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left text-xs uppercase tracking-wide text-slate-400">
                    <th class="pb-2">Thời điểm</th><th class="pb-2">Correlation</th><th class="pb-2">HTTP</th>
                    <th class="pb-2 text-right">Latency</th><th class="pb-2">Trạng thái</th><th class="pb-2">Lần</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
                @forelse ($deliveries as $d)
                    <tr>
                        <td class="py-2 text-slate-600">{{ $d->delivered_at?->format('d/m H:i:s') ?? '—' }}</td>
                        <td class="py-2 font-mono text-[11px] text-slate-500">{{ $d->correlation_id }}</td>
                        <td class="py-2 tabular-nums {{ $d->http_status < 400 ? 'text-emerald-600' : 'text-red-600' }}">{{ $d->http_status ?? '—' }}</td>
                        <td class="py-2 text-right tabular-nums text-slate-500">{{ $d->duration_ms }}ms</td>
                        <td class="py-2">
                            <span class="rounded-full px-2 py-0.5 text-[11px] font-medium {{ $d->status === 'success' ? 'bg-emerald-50 text-emerald-600' : 'bg-red-50 text-red-600' }}">{{ $d->status }}</span>
                        </td>
                        <td class="py-2 text-slate-500">#{{ $d->attempt_no }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="py-3 text-center text-slate-400">Chưa có lần gửi nào.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
