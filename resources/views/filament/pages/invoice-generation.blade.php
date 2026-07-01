<x-filament-panels::page>
    <x-x2.action-bar
        title="Sinh hóa đơn SaaS"
        subtitle="Gộp thuê bao + add-on + overage (kỳ đã khóa) + pass-through thành hóa đơn nháp." />

    <div class="flex items-center justify-between gap-3 rounded-xl border border-slate-100 bg-white p-4">
        <div class="text-sm">
            <span class="font-semibold text-slate-700">Kỳ:</span> {{ $period?->code ?? '—' }}
            @if ($periodLocked)
                <span class="ml-2 rounded bg-green-50 px-2 py-0.5 text-xs text-green-700">Đã khóa · sẵn sàng</span>
            @else
                <span class="ml-2 rounded bg-amber-50 px-2 py-0.5 text-xs text-amber-700">Chưa khóa — khóa kỳ ở màn Usage trước</span>
            @endif
        </div>
        @if ($periodLocked)
            {{ $this->generateAction }}
        @endif
    </div>

    <x-x2.section-card title="Xem trước hóa đơn theo thuê bao" subtitle="Kỳ {{ $periodKey }}">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-100 text-left text-xs uppercase text-slate-400">
                        <th class="py-2">Công ty</th><th>Gói</th><th class="text-right">Thuê bao</th>
                        <th class="text-right">Add-on</th><th class="text-right">Overage</th><th class="text-right">Tạm tính</th><th class="text-center">TT</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($eligible as $row)
                        <tr class="border-b border-slate-50">
                            <td class="py-2 font-medium text-slate-700">{{ $row['sub']->tenant?->name ?? '—' }}</td>
                            <td class="text-slate-500">{{ $row['sub']->plan?->name ?? '—' }}</td>
                            <td class="text-right tabular-nums">{{ number_format($row['base']) }}</td>
                            <td class="text-right tabular-nums">{{ number_format($row['addon']) }}</td>
                            <td class="text-right tabular-nums {{ $row['overage'] > 0 ? 'text-red-600' : '' }}">{{ number_format($row['overage']) }}</td>
                            <td class="text-right font-semibold tabular-nums text-slate-800">{{ number_format($row['total']) }}</td>
                            <td class="text-center">
                                @if ($row['exists'])
                                    <span class="rounded bg-slate-100 px-2 py-0.5 text-xs text-slate-500">đã có</span>
                                @else
                                    <span class="rounded bg-blue-50 px-2 py-0.5 text-xs text-blue-700">sẽ tạo</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="py-3 text-slate-400">Không có thuê bao đủ điều kiện.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-x2.section-card>

    <x-x2.section-card title="Hóa đơn nháp gần đây">
        <ul class="divide-y divide-slate-50 text-sm">
            @forelse ($recentDrafts as $inv)
                <li class="flex items-center justify-between py-2">
                    <span class="text-slate-700">{{ $inv->invoice_no }} · {{ $inv->tenant?->name }}</span>
                    <span class="text-xs text-slate-400">{{ number_format($inv->total_amount) }}đ · {{ $inv->period }}</span>
                </li>
            @empty
                <li class="py-2 text-slate-400">Chưa có hóa đơn nháp.</li>
            @endforelse
        </ul>
    </x-x2.section-card>
</x-filament-panels::page>
