@props([
    'empty' => false,           // true → render empty state instead of rows
    'emptyText' => 'Không có dữ liệu phù hợp.',
    'emptyIcon' => 'heroicon-o-inbox',
    'loading' => false,
    'sticky' => false,          // sticky header for long tables
])

{{-- DS-01 data table shell (bespoke Blade). Professional/compact density, row
     height ~56px. Slots: `head` (thead <th> cells), default (tbody <tr> rows),
     `emptyActions`, `footer` (pagination + result count). States: default/empty/loading. --}}
<div {{ $attributes->class(['overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm']) }}>
    <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
            @isset($head)
                <thead @class(['bg-slate-50/80 text-slate-500', 'sticky top-0 z-10' => $sticky])>
                    <tr class="text-left text-[13px] font-semibold">
                        {{ $head }}
                    </tr>
                </thead>
            @endisset
            <tbody class="divide-y divide-slate-100 text-slate-700">
                @if ($loading)
                    @for ($i = 0; $i < 5; $i++)
                        <tr class="animate-pulse">
                            <td colspan="99" class="px-4 py-4"><div class="h-4 w-full rounded bg-slate-100"></div></td>
                        </tr>
                    @endfor
                @elseif ($empty)
                    <tr>
                        <td colspan="99" class="px-4 py-16">
                            <div class="flex flex-col items-center justify-center text-center">
                                <span class="mb-3 grid h-12 w-12 place-items-center rounded-full bg-slate-100 text-slate-400">
                                    @svg($emptyIcon, 'h-6 w-6')
                                </span>
                                <p class="text-sm font-medium text-slate-600">{{ $emptyText }}</p>
                                @isset($emptyActions)
                                    <div class="mt-4 flex items-center gap-2">{{ $emptyActions }}</div>
                                @endisset
                            </div>
                        </td>
                    </tr>
                @else
                    {{ $slot }}
                @endif
            </tbody>
        </table>
    </div>

    @isset($footer)
        <div class="flex flex-wrap items-center justify-between gap-2 border-t border-slate-100 px-4 py-3 text-sm text-slate-500">
            {{ $footer }}
        </div>
    @endisset
</div>
