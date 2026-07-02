<x-filament-panels::page>
@php $catColor = ['blue'=>'bg-blue-50 text-blue-700','amber'=>'bg-amber-50 text-amber-700','teal'=>'bg-teal-50 text-teal-700','red'=>'bg-rose-50 text-rose-700']; @endphp
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div><h1 class="font-title text-2xl font-bold text-slate-900">Knowledge Base cho BQL</h1>
            <p class="mt-1 text-sm text-slate-500">Kho tri thức, hướng dẫn và FAQ dùng chung cho các ban quản lý dự án.</p></div>
        <button class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">+ Thêm bài viết</button>
    </div>
    <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
        @foreach ($cats as $c)
            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <span class="inline-flex rounded-md px-2 py-0.5 text-xs font-medium {{ $catColor[$c->color] ?? 'bg-slate-100 text-slate-600' }}">{{ $c->name }}</span>
                <div class="mt-2 text-2xl font-bold text-slate-900">{{ $c->articles_count }}</div>
                <div class="text-xs text-slate-400">bài viết</div>
            </div>
        @endforeach
    </div>
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 px-4 py-3 font-title text-sm font-bold text-slate-900">Bài viết tri thức</div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-100 text-sm">
                <thead class="bg-slate-50/70 text-left text-xs font-semibold uppercase tracking-wide text-slate-500"><tr><th class="px-4 py-3">Tiêu đề</th><th class="px-4 py-3">Danh mục</th><th class="px-4 py-3 text-right">Lượt xem</th><th class="px-4 py-3 text-right">Hữu ích</th><th class="px-4 py-3">Ngày đăng</th><th class="px-4 py-3">Trạng thái</th></tr></thead>
                <tbody class="divide-y divide-slate-50">
                    @forelse ($articles as $a)
                        <tr class="hover:bg-slate-50/60">
                            <td class="px-4 py-3 font-medium text-slate-800">{{ $a['title'] }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-slate-500">{{ $a['category'] }}</td>
                            <td class="px-4 py-3 text-right text-slate-600">{{ number_format($a['views']) }}</td>
                            <td class="px-4 py-3 text-right text-emerald-600">{{ $a['helpful'] }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-slate-500">{{ $a['published'] }}</td>
                            <td class="whitespace-nowrap px-4 py-3"><span class="rounded-md bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-700">{{ $a['status'] === 'published' ? 'Đã đăng' : $a['status'] }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-8 text-center text-slate-400">Chưa có bài viết.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
</x-filament-panels::page>
