@props([
    'lastActor' => null,   // who last acted (from audit_logs)
    'lastAction' => null,  // what
    'lastAt' => null,      // when
    'version' => null,
])

{{-- X2AuditFooter — shows last audit trail entry for the screen + build version. Data from audit_logs. --}}
<footer class="mt-auto border-t border-slate-200 bg-white px-5 py-3 text-xs text-slate-500">
    <div class="flex flex-wrap items-center justify-between gap-2">
        <div class="flex items-center gap-1.5">
            <svg class="h-3.5 w-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/></svg>
            @if ($lastActor || $lastAction)
                <span>
                    Cập nhật gần nhất:
                    <span class="font-medium text-slate-700">{{ $lastActor }}</span>
                    @if ($lastAction) — {{ $lastAction }} @endif
                    @if ($lastAt) <span class="text-slate-400">({{ $lastAt }})</span> @endif
                </span>
            @else
                <span>Có nhật ký kiểm toán cho màn hình này</span>
            @endif
        </div>
        <div class="text-slate-400">
            © {{ now()->year }} X2-BMS @if ($version) · {{ $version }} @endif
        </div>
    </div>
</footer>
