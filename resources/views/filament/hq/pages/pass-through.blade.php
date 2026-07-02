<x-filament-panels::page>
@php $money = fn ($v) => number_format($v).' đ'; @endphp
<div class="space-y-6">
    <div>
        <h1 class="font-title text-2xl font-bold text-slate-900">Pass-through: SMS, Zalo, Email, Payment Gateway</h1>
        <p class="mt-1 text-sm text-slate-500">Chi phí dịch vụ bên thứ ba tính chuyển tiếp cho công ty theo từng kênh & nhà cung cấp.</p>
    </div>
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ($rows as $r)
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-center gap-2">
                    <span class="h-3 w-3 rounded-full" style="background: {{ $r['color'] }}"></span>
                    <h3 class="font-title text-sm font-bold text-slate-900">{{ $r['name'] }}</h3>
                </div>
                <div class="mt-3 text-2xl font-bold text-slate-900">{{ $money($r['cost']) }}</div>
                <div class="mt-1 text-xs text-slate-400">{{ $r['provider'] }}</div>
                <dl class="mt-4 space-y-1.5 text-xs">
                    <div class="flex justify-between"><dt class="text-slate-500">Đã dùng</dt><dd class="font-medium text-slate-700">{{ number_format($r['used']) }}{{ $r['limit'] ? ' / '.number_format($r['limit']) : '' }}</dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500">Đơn giá</dt><dd class="font-medium text-slate-700">{{ number_format($r['unit']) }} đ</dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500">Markup</dt><dd class="font-medium text-slate-700">{{ $r['markup'] }}%</dd></div>
                </dl>
            </div>
        @endforeach
    </div>
    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="flex items-center justify-between">
            <span class="font-title text-sm font-bold text-slate-900">Tổng chi phí pass-through kỳ 07/2026</span>
            <span class="text-2xl font-bold text-slate-900">{{ $money($totalCost) }}</span>
        </div>
    </div>
</div>
</x-filament-panels::page>
