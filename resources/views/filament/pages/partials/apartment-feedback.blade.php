@php
    $feedback = $feedback ?? [];
    $fbStatus = ['new' => ['Mới', 'amber'], 'assigned' => ['Đã phân công', 'blue'], 'in_progress' => ['Đang xử lý', 'blue'], 'resolved' => ['Đã xử lý', 'green'], 'closed' => ['Đóng', 'slate']];
    $prio = ['low' => ['Thấp', 'slate'], 'medium' => ['Trung bình', 'amber'], 'high' => ['Cao', 'red'], 'urgent' => ['Khẩn', 'red']];
@endphp

@if (count($feedback))
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-slate-100 text-left text-xs text-slate-500">
                    <th class="py-2 pr-3 font-medium">Mã</th>
                    <th class="py-2 pr-3 font-medium">Tiêu đề</th>
                    <th class="py-2 pr-3 font-medium">Ưu tiên</th>
                    <th class="py-2 pr-3 font-medium">Trạng thái</th>
                    <th class="py-2 font-medium">Ngày tạo</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($feedback as $f)
                    @php
                        [$sl, $st] = $fbStatus[$f['status']] ?? [$f['status'], 'slate'];
                        [$pl, $pt] = $prio[$f['priority']] ?? [$f['priority'] ?? '—', 'slate'];
                    @endphp
                    <tr class="border-b border-slate-50">
                        <td class="py-2.5 pr-3 font-mono text-xs text-slate-600">{{ $f['code'] }}</td>
                        <td class="py-2.5 pr-3 font-medium text-slate-800">{{ $f['title'] }}</td>
                        <td class="py-2.5 pr-3"><x-x2.status-badge :label="$pl" :tone="$pt" /></td>
                        <td class="py-2.5 pr-3"><x-x2.status-badge :label="$sl" :tone="$st" /></td>
                        <td class="py-2.5 text-slate-500">{{ $f['date'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@else
    <p class="text-sm text-slate-400">Chưa có phản ánh cho căn hộ này.</p>
@endif
