<x-x2.admin-shell active="apartments" breadcrumb="Cư dân & Căn hộ / Hồ sơ căn hộ / {{ $apartment->code }}">
    <x-x2.action-bar :title="'Căn hộ '.$apartment->code" :subtitle="($apartment->floor?->name ?? '').' · '.number_format((float) $apartment->area_sqm, 0).' m² · '.$apartment->building?->name">
        <a href="{{ url('/apartments') }}" class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-sm text-slate-600 hover:bg-slate-50">← Danh sách</a>
        <a href="{{ url('/admin/apartments/'.$apartment->id.'/edit') }}" class="rounded-lg bg-x2-primary px-3 py-1.5 text-sm font-medium text-white hover:bg-x2-primary-600">Chỉnh sửa</a>
    </x-x2.action-bar>

    <div class="grid gap-4 lg:grid-cols-12">
        {{-- Left: detail sections --}}
        <div class="space-y-4 lg:col-span-8">
            <x-x2.section-card title="Thông tin căn hộ">
                <dl class="grid grid-cols-2 gap-x-6 gap-y-3 text-sm sm:grid-cols-3">
                    <div><dt class="text-xs text-slate-500">Mã căn</dt><dd class="font-medium text-slate-800">{{ $apartment->code }}</dd></div>
                    <div><dt class="text-xs text-slate-500">Tầng</dt><dd class="text-slate-700">{{ $apartment->floor?->name ?? '—' }}</dd></div>
                    <div><dt class="text-xs text-slate-500">Diện tích</dt><dd class="text-slate-700">{{ number_format((float) $apartment->area_sqm, 0) }} m²</dd></div>
                    <div><dt class="text-xs text-slate-500">Chủ sở hữu</dt><dd class="font-medium text-slate-800">{{ $owner?->full_name ?? '—' }}</dd></div>
                    <div><dt class="text-xs text-slate-500">SĐT chủ hộ</dt><dd class="text-slate-700">{{ $owner?->phone ?? '—' }}</dd></div>
                    <div><dt class="text-xs text-slate-500">Trạng thái</dt><dd><x-x2.status-badge label="Đang ở" tone="green" /></dd></div>
                </dl>
            </x-x2.section-card>

            <x-x2.section-card title="Người trong hộ" :subtitle="count($members).' thành viên'">
                <x-x2.data-table
                    :columns="[['key'=>'name','label'=>'Họ tên'],['key'=>'phone','label'=>'SĐT'],['key'=>'role','label'=>'Vai trò']]"
                    :rows="$members" empty="Chưa có thành viên" />
            </x-x2.section-card>

            <x-x2.section-card title="Phương tiện" :subtitle="count($vehicles).' phương tiện'">
                <x-x2.data-table
                    :columns="[['key'=>'plate','label'=>'Biển số'],['key'=>'type','label'=>'Loại'],['key'=>'card','label'=>'Thẻ gửi xe'],['key'=>'fee','label'=>'Phí/tháng']]"
                    :rows="$vehicles" empty="Không có phương tiện" />
            </x-x2.section-card>

            <x-x2.section-card title="Thẻ ra vào" :subtitle="count($cards).' thẻ'">
                <x-x2.data-table
                    :columns="[['key'=>'no','label'=>'Mã thẻ'],['key'=>'type','label'=>'Loại'],['key'=>'valid','label'=>'Hiệu lực đến'],['key'=>'status','label'=>'Trạng thái']]"
                    :rows="$cards" empty="Không có thẻ" />
            </x-x2.section-card>
        </div>

        {{-- Right rail --}}
        <div class="space-y-4 lg:col-span-4">
            <x-x2.section-card title="Tình hình thu phí">
                @if ($collectionRate !== null)
                    <div class="flex flex-col items-center py-2">
                        <div class="relative h-28 w-28">
                            <svg viewBox="0 0 42 42" class="h-28 w-28 -rotate-90">
                                <circle cx="21" cy="21" r="15.9155" fill="none" stroke="#f1f5f9" stroke-width="6" />
                                <circle cx="21" cy="21" r="15.9155" fill="none" stroke="#16a34a" stroke-width="6"
                                        stroke-dasharray="{{ $collectionRate }} {{ 100 - $collectionRate }}" stroke-dashoffset="25" />
                            </svg>
                            <div class="absolute inset-0 flex flex-col items-center justify-center">
                                <span class="text-xl font-bold text-slate-900">{{ $collectionRate }}%</span>
                                <span class="text-[10px] text-slate-500">đã thu</span>
                            </div>
                        </div>
                    </div>
                @else
                    <p class="py-6 text-center text-sm text-slate-400">Chưa phát hành bảng kê cho căn này</p>
                @endif
                <div class="mt-2 flex items-center justify-between rounded-lg bg-slate-50 px-3 py-2 text-sm">
                    <span class="text-slate-600">Công nợ quá hạn</span>
                    <span class="font-semibold {{ $overdueDebt > 0 ? 'text-x2-red' : 'text-x2-green' }}">
                        {{ $overdueDebt > 0 ? number_format($overdueDebt / 1e6, 0).' tr' : '0 đ' }}
                    </span>
                </div>
            </x-x2.section-card>

            <x-x2.ai-panel :suggestions="$aiSuggestions" />
        </div>
    </div>
</x-x2.admin-shell>
