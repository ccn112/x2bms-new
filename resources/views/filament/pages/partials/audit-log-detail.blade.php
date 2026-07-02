@php
    $rows = [
        ['Thời gian', $log->created_at?->format('d/m/Y H:i:s') ?? '—'],
        ['Người thực hiện', $log->actor_name ?: 'Hệ thống'],
        ['Email', $log->user?->email ?? '—'],
        ['Hành động', $log->action],
        ['Đối tượng', $log->subject_type ? class_basename($log->subject_type).' #'.$log->subject_id : '—'],
        ['Tòa', $log->building?->name ?? 'Toàn dự án'],
    ];
@endphp

<div class="space-y-4 text-sm">
    <dl class="grid grid-cols-1 gap-x-6 gap-y-3 sm:grid-cols-2">
        @foreach ($rows as [$label, $value])
            <div class="flex flex-col gap-0.5">
                <dt class="text-xs font-medium uppercase tracking-wide text-slate-400">{{ $label }}</dt>
                <dd class="font-medium text-slate-800 dark:text-slate-100">{{ $value }}</dd>
            </div>
        @endforeach
    </dl>

    <div class="rounded-xl border border-slate-200 bg-slate-50 p-3 dark:border-white/10 dark:bg-white/5">
        <div class="text-xs font-medium uppercase tracking-wide text-slate-400">Mô tả</div>
        <p class="mt-1 text-slate-700 dark:text-slate-200">{{ $log->description ?: '—' }}</p>
    </div>
</div>
