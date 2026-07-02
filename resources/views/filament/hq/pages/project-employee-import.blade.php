<x-filament-panels::page>
@php
    $steps = ['Tải lên', 'Ánh xạ cột', 'Kiểm tra', 'Xác nhận', 'Hoàn tất'];
    $stateStep = ['uploaded'=>1,'mapped'=>2,'validated'=>3,'committed'=>5,'failed'=>3,'cancelled'=>1][$batch?->status ?? 'uploaded'] ?? 1;
    $vmeta = [
        'valid' => ['Hợp lệ', 'bg-emerald-50 text-emerald-700'],
        'warning' => ['Cảnh báo', 'bg-amber-50 text-amber-700'],
        'error' => ['Lỗi', 'bg-rose-50 text-rose-700'],
        'skipped' => ['Bỏ qua', 'bg-slate-100 text-slate-500'],
        'imported' => ['Đã nhập', 'bg-blue-50 text-blue-700'],
    ];
@endphp
<div class="space-y-6">
    <div>
        <h1 class="font-title text-2xl font-bold text-slate-900">Import dự án & nhân sự</h1>
        <p class="mt-1 text-sm text-slate-500">Nhập hàng loạt dự án và nhân sự từ file Excel với kiểm tra hợp lệ theo từng dòng.</p>
    </div>

    {{-- Stepper --}}
    <div class="flex flex-wrap items-center gap-2 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        @foreach ($steps as $i => $s)
            <div class="flex items-center gap-2">
                <span @class([
                    'grid h-7 w-7 place-items-center rounded-full text-xs font-bold',
                    'bg-emerald-500 text-white' => $stateStep > $i + 1,
                    'bg-blue-600 text-white' => $stateStep === $i + 1,
                    'bg-slate-100 text-slate-400' => $stateStep < $i + 1,
                ])>{{ $stateStep > $i + 1 ? '✓' : $i + 1 }}</span>
                <span @class(['text-sm font-medium', 'text-slate-800' => $stateStep >= $i + 1, 'text-slate-400' => $stateStep < $i + 1])>{{ $s }}</span>
                @if (! $loop->last)<span class="mx-1 h-px w-6 bg-slate-200"></span>@endif
            </div>
        @endforeach
    </div>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-[1fr_300px]">
        <div class="space-y-4">
            {{-- Upload dropzone --}}
            <div class="rounded-2xl border-2 border-dashed border-slate-200 bg-white p-8 text-center shadow-sm">
                <svg class="mx-auto h-10 w-10 text-slate-300" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5"/></svg>
                <p class="mt-2 text-sm text-slate-500">Kéo thả file .xlsx vào đây hoặc <span class="font-semibold text-blue-600">chọn file</span></p>
                <p class="mt-1 text-xs text-slate-400">Hỗ trợ mẫu import dự án & nhân sự (tối đa 25MB)</p>
            </div>

            {{-- Preview --}}
            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-100 px-4 py-3 font-title text-sm font-bold text-slate-900">Xem trước dữ liệu</div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-100 text-sm">
                        <thead class="bg-slate-50/70 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                            <tr><th class="px-4 py-2">Dòng</th><th class="px-4 py-2">Loại</th><th class="px-4 py-2">Mã</th><th class="px-4 py-2">Kiểm tra</th><th class="px-4 py-2">Ghi chú lỗi</th></tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            @forelse ($rows as $r)
                                <tr>
                                    <td class="px-4 py-2 text-slate-500">{{ $r->row_number }}</td>
                                    <td class="px-4 py-2 text-slate-600">{{ ['project'=>'Dự án','employee'=>'Nhân sự','assignment'=>'Phân công'][$r->row_type] ?? $r->row_type }}</td>
                                    <td class="px-4 py-2 font-medium text-slate-800">{{ $r->external_code }}</td>
                                    <td class="px-4 py-2"><span class="rounded-md px-2 py-0.5 text-xs font-medium {{ $vmeta[$r->validation_status][1] ?? '' }}">{{ $vmeta[$r->validation_status][0] ?? $r->validation_status }}</span></td>
                                    <td class="px-4 py-2 text-xs text-rose-600">{{ $r->validation_errors ? implode('; ', array_values($r->validation_errors)) : '' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="px-4 py-8 text-center text-slate-400">Chưa có dữ liệu import. Tải file lên để bắt đầu.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- File info --}}
        <div class="space-y-4">
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h3 class="font-title text-sm font-bold text-slate-900">Thông tin file</h3>
                <dl class="mt-3 space-y-2.5 text-sm">
                    <div class="flex justify-between"><dt class="text-slate-500">Tên file</dt><dd class="max-w-[150px] truncate font-medium text-slate-800">{{ $batch?->file_name ?? '—' }}</dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500">Tổng dòng</dt><dd class="font-semibold text-slate-900">{{ $batch?->total_rows ?? 0 }}</dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500">Hợp lệ</dt><dd class="font-semibold text-emerald-600">{{ $batch?->valid_rows ?? 0 }}</dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500">Lỗi</dt><dd class="font-semibold text-rose-600">{{ $batch?->error_rows ?? 0 }}</dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500">Trạng thái</dt><dd><span class="rounded-md bg-blue-50 px-2 py-0.5 text-xs font-medium text-blue-700">{{ $batch?->status ?? '—' }}</span></dd></div>
                </dl>
                <button class="mt-4 w-full rounded-lg bg-blue-600 px-3 py-2 text-sm font-semibold text-white hover:bg-blue-700 disabled:bg-slate-200 disabled:text-slate-400" @disabled(! $batch || $batch->valid_rows === 0)>
                    Nhập {{ $batch?->valid_rows ?? 0 }} dòng hợp lệ
                </button>
            </div>
        </div>
    </div>
</div>
</x-filament-panels::page>
