@props([
    'columns' => [],   // [['key'=>'title','label'=>'Công việc'], ...]
    'rows' => [],       // array of associative rows; cells may be strings or HTML via slot below
    'empty' => 'Không có dữ liệu',
])

{{-- X2DataTable — presentational table. Rows come from a scoped query (no hardcoded data).
     Livewire-ready: promote to a Livewire component when sort/filter/paginate is needed. --}}
<div class="overflow-x-auto">
    <table class="w-full text-left text-sm">
        <thead>
            <tr class="border-b border-slate-100 text-xs text-slate-500">
                @foreach ($columns as $col)
                    <th @class(['px-3 py-2 font-medium', 'text-right' => ($col['align'] ?? '') === 'right'])>{{ $col['label'] ?? '' }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-50">
            @forelse ($rows as $row)
                <tr class="hover:bg-slate-50/60">
                    @foreach ($columns as $col)
                        <td class="px-3 py-2.5 align-middle text-slate-700">
                            {!! $row[$col['key']] ?? '' !!}
                        </td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ max(count($columns), 1) }}" class="px-3 py-8 text-center text-sm text-slate-400">
                        {{ $empty }}
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
