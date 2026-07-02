<x-filament-panels::page>
@php
    $cards = [['Biểu mẫu đang áp dụng', (int)($kpi['forms_applied']??0), 'blue'], ['Tài liệu chuẩn hiệu lực', (int)($kpi['docs_effective']??0), 'green'], ['Bài tri thức AI đã index', number_format($kpi['ai_indexed']??0), 'violet'], ['Yêu cầu cập nhật chờ duyệt', (int)($kpi['pending_updates']??0), 'amber']];
    $ac = ['blue'=>'bg-blue-500','green'=>'bg-emerald-500','violet'=>'bg-violet-500','amber'=>'bg-amber-500'];
    $stColors = ['Đã index'=>'#10b981','Chờ re-index'=>'#f59e0b','Đang rà soát'=>'#3b82f6','Lỗi cấu trúc'=>'#ef4444'];
    $totalKb = $status->sum('count'); $C = 2*M_PI*54; $off = 0;
    $maxRate = max($apply->pluck('rate')->all() ?: [1]);
@endphp
<div class="space-y-6">
    <div>
        <h1 class="font-title text-2xl font-bold text-slate-900">Trung tâm biểu mẫu, tài liệu dùng chung & tri thức AI</h1>
        <p class="mt-1 text-sm text-slate-500">Quản lý, chuẩn hóa và chia sẻ biểu mẫu, tài liệu & tri thức AI cho toàn bộ dự án và BQL.</p>
    </div>
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ($cards as [$label, $value, $c])
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-center gap-3">
                    <span class="grid h-11 w-11 place-items-center rounded-xl {{ $ac[$c] }} text-white"><svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25"/></svg></span>
                    <div><div class="text-xs text-slate-500">{{ $label }}</div><div class="text-2xl font-bold text-slate-900">{{ $value }}</div></div>
                </div>
            </div>
        @endforeach
    </div>
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h3 class="font-title text-sm font-bold text-slate-900">Mức độ áp dụng theo dự án</h3>
            <div class="mt-4 flex h-52 items-end justify-between gap-3">
                @foreach ($apply as $a)
                    <div class="flex flex-1 flex-col items-center gap-2">
                        <span class="text-xs font-semibold text-slate-700">{{ (int) $a['rate'] }}%</span>
                        <div class="w-full rounded-t-lg bg-blue-500" style="height: {{ max(6, round($a['rate'] / $maxRate * 150)) }}px"></div>
                        <span class="text-center text-[10px] leading-tight text-slate-400">{{ $a['project'] }}</span>
                    </div>
                @endforeach
            </div>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h3 class="font-title text-sm font-bold text-slate-900">Tình trạng tri thức AI</h3>
            <div class="mt-4 flex items-center gap-4">
                <div class="relative h-36 w-36 shrink-0">
                    <svg viewBox="0 0 120 120" class="h-36 w-36 -rotate-90">
                        <circle cx="60" cy="60" r="54" fill="none" stroke="#f1f5f9" stroke-width="13"/>
                        @foreach ($status as $s)
                            @php $len = $C * ($totalKb ? $s['count']/$totalKb : 0); @endphp
                            <circle cx="60" cy="60" r="54" fill="none" stroke="{{ $stColors[$s['label']] ?? '#94a3b8' }}" stroke-width="13" stroke-dasharray="{{ $len }} {{ $C-$len }}" stroke-dashoffset="{{ -$off }}"/>
                            @php $off += $len; @endphp
                        @endforeach
                    </svg>
                    <div class="absolute inset-0 grid place-items-center text-center"><div><div class="text-lg font-bold text-slate-900">{{ number_format($totalKb) }}</div><div class="text-[10px] text-slate-400">Tổng bài</div></div></div>
                </div>
                <div class="flex-1 space-y-1.5">
                    @foreach ($status as $s)
                        <div class="flex items-center gap-2 text-xs"><span class="h-2.5 w-2.5 rounded-full" style="background: {{ $stColors[$s['label']] ?? '#94a3b8' }}"></span><span class="text-slate-600">{{ $s['label'] }}</span><span class="ml-auto font-semibold text-slate-800">{{ $s['pct'] }}%</span><span class="w-12 text-right text-slate-400">({{ $s['count'] }})</span></div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>
</x-filament-panels::page>
