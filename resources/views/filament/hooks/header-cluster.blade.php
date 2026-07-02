@php
    use App\Models\Building;
    use App\Models\IocAlert;
    use App\Models\ResidentApprovalRequest;
    use App\Models\SlaEvent;
    use App\Models\Tenant;

    $user = auth()->user();
    $ctx = app(\App\Support\Context\CurrentContext::class);
    $project = $ctx->project();
    $tenant = $ctx->tenant();
    $projects = $ctx->availableProjects();
    $workspaces = $ctx->availableWorkspaces();
    $workspaceLabel = $ctx->workspaceLabel();

    // Quick-create actions (WEB-UX-00). Links resolve to /fila CRUD where built, else stubbed.
    $quickGroups = [
        ['label' => 'Vận hành', 'items' => [
            ['label' => 'Tạo phản ánh', 'icon' => 'chat', 'url' => '#'],
            ['label' => 'Tạo công việc', 'icon' => 'clipboard', 'url' => '#'],
            ['label' => 'Tạo thông báo', 'icon' => 'megaphone', 'url' => '#'],
        ]],
        ['label' => 'Tài chính', 'items' => [
            ['label' => 'Tạo kỳ phí', 'icon' => 'doc', 'url' => '#'],
            ['label' => 'Thu tiền', 'icon' => 'cash', 'url' => '#'],
        ]],
        ['label' => 'Cư dân & Cộng đồng', 'items' => [
            ['label' => 'Tạo cư dân', 'icon' => 'user', 'url' => url('/fila/residents/create')],
            ['label' => 'Tạo sự kiện', 'icon' => 'calendar', 'url' => '#'],
            ['label' => 'Tạo khảo sát', 'icon' => 'survey', 'url' => '#'],
        ]],
        ['label' => 'Nhà thầu & Kỹ thuật', 'items' => [
            ['label' => 'Thêm nhà thầu', 'icon' => 'helmet', 'url' => '#'],
            ['label' => 'Ghi nhận sự cố kỹ thuật', 'icon' => 'wrench', 'url' => '#'],
        ]],
    ];

    // Notification + approval feed (WEB-UX-09).
    $alerts = IocAlert::where('status', 'open')->latest()->take(4)->get();
    $slaOpen = SlaEvent::where('status', 'open')->count();
    $pendingApprovals = ResidentApprovalRequest::where('status', 'pending')->latest()->take(4)->get();
    $notifCount = $alerts->count() + $slaOpen;
    $approvalCount = ResidentApprovalRequest::where('status', 'pending')->count();
    $messageCount = 8; // placeholder until messaging module exists
@endphp

@php
    $icon = fn (string $k) => [
        'chat' => 'M8 10h8M8 14h5m-9 6 3.5-2.1A2 2 0 0 1 11 18h6a3 3 0 0 0 3-3V8a3 3 0 0 0-3-3H7a3 3 0 0 0-3 3v12Z',
        'clipboard' => 'M9 5h6a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H9a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2Zm0 0V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v1',
        'megaphone' => 'M3 11v2a1 1 0 0 0 1 1h2l4 4V6L6 10H4a1 1 0 0 0-1 1Zm12-5v12a4 4 0 0 0 0-12Z',
        'doc' => 'M8 3h6l4 4v14H6V5a2 2 0 0 1 2-2Zm6 0v4h4M9 13h6M9 17h6',
        'cash' => 'M3 7h18v10H3V7Zm9 2.5A2.5 2.5 0 1 1 12 14.5 2.5 2.5 0 0 1 12 9.5Z',
        'user' => 'M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm-7 8a7 7 0 0 1 14 0',
        'calendar' => 'M7 3v3m10-3v3M4 8h16M5 6h14a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1Z',
        'survey' => 'M9 5h6a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H9a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2Zm1 5 1.5 1.5L14 8m-4 6 1.5 1.5L14 12',
        'helmet' => 'M4 16h16M5 16a7 7 0 0 1 14 0M10 6a5 5 0 0 1 4 0',
        'wrench' => 'M14 7a3 3 0 0 1 4 4l-8 8-4 1 1-4 7-7a3 3 0 0 1 0-2Z',
    ][$k] ?? 'M12 6v12m6-6H6';
@endphp

<div class="flex items-center gap-1.5">
    {{-- Unified context switcher trigger (WEB-UX-03). Opens <livewire:context-switcher>:
         Công ty → Dự án → Workspace/Vai trò in one modal, permission-gated. --}}
    <button type="button" x-on:click="$dispatch('open-x2-context')"
        class="flex h-11 items-center gap-2.5 rounded-xl border border-gray-200 bg-white px-3 text-left transition hover:border-gray-300 dark:border-white/10 dark:bg-white/5">
        <svg class="h-5 w-5 shrink-0 text-x2-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7"><path stroke-linecap="round" stroke-linejoin="round" d="M3 21h18M5 21V7l7-4 7 4v14M9 9h.01M15 9h.01M9 13h.01M15 13h.01M9 17h6"/></svg>
        <span class="leading-tight">
            <span class="block max-w-[150px] truncate text-[10px] font-medium uppercase tracking-wide text-gray-400">{{ $tenant?->name ?? 'Công ty' }}</span>
            <span class="block max-w-[220px] truncate text-sm font-semibold text-gray-900 dark:text-white">{{ $project?->name ?? 'Chọn dự án' }} · {{ $workspaceLabel }}</span>
        </span>
        <svg class="h-4 w-4 shrink-0 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6"/></svg>
    </button>

    {{-- Quick create mega-menu (WEB-UX-00) --}}
    <div x-data="{ open: false }" class="relative">
        <button type="button" x-on:click="open = !open"
            class="flex h-10 items-center gap-2 rounded-xl bg-x2-gold px-3.5 text-sm font-semibold text-white shadow-sm transition hover:bg-x2-gold-600">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14"/></svg>
            Tạo mới
        </button>
        <div x-show="open" x-cloak x-transition x-on:click.outside="open = false"
            class="absolute right-0 z-50 mt-2 w-[26rem] rounded-2xl border border-gray-100 bg-white p-3 shadow-2xl dark:border-white/10 dark:bg-gray-900">
            <div class="mb-2 flex items-center gap-2 rounded-lg bg-gray-50 px-3 py-2 dark:bg-white/5">
                <svg class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.3-4.3M11 19a8 8 0 1 1 0-16 8 8 0 0 1 0 16Z"/></svg>
                <span class="text-sm text-gray-400">Tìm nhanh trong Tạo mới…</span>
            </div>
            @foreach ($quickGroups as $group)
                <div class="px-1 pb-1 pt-2 text-[11px] font-semibold uppercase tracking-wide text-gray-400">{{ $group['label'] }}</div>
                <div class="grid grid-cols-3 gap-1.5">
                    @foreach ($group['items'] as $item)
                        <a href="{{ $item['url'] }}" class="flex flex-col items-center gap-1.5 rounded-xl border border-transparent px-2 py-3 text-center transition hover:border-gray-100 hover:bg-gray-50 dark:hover:bg-white/5">
                            <span class="grid h-9 w-9 place-items-center rounded-lg bg-x2-primary/10 text-x2-primary">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon($item['icon']) }}"/></svg>
                            </span>
                            <span class="text-[11px] font-medium leading-tight text-gray-700 dark:text-gray-200">{{ $item['label'] }}</span>
                        </a>
                    @endforeach
                </div>
            @endforeach
            <a href="#" class="mt-3 flex items-center justify-center gap-2 rounded-xl bg-gray-50 py-2.5 text-sm font-medium text-x2-primary hover:bg-gray-100 dark:bg-white/5">
                Tạo nâng cao
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14m-6-6 6 6-6 6"/></svg>
            </a>
        </div>
    </div>

    {{-- Notification + approval drawer trigger (WEB-UX-09) --}}
    <div x-data="{ open: false }" class="relative">
        <button type="button" x-on:click="open = !open" title="Thông báo" aria-label="Thông báo"
            class="relative grid h-10 w-10 place-items-center rounded-xl text-gray-500 transition hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-white/5">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6"><path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.4-1.4A2 2 0 0 1 18 14.2V11a6 6 0 1 0-12 0v3.2c0 .5-.2 1-.6 1.4L4 17h5m6 0v1a3 3 0 1 1-6 0v-1m6 0H9"/></svg>
            @if ($notifCount > 0)
                <span class="absolute right-1.5 top-1.5 grid h-4 min-w-4 place-items-center rounded-full bg-x2-red px-1 text-[10px] font-semibold text-white">{{ $notifCount }}</span>
            @endif
        </button>
        <div x-show="open" x-cloak x-transition x-on:click.outside="open = false"
            class="absolute right-0 z-50 mt-2 w-96 rounded-2xl border border-gray-100 bg-white shadow-2xl dark:border-white/10 dark:bg-gray-900">
            <div class="flex items-center justify-between border-b border-gray-100 px-4 py-3 dark:border-white/10">
                <span class="text-sm font-semibold text-gray-800 dark:text-gray-100" style="font-family:'Manrope',sans-serif">Thông báo</span>
                <span class="rounded-full bg-x2-red/10 px-2 py-0.5 text-[11px] font-medium text-x2-red">{{ $notifCount }} mới</span>
            </div>
            <div class="max-h-72 overflow-y-auto py-1">
                @forelse ($alerts as $a)
                    <a href="#" class="flex items-start gap-3 px-4 py-2.5 hover:bg-gray-50 dark:hover:bg-white/5">
                        <span class="mt-0.5 grid h-8 w-8 shrink-0 place-items-center rounded-lg {{ $a->severity === 'critical' ? 'bg-x2-red/10 text-x2-red' : ($a->severity === 'warning' ? 'bg-x2-amber/10 text-x2-amber' : 'bg-x2-blue/10 text-x2-blue') }}">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.3 4.3 2.6 18a1.5 1.5 0 0 0 1.3 2.2h16.2a1.5 1.5 0 0 0 1.3-2.2L13.7 4.3a1.5 1.5 0 0 0-2.6 0Z"/></svg>
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="block truncate text-sm font-medium text-gray-800 dark:text-gray-100">{{ $a->title }}</span>
                            <span class="block text-xs text-gray-400">{{ $a->created_at?->diffForHumans() }}</span>
                        </span>
                    </a>
                @empty
                    <div class="px-4 py-6 text-center text-sm text-gray-400">Không có thông báo</div>
                @endforelse
            </div>
            <div class="border-t border-gray-100 px-4 py-2 dark:border-white/10">
                <div class="mb-1 flex items-center justify-between">
                    <span class="text-xs font-semibold uppercase tracking-wide text-gray-400">Chờ phê duyệt</span>
                    <span class="text-xs font-medium text-x2-amber">{{ $approvalCount }}</span>
                </div>
                @forelse ($pendingApprovals as $p)
                    <a href="{{ url('/admin/resident-approvals') }}" class="flex items-center justify-between rounded-lg px-2 py-1.5 hover:bg-gray-50 dark:hover:bg-white/5">
                        <span class="truncate text-sm text-gray-700 dark:text-gray-200">{{ $p->full_name }}</span>
                        <span class="text-xs text-gray-400">{{ $p->requested_role }}</span>
                    </a>
                @empty
                    <div class="py-2 text-center text-xs text-gray-400">Không có hồ sơ chờ duyệt</div>
                @endforelse
            </div>
            <a href="{{ url('/admin/resident-approvals') }}" class="block rounded-b-2xl border-t border-gray-100 py-2.5 text-center text-sm font-medium text-x2-primary hover:bg-gray-50 dark:border-white/10 dark:hover:bg-white/5">Xem tất cả</a>
        </div>
    </div>

    {{-- Messages --}}
    <button type="button" title="Tin nhắn"
        class="relative grid h-10 w-10 place-items-center rounded-xl text-gray-500 transition hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-white/5">
        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6"><path stroke-linecap="round" stroke-linejoin="round" d="M8 10h8M8 14h5m-9 6 3.5-2.1A2 2 0 0 1 11 18h6a3 3 0 0 0 3-3V8a3 3 0 0 0-3-3H7a3 3 0 0 0-3 3v12Z"/></svg>
        @if ($messageCount > 0)
            <span class="absolute right-1.5 top-1.5 grid h-4 min-w-4 place-items-center rounded-full bg-x2-teal px-1 text-[10px] font-semibold text-white">{{ $messageCount }}</span>
        @endif
    </button>

    {{-- Help --}}
    <a href="#" title="Trợ giúp"
        class="grid h-10 w-10 place-items-center rounded-xl text-gray-500 transition hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-white/5">
        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6"><path stroke-linecap="round" stroke-linejoin="round" d="M9.1 9a3 3 0 1 1 4.5 2.6c-.9.5-1.6 1.2-1.6 2.4m0 3h.01M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
    </a>

    <span class="mx-1 h-6 w-px bg-gray-200 dark:bg-white/10"></span>
</div>
