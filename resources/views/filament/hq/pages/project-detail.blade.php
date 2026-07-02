<x-filament-panels::page>
@php
    $statusRing = [
        'Đang hoạt động' => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
        'Trial' => 'bg-sky-50 text-sky-700 ring-sky-600/20',
        'Tạm ngừng' => 'bg-slate-100 text-slate-600 ring-slate-500/20',
    ];
    $lifecycle = [
        ['Khởi tạo', 'done'], ['Kích hoạt gói', 'done'], ['Bố trí BQL', 'done'],
        ['Đang vận hành', $p->status === 'active' ? 'current' : 'todo'], ['Kết thúc', 'todo'],
    ];
    $modLabel = ['x2ai'=>'X2 AI','contractor_library'=>'Nhà thầu','report_library'=>'Báo cáo','rag'=>'Tri thức RAG','supplier_library'=>'Nhà cung cấp','kb_inheritance'=>'Kế thừa KB','public_project'=>'Dự án công khai','prompt_guardrail'=>'Guardrail'];
@endphp

<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <div class="flex items-center gap-3">
                <h1 class="font-title text-2xl font-bold text-slate-900">{{ $p->code }} — {{ $p->name }}</h1>
                <span class="inline-flex items-center rounded-md px-2 py-0.5 text-xs font-medium ring-1 ring-inset {{ $statusRing[$statusLabel] ?? '' }}">{{ $statusLabel }}</span>
                @if ($planName)
                    <span class="inline-flex items-center rounded-md bg-violet-50 px-2 py-0.5 text-xs font-medium text-violet-700 ring-1 ring-inset ring-violet-600/20">{{ $planName }}</span>
                @endif
            </div>
            <p class="mt-1 text-sm text-slate-500">{{ $p->address }}{{ $p->city ? ', '.$p->city : '' }} · Trưởng BQL: {{ $manager?->user?->name ?? '—' }}</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ url('/hq/projects/'.$p->id.'/bql') }}" class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Thiết lập BQL</a>
            <a href="{{ url('/hq/projects/'.$p->id.'/package') }}" class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Quản lý gói dịch vụ</a>
            <a href="{{ url('/hq/projects/'.$p->id.'/modules') }}" class="rounded-lg bg-blue-600 px-3 py-2 text-sm font-semibold text-white hover:bg-blue-700">Trạng thái module</a>
        </div>
    </div>

    {{-- Lifecycle path --}}
    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="flex flex-wrap items-center gap-2">
            @foreach ($lifecycle as $idx => [$label, $state])
                <div class="flex items-center gap-2">
                    <span @class([
                        'grid h-8 w-8 place-items-center rounded-full text-sm font-bold',
                        'bg-emerald-500 text-white' => $state === 'done',
                        'bg-blue-600 text-white ring-4 ring-blue-100' => $state === 'current',
                        'bg-slate-100 text-slate-400' => $state === 'todo',
                    ])>{{ $state === 'done' ? '✓' : $idx + 1 }}</span>
                    <span @class(['text-sm font-medium', 'text-slate-800' => $state !== 'todo', 'text-slate-400' => $state === 'todo'])>{{ $label }}</span>
                </div>
                @if (! $loop->last)<div class="h-px w-8 bg-slate-200"></div>@endif
            @endforeach
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-[1fr_320px]">
        <div class="space-y-6">
            {{-- Project info + BQL --}}
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h3 class="font-title text-sm font-bold text-slate-900">Thông tin dự án</h3>
                    <dl class="mt-3 space-y-2.5 text-sm">
                        <div class="flex justify-between"><dt class="text-slate-500">Mã dự án</dt><dd class="font-medium text-slate-800">{{ $p->code }}</dd></div>
                        <div class="flex justify-between"><dt class="text-slate-500">Loại hình</dt><dd class="font-medium text-slate-800">{{ $p->type }}</dd></div>
                        <div class="flex justify-between"><dt class="text-slate-500">Quy mô</dt><dd class="font-medium text-slate-800">{{ $p->building_count }} tòa · {{ number_format($p->apartment_count) }} căn</dd></div>
                        <div class="flex justify-between"><dt class="text-slate-500">Diện tích</dt><dd class="font-medium text-slate-800">{{ number_format($p->land_area_sqm) }} m²</dd></div>
                        <div class="flex justify-between"><dt class="text-slate-500">Hotline</dt><dd class="font-medium text-slate-800">{{ $team?->hotline ?? '—' }}</dd></div>
                        <div class="flex justify-between"><dt class="text-slate-500">Ngày bắt đầu gói</dt><dd class="font-medium text-slate-800">{{ optional($period?->started_at)->format('d/m/Y') ?? '—' }}</dd></div>
                    </dl>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h3 class="font-title text-sm font-bold text-slate-900">BQL & liên hệ</h3>
                    <div class="mt-3 space-y-1 text-sm">
                        <div class="font-semibold text-slate-800">{{ $manager?->user?->name ?? '—' }}</div>
                        <div class="text-slate-500">Trưởng BQL · {{ $manager?->phone ?? '—' }}</div>
                    </div>
                    <div class="mt-4 rounded-xl bg-slate-50 p-3 text-sm">
                        <div class="font-medium text-slate-700">{{ $team?->name ?? 'Ban quản lý' }}</div>
                        <div class="mt-1 text-slate-500">Số nhân sự: {{ $assignments->count() }} người</div>
                    </div>
                </div>
            </div>

            {{-- Tabs (Nhân sự dự án) --}}
            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm" x-data="{ tab: 'staff' }">
                <div class="flex gap-1 border-b border-slate-100 px-4 pt-3">
                    @foreach (['staff' => 'Nhân sự dự án', 'package' => 'Gói dịch vụ', 'audit' => 'Audit log'] as $k => $l)
                        <button @click="tab = '{{ $k }}'" :class="tab === '{{ $k }}' ? 'border-blue-600 text-blue-700' : 'border-transparent text-slate-500'"
                                class="border-b-2 px-3 pb-2 text-sm font-medium">{{ $l }}</button>
                    @endforeach
                </div>
                <div class="p-4">
                    <div x-show="tab === 'staff'" class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-100 text-sm">
                            <thead class="text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                <tr><th class="px-3 py-2">Họ và tên</th><th class="px-3 py-2">Chức danh</th><th class="px-3 py-2">Phòng ban</th><th class="px-3 py-2">Vai trò</th></tr>
                            </thead>
                            <tbody class="divide-y divide-slate-50">
                                @forelse ($assignments as $a)
                                    <tr>
                                        <td class="px-3 py-2 font-medium text-slate-800">{{ $a['name'] }}</td>
                                        <td class="px-3 py-2 text-slate-500">{{ $a['position'] }}</td>
                                        <td class="px-3 py-2 text-slate-500">{{ $a['dept'] }}</td>
                                        <td class="px-3 py-2"><span class="rounded-md bg-slate-100 px-2 py-0.5 text-xs text-slate-600">{{ $a['type'] === 'primary' ? 'Chính' : ($a['type'] === 'secondary' ? 'Phụ' : 'Tạm thời') }}</span></td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="px-3 py-6 text-center text-slate-400">Chưa có nhân sự.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div x-show="tab === 'package'" x-cloak class="text-sm text-slate-600">
                        Gói hiện tại: <span class="font-semibold text-slate-800">{{ $planName ?? '—' }}</span> ·
                        Hiệu lực {{ optional($period?->current_period_start)->format('d/m/Y') }} → {{ optional($period?->current_period_end)->format('d/m/Y') }}.
                    </div>
                    <div x-show="tab === 'audit'" x-cloak class="text-sm text-slate-400">Nhật ký thay đổi dự án hiển thị tại đây.</div>
                </div>
            </div>
        </div>

        {{-- Right --}}
        <div class="space-y-4">
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h3 class="font-title text-sm font-bold text-slate-900">KPI nhanh</h3>
                <div class="mt-3 grid grid-cols-2 gap-3">
                    <div class="rounded-xl bg-blue-50 p-3"><div class="text-xs text-slate-500">Căn hộ</div><div class="text-lg font-bold text-slate-900">{{ number_format($p->apartment_count) }}</div></div>
                    <div class="rounded-xl bg-emerald-50 p-3"><div class="text-xs text-slate-500">Tòa nhà</div><div class="text-lg font-bold text-slate-900">{{ $p->building_count }}</div></div>
                    <div class="rounded-xl bg-amber-50 p-3"><div class="text-xs text-slate-500">Nhân sự BQL</div><div class="text-lg font-bold text-slate-900">{{ $assignments->count() }}</div></div>
                    <div class="rounded-xl bg-violet-50 p-3"><div class="text-xs text-slate-500">Diện tích</div><div class="text-lg font-bold text-slate-900">{{ number_format($p->land_area_sqm) }}</div></div>
                </div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h3 class="font-title text-sm font-bold text-slate-900">Gói dịch vụ</h3>
                <dl class="mt-3 space-y-2 text-sm">
                    <div class="flex justify-between"><dt class="text-slate-500">Gói hiện tại</dt><dd class="font-semibold text-slate-800">{{ $planName ?? '—' }}</dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500">Ngày hết hạn</dt><dd class="font-medium text-slate-800">{{ optional($period?->current_period_end)->format('d/m/Y') ?? '—' }}</dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500">Tự động gia hạn</dt><dd class="font-medium text-slate-800">{{ $period?->auto_renew ? 'Có' : 'Không' }}</dd></div>
                </dl>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h3 class="font-title text-sm font-bold text-slate-900">Trạng thái module</h3>
                <ul class="mt-3 space-y-2 text-sm">
                    @forelse ($modules as $m)
                        <li class="flex items-center justify-between">
                            <span class="text-slate-600">{{ $modLabel[$m['key']] ?? $m['key'] }}</span>
                            <span @class([
                                'rounded-md px-2 py-0.5 text-xs font-medium',
                                'bg-emerald-50 text-emerald-700' => $m['status'] === 'enabled',
                                'bg-amber-50 text-amber-700' => $m['status'] === 'pending',
                                'bg-slate-100 text-slate-500' => $m['status'] === 'locked' || $m['status'] === 'disabled',
                            ])>{{ ['enabled'=>'Bật','disabled'=>'Tắt','pending'=>'Chờ duyệt','locked'=>'Khóa'][$m['status']] ?? $m['status'] }}</span>
                        </li>
                    @empty
                        <li class="text-slate-400">Dùng đúng theo gói, không có override.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
</div>
</x-filament-panels::page>
