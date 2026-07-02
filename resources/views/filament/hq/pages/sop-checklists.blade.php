<x-filament-panels::page>
@php $stB = ['active'=>['Hiệu lực','bg-emerald-50 text-emerald-700'],'draft'=>['Dự thảo','bg-amber-50 text-amber-700'],'archived'=>['Lưu trữ','bg-slate-100 text-slate-500']]; @endphp
<div class="space-y-6">
    <div><h1 class="font-title text-2xl font-bold text-slate-900">SOP & checklist vận hành mẫu</h1>
        <p class="mt-1 text-sm text-slate-500">Thư viện quy trình chuẩn (SOP) và checklist vận hành áp dụng cho các dự án.</p></div>
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3"><h3 class="font-title text-sm font-bold text-slate-900">SOP quy trình</h3><span class="text-xs text-slate-400">{{ $sops->count() }} quy trình</span></div>
            <table class="min-w-full divide-y divide-slate-100 text-sm">
                <thead class="bg-slate-50/70 text-left text-xs font-semibold uppercase tracking-wide text-slate-500"><tr><th class="px-4 py-2">Mã</th><th class="px-4 py-2">Tên SOP</th><th class="px-4 py-2">Nhóm</th><th class="px-4 py-2 text-center">Bước</th><th class="px-4 py-2">Trạng thái</th></tr></thead>
                <tbody class="divide-y divide-slate-50">
                    @foreach ($sops as $s)
                        <tr class="hover:bg-slate-50/60"><td class="px-4 py-2 font-semibold text-blue-600">{{ $s['code'] }}</td><td class="px-4 py-2 font-medium text-slate-800">{{ $s['name'] }}</td><td class="px-4 py-2 text-slate-500">{{ $s['category'] }}</td><td class="px-4 py-2 text-center text-slate-600">{{ $s['steps'] }}</td><td class="px-4 py-2"><span class="rounded-md px-2 py-0.5 text-xs font-medium {{ $stB[$s['status']][1] ?? '' }}">{{ $stB[$s['status']][0] ?? $s['status'] }}</span></td></tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3"><h3 class="font-title text-sm font-bold text-slate-900">Checklist vận hành</h3><span class="text-xs text-slate-400">{{ $checklists->count() }} checklist</span></div>
            <table class="min-w-full divide-y divide-slate-100 text-sm">
                <thead class="bg-slate-50/70 text-left text-xs font-semibold uppercase tracking-wide text-slate-500"><tr><th class="px-4 py-2">Mã</th><th class="px-4 py-2">Tên checklist</th><th class="px-4 py-2">Nhóm</th><th class="px-4 py-2 text-center">Mục</th><th class="px-4 py-2">Trạng thái</th></tr></thead>
                <tbody class="divide-y divide-slate-50">
                    @foreach ($checklists as $c)
                        <tr class="hover:bg-slate-50/60"><td class="px-4 py-2 font-semibold text-blue-600">{{ $c['code'] }}</td><td class="px-4 py-2 font-medium text-slate-800">{{ $c['name'] }}</td><td class="px-4 py-2 text-slate-500">{{ $c['category'] }}</td><td class="px-4 py-2 text-center text-slate-600">{{ $c['items'] }}</td><td class="px-4 py-2"><span class="rounded-md px-2 py-0.5 text-xs font-medium {{ $stB[$c['status']][1] ?? '' }}">{{ $stB[$c['status']][0] ?? $c['status'] }}</span></td></tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
</x-filament-panels::page>
