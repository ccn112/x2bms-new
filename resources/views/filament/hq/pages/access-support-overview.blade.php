<x-filament-panels::page>
@php
    $fmtMin = fn ($m) => $m >= 60 ? floor($m/60).'h '.($m%60).'m' : $m.'m';
    $cards = [
        ['Tổng người dùng', number_format($kpi['total_users']??0), 'blue'],
        ['Vai trò đang dùng', (int)($kpi['roles']??0), 'violet'],
        ['Ticket', number_format($kpi['tickets']??0), 'orange'],
        ['Ticket quá hạn SLA', (int)($kpi['sla_overdue']??0), 'red'],
        ['Mức độ hài lòng CSAT', ($kpi['csat']??0).'/5', 'green'],
    ];
    $ac = ['blue'=>'bg-blue-500','violet'=>'bg-violet-500','orange'=>'bg-orange-500','red'=>'bg-rose-500','green'=>'bg-emerald-500'];
    $dc = ['green'=>'#10b981','amber'=>'#f59e0b','red'=>'#ef4444','gray'=>'#94a3b8','blue'=>'#3b82f6','orange'=>'#f97316','violet'=>'#8b5cf6'];
    $donut = function($items, $key) use ($dc) {
        $total = $items->sum('count'); $C = 2*M_PI*52; $off = 0; $out = '';
        foreach ($items as $s) { $len = $C * ($total? $s['count']/$total : 0); $out .= '<circle cx="60" cy="60" r="52" fill="none" stroke="'.($dc[$s['color']??'blue']??'#3b82f6').'" stroke-width="14" stroke-dasharray="'.$len.' '.($C-$len).'" stroke-dashoffset="'.(-$off).'"/>'; $off += $len; }
        return [$out, $total];
    };
    [$usSvg, $usTotal] = $donut($userStatus, 'us');
    [$tsSvg, $tsTotal] = $donut($ticketStatus, 'ts');
    $maxCsat = 5; $maxBuild = max($byBuilding->pluck('count')->all() ?: [1]);
@endphp
<div class="space-y-6">
    <div>
        <h1 class="font-title text-2xl font-bold text-slate-900">Tổng quan phân quyền & hỗ trợ</h1>
        <p class="mt-1 text-sm text-slate-500">Giám sát phân quyền và hoạt động hỗ trợ trên toàn hệ thống.</p>
    </div>
    <div class="grid grid-cols-2 gap-4 md:grid-cols-3 xl:grid-cols-5">
        @foreach ($cards as [$label,$value,$c])
            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm"><div class="flex items-center gap-3"><span class="grid h-10 w-10 place-items-center rounded-full {{ $ac[$c] }} text-white"><svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.5 20.25a8.25 8.25 0 0 1 15 0"/></svg></span><div><div class="text-xs text-slate-500">{{ $label }}</div><div class="text-xl font-bold text-slate-900">{{ $value }}</div></div></div></div>
        @endforeach
    </div>
    <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm"><div class="text-xs text-slate-500">Thời gian phản hồi TB</div><div class="mt-1 text-lg font-bold text-slate-900">1h 18m</div></div>
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm"><div class="text-xs text-slate-500">Thời gian giải quyết TB</div><div class="mt-1 text-lg font-bold text-slate-900">8h 42m</div></div>
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm"><div class="text-xs text-slate-500">Tỷ lệ đúng SLA</div><div class="mt-1 text-lg font-bold text-emerald-600">88.4%</div></div>
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm"><div class="text-xs text-slate-500">CSAT xu hướng</div><div class="mt-1 text-lg font-bold text-blue-600">▲ {{ $csat->last()['value'] ?? '—' }}</div></div>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        {{-- User status donut --}}
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h3 class="font-title text-sm font-bold text-slate-900">Người dùng theo trạng thái</h3>
            <div class="mt-4 flex items-center gap-4">
                <div class="relative h-28 w-28 shrink-0"><svg viewBox="0 0 120 120" class="h-28 w-28 -rotate-90"><circle cx="60" cy="60" r="52" fill="none" stroke="#f1f5f9" stroke-width="14"/>{!! $usSvg !!}</svg><div class="absolute inset-0 grid place-items-center text-center"><div><div class="text-base font-bold text-slate-900">{{ number_format($usTotal) }}</div><div class="text-[10px] text-slate-400">Tổng</div></div></div></div>
                <div class="flex-1 space-y-1 text-xs">
                    @foreach ($userStatus as $s)<div class="flex items-center gap-2"><span class="h-2 w-2 rounded-full" style="background: {{ $dc[$s['color']] ?? '#94a3b8' }}"></span><span class="text-slate-600">{{ $s['label'] }}</span><span class="ml-auto font-semibold text-slate-800">{{ number_format($s['count']) }}</span><span class="w-10 text-right text-slate-400">{{ $s['pct'] }}%</span></div>@endforeach
                </div>
            </div>
        </div>
        {{-- Role usage --}}
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h3 class="font-title text-sm font-bold text-slate-900">Vai trò đang sử dụng</h3>
            <div class="mt-4 space-y-3">
                @foreach ($roleUsage as $r)
                    <div><div class="mb-1 flex justify-between text-xs"><span class="text-slate-600">{{ $r['label'] }}</span><span class="font-semibold text-slate-800">{{ $r['count'] }} ({{ $r['pct'] }}%)</span></div><div class="h-1.5 rounded-full bg-slate-100"><div class="h-full rounded-full bg-violet-500" style="width: {{ $r['pct'] }}%"></div></div></div>
                @endforeach
            </div>
        </div>
        {{-- Ticket status donut --}}
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h3 class="font-title text-sm font-bold text-slate-900">Ticket theo trạng thái</h3>
            <div class="mt-4 flex items-center gap-4">
                <div class="relative h-28 w-28 shrink-0"><svg viewBox="0 0 120 120" class="h-28 w-28 -rotate-90"><circle cx="60" cy="60" r="52" fill="none" stroke="#f1f5f9" stroke-width="14"/>{!! $tsSvg !!}</svg><div class="absolute inset-0 grid place-items-center text-center"><div><div class="text-base font-bold text-slate-900">{{ number_format($tsTotal) }}</div><div class="text-[10px] text-slate-400">Tổng</div></div></div></div>
                <div class="flex-1 space-y-1 text-xs">
                    @foreach ($ticketStatus as $s)<div class="flex items-center gap-2"><span class="h-2 w-2 rounded-full" style="background: {{ $dc[$s['color']] ?? '#94a3b8' }}"></span><span class="text-slate-600">{{ $s['label'] }}</span><span class="ml-auto font-semibold text-slate-800">{{ $s['count'] }}</span><span class="w-10 text-right text-slate-400">{{ $s['pct'] }}%</span></div>@endforeach
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h3 class="font-title text-sm font-bold text-slate-900">Phân bổ ticket theo nguồn</h3>
            <div class="mt-4 space-y-3">
                @foreach ($ticketSource as $s)<div><div class="mb-1 flex justify-between text-xs"><span class="text-slate-600">{{ $s['label'] }}</span><span class="font-semibold text-slate-800">{{ $s['count'] }} ({{ $s['pct'] }}%)</span></div><div class="h-1.5 rounded-full bg-slate-100"><div class="h-full rounded-full bg-blue-500" style="width: {{ $s['pct'] }}%"></div></div></div>@endforeach
            </div>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h3 class="font-title text-sm font-bold text-slate-900">Ticket theo tòa nhà (Top 5)</h3>
            <div class="mt-4 space-y-3">
                @foreach ($byBuilding as $b)<div><div class="mb-1 flex justify-between text-xs"><span class="text-slate-600">{{ $b['label'] }}</span><span class="font-semibold text-slate-800">{{ $b['count'] }}</span></div><div class="h-1.5 rounded-full bg-slate-100"><div class="h-full rounded-full bg-orange-500" style="width: {{ round($b['count']/$maxBuild*100) }}%"></div></div></div>@endforeach
            </div>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h3 class="font-title text-sm font-bold text-slate-900">CSAT theo tuần</h3>
            <div class="mt-4 flex h-40 items-end justify-between gap-2">
                @foreach ($csat as $c)<div class="flex flex-1 flex-col items-center gap-1"><span class="text-[10px] font-semibold text-slate-700">{{ $c['value'] }}</span><div class="w-full rounded-t bg-blue-400" style="height: {{ round($c['value']/$maxCsat*120) }}px"></div><span class="text-[9px] text-slate-400">{{ $c['day'] }}</span></div>@endforeach
            </div>
        </div>
    </div>
</div>
</x-filament-panels::page>
