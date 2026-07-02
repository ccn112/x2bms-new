<x-filament-panels::page>
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="font-title text-2xl font-bold text-slate-900">Thiết lập BQL — {{ $project->name }}</h1>
            <p class="mt-1 text-sm text-slate-500">Bố trí nhân sự ban quản lý, kênh liên hệ và định biên theo phòng ban.</p>
        </div>
        <a href="{{ url('/hq/projects/'.$project->id) }}" class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50">← Chi tiết dự án</a>
    </div>

    @if ($understaffed)
        <div class="flex items-center gap-2 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z"/></svg>
            BQL dự án đang thiếu nhân sự — cần bổ sung để đảm bảo vận hành.
        </div>
    @endif

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-[1fr_320px]">
        <div class="space-y-6">
            {{-- Headcount cards --}}
            <div class="grid grid-cols-2 gap-4 md:grid-cols-3">
                @foreach ($depts as $d)
                    @php $ok = $d['current'] >= $d['required']; @endphp
                    <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                        <div class="text-sm font-medium text-slate-600">{{ $d['name'] }}</div>
                        <div class="mt-1 flex items-baseline gap-1">
                            <span class="text-2xl font-bold {{ $ok ? 'text-slate-900' : 'text-rose-600' }}">{{ $d['current'] }}</span>
                            <span class="text-sm text-slate-400">/ {{ $d['required'] }} yêu cầu</span>
                        </div>
                        <div class="mt-2 h-1.5 w-full overflow-hidden rounded-full bg-slate-100">
                            <div class="h-full rounded-full {{ $ok ? 'bg-emerald-500' : 'bg-rose-400' }}" style="width: {{ min(100, $d['required'] ? round($d['current']/$d['required']*100) : 100) }}%"></div>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- BQL staff table --}}
            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-100 px-4 py-3 font-title text-sm font-bold text-slate-900">Nhân sự BQL</div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-100 text-sm">
                        <thead class="bg-slate-50/70 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                            <tr><th class="px-4 py-2">Họ tên</th><th class="px-4 py-2">Chức danh</th><th class="px-4 py-2">Phòng ban</th><th class="px-4 py-2">SĐT</th><th class="px-4 py-2">Loại</th></tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            @forelse ($assignments as $a)
                                <tr>
                                    <td class="px-4 py-2 font-medium text-slate-800">{{ $a['name'] }}</td>
                                    <td class="px-4 py-2 text-slate-500">{{ $a['position'] }}</td>
                                    <td class="px-4 py-2 text-slate-500">{{ $a['dept'] }}</td>
                                    <td class="px-4 py-2 text-slate-500">{{ $a['phone'] }}</td>
                                    <td class="px-4 py-2"><span class="rounded-md bg-slate-100 px-2 py-0.5 text-xs text-slate-600">{{ $a['type'] === 'primary' ? 'Chính' : ($a['type'] === 'secondary' ? 'Phụ' : 'Tạm thời') }}</span></td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="px-4 py-8 text-center text-slate-400">Chưa bố trí nhân sự.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Contact + manager --}}
        <div class="space-y-4">
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h3 class="font-title text-sm font-bold text-slate-900">Trưởng BQL</h3>
                <div class="mt-3 text-sm">
                    <div class="font-semibold text-slate-800">{{ $manager?->user?->name ?? '—' }}</div>
                    <div class="text-slate-500">{{ $manager?->phone ?? '—' }}</div>
                </div>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h3 class="font-title text-sm font-bold text-slate-900">Kênh liên hệ</h3>
                <dl class="mt-3 space-y-2 text-sm">
                    <div class="flex justify-between"><dt class="text-slate-500">Hotline</dt><dd class="font-medium text-slate-800">{{ $team?->hotline ?? '—' }}</dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500">Email</dt><dd class="font-medium text-slate-800">{{ $team?->email ?? '—' }}</dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500">Địa chỉ</dt><dd class="font-medium text-slate-800 text-right">{{ $team?->address ?? '—' }}</dd></div>
                </dl>
            </div>
        </div>
    </div>
</div>
</x-filament-panels::page>
