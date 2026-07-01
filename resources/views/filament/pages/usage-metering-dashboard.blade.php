<x-filament-panels::page>
    <x-x2.action-bar
        title="Theo dõi usage & metering"
        subtitle="Thu thập/nhập usage → tính lại → khóa kỳ → sinh cảnh báo vượt hạn. Kỳ đã khóa mới đưa vào hóa đơn." />

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ($kpis as $kpi)
            <x-x2.kpi-card :label="$kpi['label']" :value="$kpi['value']" :accent="$kpi['accent']" />
        @endforeach
    </div>

    @if ($period && $period->status === 'locked')
        <div class="rounded-xl border border-green-200 bg-green-50 p-3 text-sm text-green-800">
            🔒 Kỳ <strong>{{ $period->code }}</strong> đã khóa lúc {{ $period->locked_at?->format('d/m/Y H:i') }} bởi {{ $period->locked_by }} — sẵn sàng sinh hóa đơn.
        </div>
    @endif

    <div class="rounded-2xl border border-slate-100 bg-white p-1 shadow-sm">
        {{ $this->table }}
    </div>
</x-filament-panels::page>
