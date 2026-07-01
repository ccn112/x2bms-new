@php
    $statusMeta = \App\Filament\Pages\NotificationCenter::STATUS[$record->status] ?? [$record->status, 'slate'];
    $scopeLabel = ['all' => 'Toàn hệ thống', 'tenant' => 'Công ty', 'project' => 'Dự án', 'building' => 'Tòa nhà', 'apartment' => 'Căn hộ', 'role' => 'Vai trò', 'resident' => 'Cư dân'];
    $sent = $record->deliveryLogs->where('status', 'sent')->count();
    $failed = $record->deliveryLogs->where('status', 'failed')->count();
@endphp

<div class="space-y-4 text-sm">
    <div class="flex flex-wrap items-center gap-2 text-xs">
        <x-x2.status-badge :label="$statusMeta[0]" :tone="['gray'=>'slate','warning'=>'amber','success'=>'green'][$statusMeta[1]] ?? 'slate'" />
        <span class="rounded-md bg-slate-100 px-2 py-0.5 text-slate-600">{{ \App\Filament\Pages\NotificationCenter::TYPE[$record->type] ?? $record->type }}</span>
        <span class="rounded-md bg-slate-100 px-2 py-0.5 text-slate-600">{{ \App\Models\Notification::OWNER_LEVEL[$record->owner_level] ?? $record->owner_level }}</span>
        <span class="text-slate-400">· Ưu tiên: {{ $record->priority }}</span>
        @if ($record->published_at)<span class="text-slate-400">· Phát hành {{ $record->published_at->format('d/m/Y H:i') }}</span>
        @elseif ($record->publish_at)<span class="text-x2-amber">· Hẹn {{ $record->publish_at->format('d/m/Y H:i') }}</span>@endif
    </div>

    @if ($record->summary)<p class="rounded-lg bg-slate-50 p-3 italic text-slate-600">{{ $record->summary }}</p>@endif
    <div class="x2ai-prose max-w-none text-slate-700">{!! $record->body ?: '<p class="text-slate-400">(Không có nội dung)</p>' !!}</div>

    <div class="grid grid-cols-2 gap-3">
        <div class="rounded-lg bg-slate-50 p-3">
            <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">Phạm vi nhận</div>
            <ul class="mt-1 space-y-0.5">
                @forelse ($record->audiences as $a)
                    <li class="text-slate-700">{{ $scopeLabel[$a->scope_type] ?? $a->scope_type }}{{ $a->scope_id ? ' #'.$a->scope_id : '' }}</li>
                @empty
                    <li class="text-slate-400">—</li>
                @endforelse
            </ul>
        </div>
        <div class="rounded-lg bg-slate-50 p-3">
            <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">Kênh gửi</div>
            <div class="mt-1 flex flex-wrap gap-1">
                @forelse ($record->channels as $c)
                    <span class="rounded bg-white px-2 py-0.5 text-xs font-medium text-slate-600 ring-1 ring-slate-200">{{ strtoupper($c->channel) }}</span>
                @empty
                    <span class="text-slate-400">—</span>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Hiệu quả --}}
    <div class="grid grid-cols-3 gap-3">
        <div class="rounded-lg bg-slate-50 p-3 text-center">
            <div class="text-2xl font-bold text-x2-navy">{{ number_format($record->recipient_count) }}</div>
            <div class="text-xs text-slate-500">Người nhận</div>
        </div>
        <div class="rounded-lg bg-slate-50 p-3 text-center">
            <div class="text-2xl font-bold text-x2-green">{{ number_format($record->read_count) }}</div>
            <div class="text-xs text-slate-500">Đã đọc {{ $record->recipient_count ? '('.round($record->read_count / $record->recipient_count * 100).'%)' : '' }}</div>
        </div>
        <div class="rounded-lg bg-slate-50 p-3 text-center">
            <div class="text-2xl font-bold text-slate-700">{{ number_format($sent) }}</div>
            <div class="text-xs text-slate-500">Đã gửi{{ $failed ? ' · '.$failed.' lỗi' : '' }}</div>
        </div>
    </div>
</div>
