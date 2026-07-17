@if (count($timeline ?? []))
    <ol class="space-y-3">
        @foreach ($timeline as $e)
            <li class="flex gap-3">
                <div class="flex flex-col items-center">
                    <span class="mt-1.5 h-2.5 w-2.5 shrink-0 rounded-full {{ $dotColor[$e['dot']] ?? 'bg-slate-400' }}"></span>
                    @unless ($loop->last)<span class="mt-1 w-px flex-1 bg-slate-200"></span>@endunless
                </div>
                <div class="flex-1 pb-1">
                    <div class="flex items-start justify-between gap-3">
                        <p class="text-sm font-medium text-slate-800">{{ $e['title'] }}</p>
                        <span class="shrink-0 text-xs text-slate-400">{{ $e['date'] }} {{ $e['time'] }}</span>
                    </div>
                    @if ($e['detail'])<p class="text-xs text-slate-500">{{ $e['detail'] }}</p>@endif
                    @if ($e['actor'])<p class="mt-0.5 text-xs text-slate-400">{{ $e['actor'] }}</p>@endif
                </div>
            </li>
        @endforeach
    </ol>
@else
    <p class="text-sm text-slate-400">Chưa có sự kiện.</p>
@endif
