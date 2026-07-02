<x-filament-panels::page>
<div class="space-y-6">
    <div><h1 class="font-title text-2xl font-bold text-slate-900">Nhật ký hoạt động</h1>
        <p class="mt-1 text-sm text-slate-500">Toàn bộ hành động nhạy cảm (phân quyền, đổi trạng thái, phê duyệt...) được ghi audit.</p></div>
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-100 text-sm">
                <thead class="bg-slate-50/70 text-left text-xs font-semibold uppercase tracking-wide text-slate-500"><tr><th class="px-4 py-3">Thời gian</th><th class="px-4 py-3">Người thực hiện</th><th class="px-4 py-3">Hành động</th><th class="px-4 py-3">Mô tả</th></tr></thead>
                <tbody class="divide-y divide-slate-50">
                    @forelse ($rows as $r)
                        <tr class="hover:bg-slate-50/60">
                            <td class="whitespace-nowrap px-4 py-3 text-slate-500">{{ $r['at'] }}</td>
                            <td class="whitespace-nowrap px-4 py-3 font-medium text-slate-800">{{ $r['actor'] }}</td>
                            <td class="whitespace-nowrap px-4 py-3"><span class="rounded-md bg-slate-100 px-2 py-0.5 font-mono text-xs text-slate-600">{{ $r['action'] }}</span></td>
                            <td class="px-4 py-3 text-slate-600">{{ $r['desc'] }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-4 py-8 text-center text-slate-400">Chưa có nhật ký.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
</x-filament-panels::page>
