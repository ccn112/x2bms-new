@php
    $barColors = ['#0b1b3f', '#2563eb', '#0ea5e9', '#c8a24c', '#10b981', '#8b5cf6'];
    $maxOf = fn ($coll) => max(1, (int) collect($coll)->max('count'));
@endphp

<x-filament-panels::page>
    <x-x2.action-bar
        title="Tổng quan nội dung nền tảng"
        subtitle="Toàn cảnh content, thư viện dùng chung, tri thức, tài khoản chờ gắn căn và chỉ số AI." />

    {{-- Quick actions --}}
    <div class="flex flex-wrap gap-2">
        <a href="{{ url('/admin/platform/user-registry') }}" class="inline-flex items-center gap-1.5 rounded-lg bg-x2-navy px-3 py-2 text-sm font-medium text-white hover:opacity-90">
            <x-heroicon-m-users class="h-4 w-4" /> Tài khoản gốc
        </a>
        <a href="{{ url('/admin/platform/binding-queue') }}" class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
            <x-heroicon-m-identification class="h-4 w-4" /> Duyệt gắn căn
        </a>
        <a href="{{ url('/admin/ai/knowledge') }}" class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
            <x-heroicon-m-book-open class="h-4 w-4" /> Cơ sở tri thức
        </a>
    </div>

    {{-- KPI row --}}
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-7">
        @foreach ($kpis as $kpi)
            <x-x2.kpi-card :label="$kpi['label']" :value="$kpi['value']" :accent="$kpi['accent']" />
        @endforeach
    </div>

    {{-- Charts --}}
    <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
        <x-x2.section-card title="Nội dung theo loại">
            <ul class="space-y-3">
                @forelse ($contentByType as $i => $row)
                    <li>
                        <div class="mb-1 flex items-center justify-between text-sm">
                            <span class="font-medium text-slate-700">{{ $row['label'] }}</span>
                            <span class="tabular-nums text-slate-500">{{ $row['count'] }}</span>
                        </div>
                        <div class="h-2 overflow-hidden rounded-full bg-slate-100">
                            <div class="h-full rounded-full" style="width: {{ round($row['count'] / $maxOf($contentByType) * 100) }}%; background-color: {{ $barColors[$i % count($barColors)] }}"></div>
                        </div>
                    </li>
                @empty
                    <li class="text-sm text-slate-400">Chưa có nội dung.</li>
                @endforelse
            </ul>
        </x-x2.section-card>

        <x-x2.section-card title="KB theo phạm vi">
            <ul class="space-y-3">
                @forelse ($kbByScope as $i => $row)
                    <li>
                        <div class="mb-1 flex items-center justify-between text-sm">
                            <span class="font-medium text-slate-700">{{ $row['label'] }}</span>
                            <span class="tabular-nums text-slate-500">{{ $row['count'] }}</span>
                        </div>
                        <div class="h-2 overflow-hidden rounded-full bg-slate-100">
                            <div class="h-full rounded-full" style="width: {{ round($row['count'] / $maxOf($kbByScope) * 100) }}%; background-color: {{ $barColors[$i % count($barColors)] }}"></div>
                        </div>
                    </li>
                @empty
                    <li class="text-sm text-slate-400">Chưa có tài liệu KB.</li>
                @endforelse
            </ul>
        </x-x2.section-card>

        <x-x2.section-card title="Tài khoản mới theo tuần">
            <div class="flex h-40 items-end gap-1.5">
                @forelse ($newAccounts as $t)
                    <div class="group flex flex-1 flex-col items-center justify-end gap-1.5">
                        <span class="text-[10px] font-semibold text-slate-400 opacity-0 group-hover:opacity-100">{{ $t['count'] }}</span>
                        <div class="w-full rounded-t bg-gradient-to-t from-x2-navy to-x2-blue" style="height: {{ max(4, round($t['count'] / $maxOf($newAccounts) * 128)) }}px"></div>
                        <span class="text-[10px] text-slate-400">{{ $t['label'] }}</span>
                    </div>
                @empty
                    <p class="text-sm text-slate-400">Chưa có dữ liệu.</p>
                @endforelse
            </div>
        </x-x2.section-card>
    </div>

    {{-- Worklists --}}
    <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
        <x-x2.section-card title="Nội dung chờ duyệt">
            <ul class="divide-y divide-slate-50 text-sm">
                @forelse ($pendingContents as $c)
                    <li class="flex items-center justify-between py-2">
                        <span class="truncate text-slate-700">{{ $c->title }}</span>
                        <span class="ml-2 shrink-0 rounded bg-amber-50 px-2 py-0.5 text-xs text-amber-700">{{ $contentTypeLabels[$c->content_type] ?? $c->content_type }}</span>
                    </li>
                @empty
                    <li class="py-2 text-slate-400">Không có nội dung chờ duyệt.</li>
                @endforelse
            </ul>
        </x-x2.section-card>

        <x-x2.section-card title="Yêu cầu gắn căn chờ duyệt">
            <ul class="divide-y divide-slate-50 text-sm">
                @forelse ($pendingBindings as $b)
                    <li class="flex items-center justify-between py-2">
                        <span class="truncate text-slate-700">{{ $b->account?->full_name ?? '—' }}</span>
                        <span class="ml-2 shrink-0 text-xs text-slate-400">{{ $b->apartment?->code ?? '—' }} · {{ $b->code }}</span>
                    </li>
                @empty
                    <li class="py-2 text-slate-400">Không có yêu cầu chờ.</li>
                @endforelse
            </ul>
            @if ($pendingBindings->isNotEmpty())
                <a href="{{ url('/admin/platform/binding-queue') }}" class="mt-2 inline-block text-xs font-medium text-x2-blue hover:underline">Mở hàng đợi →</a>
            @endif
        </x-x2.section-card>

        <x-x2.section-card title="Index AI lỗi">
            <ul class="divide-y divide-slate-50 text-sm">
                @forelse ($failedDocs as $d)
                    <li class="flex items-center justify-between py-2">
                        <span class="truncate text-slate-700">{{ $d->title }}</span>
                        <span class="ml-2 shrink-0 rounded bg-red-50 px-2 py-0.5 text-xs text-red-600">lỗi</span>
                    </li>
                @empty
                    <li class="py-2 text-slate-400">Không có index lỗi.</li>
                @endforelse
            </ul>
        </x-x2.section-card>
    </div>
</x-filament-panels::page>
