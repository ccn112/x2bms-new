<x-filament-panels::page>
<div class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="font-title text-2xl font-bold text-slate-900">Phân công nhân sự vào dự án</h1>
            <p class="mt-1 text-sm text-slate-500">Chọn dự án, chọn nhân sự khả dụng và cấu hình phân công.</p>
        </div>
        <label class="flex items-center gap-2 text-sm">
            <span class="text-slate-500">Dự án:</span>
            <select wire:model.live="projectId" class="rounded-lg border-slate-200 text-sm font-medium focus:border-blue-500 focus:ring-blue-500">
                @foreach ($projects as $p)
                    <option value="{{ $p->id }}">{{ $p->code }} — {{ $p->name }}</option>
                @endforeach
            </select>
        </label>
    </div>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-[1fr_320px]">
        <div class="space-y-6">
            {{-- Available staff --}}
            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-100 px-4 py-3 font-title text-sm font-bold text-slate-900">Nhân sự khả dụng</div>
                <div class="max-h-80 overflow-y-auto">
                    <table class="min-w-full divide-y divide-slate-100 text-sm">
                        <thead class="sticky top-0 bg-slate-50/90 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                            <tr><th class="px-4 py-2">Mã</th><th class="px-4 py-2">Họ tên</th><th class="px-4 py-2">Chức danh</th><th class="px-4 py-2">Phòng ban</th><th class="px-4 py-2"></th></tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            @forelse ($available as $s)
                                <tr @class(['hover:bg-blue-50/40', 'bg-blue-50' => $employeeId === $s['id']])>
                                    <td class="px-4 py-2 font-medium text-blue-600">{{ $s['code'] }}</td>
                                    <td class="px-4 py-2 text-slate-800">{{ $s['name'] }}</td>
                                    <td class="px-4 py-2 text-slate-500">{{ $s['position'] }}</td>
                                    <td class="px-4 py-2 text-slate-500">{{ $s['dept'] }}</td>
                                    <td class="px-4 py-2 text-right">
                                        <button wire:click="$set('employeeId', {{ $s['id'] }})" class="rounded-md px-2 py-1 text-xs font-semibold {{ $employeeId === $s['id'] ? 'bg-blue-600 text-white' : 'text-blue-600 hover:bg-blue-50' }}">Chọn</button>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="px-4 py-8 text-center text-slate-400">Tất cả nhân sự đã được phân công vào dự án này.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Current assignments --}}
            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-100 px-4 py-3 font-title text-sm font-bold text-slate-900">Phân công hiện tại — {{ $selected?->name }}</div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-100 text-sm">
                        <thead class="bg-slate-50/70 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                            <tr><th class="px-4 py-2">Họ tên</th><th class="px-4 py-2">Chức danh</th><th class="px-4 py-2">Phòng ban</th><th class="px-4 py-2">Loại</th><th class="px-4 py-2">Workload</th></tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            @forelse ($current as $a)
                                <tr>
                                    <td class="px-4 py-2 font-medium text-slate-800">{{ $a['name'] }}</td>
                                    <td class="px-4 py-2 text-slate-500">{{ $a['position'] }}</td>
                                    <td class="px-4 py-2 text-slate-500">{{ $a['dept'] }}</td>
                                    <td class="px-4 py-2"><span class="rounded-md bg-slate-100 px-2 py-0.5 text-xs text-slate-600">{{ $a['type'] === 'primary' ? 'Chính' : ($a['type'] === 'secondary' ? 'Phụ' : 'Tạm thời') }}</span></td>
                                    <td class="px-4 py-2 text-slate-500">{{ $a['workload'] }}%</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="px-4 py-8 text-center text-slate-400">Chưa có nhân sự.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Assignment config --}}
        <div class="space-y-4">
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h3 class="font-title text-sm font-bold text-slate-900">Cấu hình phân công</h3>
                <div class="mt-3 space-y-3 text-sm">
                    <div class="rounded-lg bg-slate-50 px-3 py-2">
                        <span class="text-slate-500">Nhân sự đã chọn:</span>
                        <div class="font-semibold text-slate-800">{{ $available->firstWhere('id', $employeeId)['name'] ?? 'Chưa chọn' }}</div>
                    </div>
                    <label class="block"><span class="text-slate-500">Loại phân công</span>
                        <select wire:model="assignmentType" class="mt-1 w-full rounded-lg border-slate-200 text-sm">
                            <option value="primary">Chính</option><option value="secondary">Phụ</option><option value="temporary">Tạm thời</option>
                        </select></label>
                    <label class="block"><span class="text-slate-500">Workload (%)</span>
                        <input type="number" wire:model="workload" min="1" max="100" class="mt-1 w-full rounded-lg border-slate-200 text-sm"></label>
                    <button wire:click="assign" @disabled(! $employeeId)
                            class="w-full rounded-lg bg-blue-600 px-3 py-2 text-sm font-semibold text-white hover:bg-blue-700 disabled:cursor-not-allowed disabled:bg-slate-200 disabled:text-slate-400">
                        Phân công vào dự án
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
</x-filament-panels::page>
