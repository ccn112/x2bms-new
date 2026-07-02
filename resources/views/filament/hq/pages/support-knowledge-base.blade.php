<x-filament-panels::page>
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div><h1 class="font-title text-2xl font-bold text-slate-900">Cơ sở tri thức hỗ trợ</h1>
            <p class="mt-1 text-sm text-slate-500">Bài viết hướng dẫn & FAQ hỗ trợ dùng chung cho đội ngũ và khách hàng.</p></div>
        <button class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">+ Thêm bài viết</button>
    </div>
    <div class="grid grid-cols-2 gap-4 md:grid-cols-4 xl:grid-cols-6">
        @foreach ($cats as $c)
            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <div class="text-sm font-medium text-slate-700">{{ $c->name }}</div>
                <div class="mt-1 text-xl font-bold text-slate-900">{{ $c->articles_count }}</div>
                <div class="text-xs text-slate-400">bài viết</div>
            </div>
        @endforeach
    </div>
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 px-4 py-3 font-title text-sm font-bold text-slate-900">Bài viết hỗ trợ</div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-100 text-sm">
                <thead class="bg-slate-50/70 text-left text-xs font-semibold uppercase tracking-wide text-slate-500"><tr><th class="px-4 py-3">Mã</th><th class="px-4 py-3">Tiêu đề</th><th class="px-4 py-3 text-right">Đánh giá</th><th class="px-4 py-3 text-right">Lượt xem</th><th class="px-4 py-3">Ngày đăng</th></tr></thead>
                <tbody class="divide-y divide-slate-50">
                    @forelse ($articles as $a)
                        <tr class="hover:bg-slate-50/60">
                            <td class="whitespace-nowrap px-4 py-3 font-semibold text-blue-600">{{ $a['code'] }}</td>
                            <td class="px-4 py-3 font-medium text-slate-800">{{ $a['title'] }}</td>
                            <td class="px-4 py-3 text-right text-amber-600">★ {{ number_format($a['rating'],1) }}</td>
                            <td class="px-4 py-3 text-right text-slate-500">{{ number_format($a['views']) }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-slate-500">{{ $a['published'] }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-8 text-center text-slate-400">Chưa có bài viết.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
</x-filament-panels::page>
