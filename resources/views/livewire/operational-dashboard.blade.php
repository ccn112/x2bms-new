<x-x2.page-shell>
    <x-slot:sidebar>
        <x-x2.sidebar brand="X2-BMS" tagline="Operation Center" :groups="$navGroups" />
    </x-slot:sidebar>

    <x-slot:topbar>
        <x-x2.topbar
            breadcrumb="Tổng quan"
            :buildingName="$building->name"
            :userName="$user?->name"
            :userRole="$user?->title"
            :notificationCount="18" />
    </x-slot:topbar>

    <x-slot:footer>
        <x-x2.audit-footer
            :lastActor="$lastAudit?->actor_name"
            :lastAction="$lastAudit?->description"
            :lastAt="$lastAudit?->created_at?->format('d/m/Y H:i')"
            version="v0.1-pilot" />
    </x-slot:footer>

    {{-- Greeting / action bar --}}
    <x-x2.action-bar
        title="Xin chào, {{ $user?->name }} 👋"
        subtitle="Tổng quan {{ $building->name }} · {{ now()->format('d/m/Y') }}">
        <button type="button" class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-sm font-medium text-slate-600 hover:bg-slate-50">
            Tùy chỉnh
        </button>
        <button type="button" class="rounded-lg bg-x2-primary px-3 py-1.5 text-sm font-medium text-white hover:bg-x2-primary-600">
            + Tạo nhanh
        </button>
    </x-x2.action-bar>

    {{-- KPI row --}}
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
        @foreach ($kpis as $kpi)
            <x-x2.kpi-card
                :label="$kpi['label']"
                :value="$kpi['value']"
                :sub="$kpi['sub'] ?? null"
                :accent="$kpi['accent']"
                :trend="$kpi['trend'] ?? null"
                :trendUp="$kpi['trendUp'] ?? true" />
        @endforeach
    </div>

    {{-- Middle row: fee trend / feedback donut / alerts --}}
    <div class="grid gap-4 lg:grid-cols-12">
        {{-- Tình hình thu phí (bar chart) --}}
        <x-x2.section-card title="Tình hình thu phí" subtitle="Số đã thu theo kỳ (tỷ VND)" class="lg:col-span-5">
            <div class="flex h-44 items-end gap-3 pt-2">
                @foreach ($feeTrend as $bar)
                    <div class="flex flex-1 flex-col items-center gap-1">
                        <div class="flex h-32 w-full items-end">
                            <div class="w-full rounded-t-md {{ $bar['current'] ? 'bg-x2-primary' : 'bg-x2-primary/30' }}"
                                 style="height: {{ max($bar['height'], 4) }}%"
                                 title="{{ $bar['value'] }}"></div>
                        </div>
                        <span class="text-[10px] text-slate-500">{{ $bar['label'] }}</span>
                    </div>
                @endforeach
            </div>
        </x-x2.section-card>

        {{-- Phản ánh phân loại (donut) --}}
        <x-x2.section-card title="Phản ánh phân loại" subtitle="Theo nhóm" class="lg:col-span-4">
            <div class="flex items-center gap-4">
                <div class="relative h-32 w-32 shrink-0">
                    <svg viewBox="0 0 42 42" class="h-32 w-32 -rotate-90">
                        <circle cx="21" cy="21" r="15.9155" fill="none" stroke="#f1f5f9" stroke-width="6" />
                        @foreach ($donut as $seg)
                            <circle cx="21" cy="21" r="15.9155" fill="none"
                                    stroke="{{ $seg['color'] }}" stroke-width="6"
                                    stroke-dasharray="{{ $seg['pct'] }} {{ 100 - $seg['pct'] }}"
                                    stroke-dashoffset="{{ 25 - $seg['offset'] }}" />
                        @endforeach
                    </svg>
                    <div class="absolute inset-0 flex flex-col items-center justify-center">
                        <span class="text-2xl font-bold text-slate-900">{{ $totalFeedback }}</span>
                        <span class="text-[10px] text-slate-500">phản ánh</span>
                    </div>
                </div>
                <ul class="flex-1 space-y-1.5">
                    @foreach ($donut as $seg)
                        <li class="flex items-center gap-2 text-xs">
                            <span class="h-2.5 w-2.5 rounded-full" style="background: {{ $seg['color'] }}"></span>
                            <span class="flex-1 text-slate-600">{{ $seg['name'] }}</span>
                            <span class="font-medium text-slate-800">{{ $seg['count'] }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        </x-x2.section-card>

        {{-- Cảnh báo & cần xử lý --}}
        <x-x2.section-card title="Cảnh báo & cần xử lý" class="lg:col-span-3">
            <ul class="space-y-2">
                @forelse ($alerts as $alert)
                    <li class="flex items-start gap-2">
                        <x-x2.status-badge :tone="$alert['tone']" :label="ucfirst($alert['severity'])" />
                        <span class="text-xs leading-snug text-slate-600">{{ $alert['title'] }}</span>
                    </li>
                @empty
                    <li class="py-6 text-center text-sm text-slate-400">Không có cảnh báo</li>
                @endforelse
            </ul>
        </x-x2.section-card>
    </div>

    {{-- Bottom row: work orders / department performance / AI --}}
    <div class="grid gap-4 lg:grid-cols-12">
        <x-x2.section-card title="Việc cần xử lý hôm nay" class="lg:col-span-5">
            <x-slot:action>
                <a href="#" class="text-x2-primary hover:underline">Xem tất cả</a>
            </x-slot:action>
            <x-x2.data-table
                :columns="[
                    ['key' => 'title', 'label' => 'Công việc'],
                    ['key' => 'department', 'label' => 'Bộ phận'],
                    ['key' => 'status', 'label' => 'Trạng thái'],
                ]"
                :rows="$workOrders"
                empty="Không có việc cần xử lý" />
        </x-x2.section-card>

        <x-x2.section-card title="Hiệu suất xử lý theo bộ phận" class="lg:col-span-4">
            <ul class="space-y-3">
                @foreach ($deptPerformance as $dept)
                    <li>
                        <div class="mb-1 flex items-center justify-between text-xs">
                            <span class="text-slate-600">{{ $dept['name'] }}</span>
                            <span class="font-medium text-slate-800">{{ $dept['pct'] }}%</span>
                        </div>
                        <div class="h-2 w-full overflow-hidden rounded-full bg-slate-100">
                            <div class="h-full rounded-full bg-x2-teal" style="width: {{ $dept['pct'] }}%"></div>
                        </div>
                    </li>
                @endforeach
            </ul>
        </x-x2.section-card>

        <div class="lg:col-span-3">
            <x-x2.ai-panel :suggestions="$aiSuggestions" />
        </div>
    </div>
</x-x2.page-shell>
