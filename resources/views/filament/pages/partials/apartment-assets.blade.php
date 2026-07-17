@php
    $compact = $compact ?? false;
    $vs = $compact ? $vehicles->take(2) : $vehicles;
    $cs = $compact ? $cards->take(2) : $cards;
@endphp

<div class="space-y-2">
    @foreach ($vs as $v)
        <div class="flex items-center justify-between gap-2 rounded-lg border border-slate-100 px-3 py-2">
            <div class="flex min-w-0 items-center gap-2.5">
                <svg class="h-5 w-5 shrink-0 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 01-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 00-3.213-9.193 2.056 2.056 0 00-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 00-10.026 0 1.106 1.106 0 00-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12"/></svg>
                <div class="min-w-0">
                    <p class="truncate font-medium text-slate-800">{{ $v->plate_no }} <span class="ml-1 text-xs font-normal text-slate-400">{{ optional($v->type)->label() ?? $v->type }}</span></p>
                    <p class="truncate text-xs text-slate-500">{{ optional($v->resident)->full_name ?? '—' }}</p>
                </div>
            </div>
            <x-x2.status-badge label="Đang hiệu lực" tone="green" />
        </div>
    @endforeach

    @foreach ($cs as $c)
        @php $cardStatus = $c->status instanceof \BackedEnum ? $c->status->value : $c->status; @endphp
        <div class="flex items-center justify-between gap-2 rounded-lg border border-slate-100 px-3 py-2">
            <div class="flex min-w-0 items-center gap-2.5">
                <svg class="h-5 w-5 shrink-0 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3M3.75 5.25h16.5a1.5 1.5 0 011.5 1.5v10.5a1.5 1.5 0 01-1.5 1.5H3.75a1.5 1.5 0 01-1.5-1.5V6.75a1.5 1.5 0 011.5-1.5z"/></svg>
                <div class="min-w-0">
                    <p class="truncate font-medium text-slate-800">Thẻ cư dân #{{ $c->card_no }}</p>
                    <p class="truncate text-xs text-slate-500">{{ $c->is_biometric ? 'Sinh trắc học' : 'RFID' }}</p>
                </div>
            </div>
            <x-x2.status-badge :label="$cardStatus === 'active' ? 'Đang hiệu lực' : ($cardStatus === 'revoked' ? 'Thu hồi' : 'Hết hạn')" :tone="$cardStatus === 'active' ? 'green' : 'red'" />
        </div>
    @endforeach

    @if (! $vehicles->count() && ! $cards->count())
        <p class="text-sm text-slate-400">Chưa gắn phương tiện / thẻ.</p>
    @endif
</div>
