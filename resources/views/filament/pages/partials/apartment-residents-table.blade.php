@php
    $residents = $residents ?? [];
    $compact = $compact ?? false;
    $rows = $compact ? array_slice($residents, 0, 3) : $residents;
@endphp

@if (count($rows))
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-slate-100 text-left text-xs text-slate-500">
                    <th class="py-2 pr-3 font-medium">Họ và tên</th>
                    <th class="py-2 pr-3 font-medium">Quan hệ</th>
                    <th class="py-2 pr-3 font-medium">Vai trò</th>
                    <th class="py-2 pr-3 font-medium">Ngày sinh</th>
                    <th class="py-2 pr-3 font-medium">Số điện thoại</th>
                    <th class="py-2 font-medium">Trạng thái</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($rows as $r)
                    <tr class="border-b border-slate-50">
                        <td class="py-2.5 pr-3 font-medium">
                            @if (! empty($r['id']))
                                <a href="{{ url('/admin/residents/'.$r['id'].'/detail') }}" class="text-x2-primary hover:underline">{{ $r['name'] }}</a>
                            @else
                                <span class="text-slate-800">{{ $r['name'] }}</span>
                            @endif
                        </td>
                        <td class="py-2.5 pr-3"><x-x2.status-badge :label="$r['relationship']" :tone="$r['relationshipTone']" /></td>
                        <td class="py-2.5 pr-3 text-slate-600">{{ $r['role'] }}</td>
                        <td class="py-2.5 pr-3 text-slate-600">{{ $r['dob'] }}</td>
                        <td class="py-2.5 pr-3 text-slate-600">{{ $r['phone'] }}</td>
                        <td class="py-2.5"><x-x2.status-badge label="Đang ở" tone="green" /></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@else
    <p class="text-sm text-slate-400">Chưa có cư dân liên kết.</p>
@endif
