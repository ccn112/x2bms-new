<x-filament-panels::page>
    <x-x2.action-bar
        title="Cơ sở tri thức hỗ trợ (KB)"
        subtitle="Tài liệu nội bộ X2AI dùng để trả lời cư dân & hỗ trợ BQL. Quản lý danh mục, bài viết và độ hữu ích." />

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ($kpis as $kpi)
            <x-x2.kpi-card :label="$kpi['label']" :value="$kpi['value']" :accent="$kpi['accent']" />
        @endforeach
    </div>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-12">
        {{-- LEFT: article table --}}
        <div class="xl:col-span-8">
            <div class="rounded-2xl border border-slate-100 bg-white p-1 shadow-sm">
                {{ $this->table }}
            </div>
        </div>

        {{-- RIGHT: categories + support copilot --}}
        <aside class="space-y-6 xl:col-span-4">
            <x-x2.section-card title="Danh mục" subtitle="Số bài theo từng nhóm">
                <ul class="space-y-2">
                    @foreach ($categories as $cat)
                        <li class="flex items-center justify-between gap-3 rounded-lg px-2 py-1.5 hover:bg-slate-50">
                            <span class="flex items-center gap-2.5">
                                <span class="grid h-8 w-8 place-items-center rounded-lg" style="background-color: {{ $cat->color }}1a; color: {{ $cat->color }}">
                                    <x-filament::icon :icon="$cat->icon ?? 'heroicon-o-folder'" class="h-4 w-4" />
                                </span>
                                <span class="text-sm font-medium text-slate-700">{{ $cat->name }}</span>
                            </span>
                            <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-semibold tabular-nums text-slate-600">{{ $cat->articles_count }}</span>
                        </li>
                    @endforeach
                </ul>
            </x-x2.section-card>

            <x-x2.section-card title="Bài xem nhiều">
                <ol class="space-y-2.5">
                    @foreach ($topArticles as $i => $art)
                        <li class="flex items-start gap-2.5 text-sm">
                            <span class="grid h-5 w-5 shrink-0 place-items-center rounded-full bg-x2-navy/5 text-[11px] font-bold text-x2-navy">{{ $i + 1 }}</span>
                            <div class="min-w-0">
                                <div class="truncate text-slate-700">{{ $art->title }}</div>
                                <div class="text-xs text-slate-400">{{ number_format($art->views) }} lượt xem</div>
                            </div>
                        </li>
                    @endforeach
                </ol>
            </x-x2.section-card>

            {{-- X2AI Support Copilot → shared floating chat --}}
            <div class="rounded-2xl border border-x2-gold/30 bg-gradient-to-br from-x2-navy to-x2-blue p-5 text-white shadow-sm">
                <div class="flex items-center gap-2">
                    <span class="grid h-9 w-9 place-items-center rounded-lg bg-white/15">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.3 6.2L22 12l-6.7 2.8L13 21l-2.3-6.2L4 12l6.7-2.8L13 3z"/></svg>
                    </span>
                    <h3 class="font-title text-base font-semibold">X2AI Support Copilot</h3>
                </div>
                <p class="mt-2 text-sm text-white/80">Hỏi đáp dựa trên cơ sở tri thức này. Trợ lý sẽ trích dẫn tài liệu liên quan.</p>
                <button type="button"
                        onclick="window.dispatchEvent(new CustomEvent('x2ai-open'))"
                        class="mt-4 inline-flex items-center gap-2 rounded-lg bg-x2-gold px-4 py-2 text-sm font-semibold text-x2-navy transition hover:bg-x2-gold/90">
                    Mở trợ lý
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                </button>
            </div>
        </aside>
    </div>
</x-filament-panels::page>
