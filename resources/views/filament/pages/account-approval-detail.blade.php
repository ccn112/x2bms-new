<x-filament-panels::page>
    <a href="{{ url('/admin/resident-approvals') }}" class="mb-2 inline-flex items-center gap-1.5 text-sm font-medium text-x2-primary hover:underline">
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
        Quay lại hàng đợi duyệt
    </a>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-[300px_1fr_320px]">
        {{-- Left: summary --}}
        <div class="space-y-4">
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-gray-900">
                <div class="flex items-center gap-3">
                    <span class="grid h-12 w-12 place-items-center rounded-full bg-x2-navy text-lg font-bold text-white">
                        {{ \Illuminate\Support\Str::of($r->full_name)->explode(' ')->map(fn ($w) => mb_substr($w, 0, 1))->take(2)->implode('') }}
                    </span>
                    <div class="min-w-0">
                        <div class="truncate font-semibold text-slate-900 dark:text-white">{{ $r->full_name }}</div>
                        <x-x2.status-badge :label="$statusMeta[0]" :tone="$statusMeta[1]" />
                    </div>
                </div>
                <dl class="mt-4 space-y-2.5 border-t border-slate-100 pt-4 text-sm dark:border-white/10">
                    <div class="flex justify-between gap-2"><dt class="text-slate-400">Mã hồ sơ</dt><dd class="font-medium text-slate-700 dark:text-slate-200">#{{ $r->id }}</dd></div>
                    <div class="flex justify-between gap-2"><dt class="text-slate-400">Ngày gửi</dt><dd class="font-medium text-slate-700 dark:text-slate-200">{{ $r->submitted_at ? \Illuminate\Support\Carbon::parse($r->submitted_at)->format('d/m/Y H:i') : '—' }}</dd></div>
                    <div class="flex justify-between gap-2"><dt class="text-slate-400">Độ khớp</dt><dd class="font-semibold {{ ($r->match_score ?? 0) >= 80 ? 'text-x2-green' : 'text-x2-amber' }}">{{ $r->match_score ?? 0 }}%</dd></div>
                    <div class="flex justify-between gap-2"><dt class="text-slate-400">Giấy tờ</dt><dd class="font-medium text-slate-700 dark:text-slate-200">{{ $r->document_count ?? 0 }} tệp</dd></div>
                </dl>
            </div>
        </div>

        {{-- Center: reconciliation --}}
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-gray-900">
            <div class="flex items-center justify-between">
                <h3 class="text-base font-semibold text-slate-900 dark:text-white">Đối chiếu thông tin</h3>
                <div class="flex items-center gap-3 text-xs text-slate-400">
                    <span class="flex items-center gap-1"><span class="h-2 w-2 rounded-full bg-x2-green"></span>Khớp</span>
                    <span class="flex items-center gap-1"><span class="h-2 w-2 rounded-full bg-x2-amber"></span>Chênh lệch</span>
                    <span class="flex items-center gap-1"><span class="h-2 w-2 rounded-full bg-slate-300"></span>Chưa có dữ liệu</span>
                </div>
            </div>
            <div class="mt-4 overflow-hidden rounded-xl border border-slate-100 dark:border-white/10">
                <table class="w-full text-sm">
                    <thead><tr class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-400 dark:bg-white/5">
                        <th class="px-4 py-2.5 font-medium">Trường</th>
                        <th class="px-4 py-2.5 font-medium">Thông tin khai báo</th>
                        <th class="px-4 py-2.5 font-medium">Dữ liệu hệ thống</th>
                        <th class="px-4 py-2.5 font-medium text-center">TT</th>
                    </tr></thead>
                    <tbody class="divide-y divide-slate-50 dark:divide-white/5">
                        @foreach ($rows as $row)
                            <tr>
                                <td class="px-4 py-3 text-slate-500">{{ $row['label'] }}</td>
                                <td class="px-4 py-3 font-medium text-slate-800 dark:text-slate-100">{{ $row['declared'] }}</td>
                                <td class="px-4 py-3 text-slate-600 dark:text-slate-300">{{ $row['system'] }}</td>
                                <td class="px-4 py-3 text-center">
                                    @if (! $row['hasSystem'])
                                        <span class="inline-block h-2.5 w-2.5 rounded-full bg-slate-300" title="Chưa có dữ liệu"></span>
                                    @elseif ($row['ok'])
                                        <svg class="mx-auto h-4 w-4 text-x2-green" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.4"><path stroke-linecap="round" stroke-linejoin="round" d="m5 13 4 4L19 7"/></svg>
                                    @else
                                        <svg class="mx-auto h-4 w-4 text-x2-amber" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z"/></svg>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if ($r->note)
                <div class="mt-4 rounded-xl bg-slate-50 p-3 text-sm dark:bg-white/5">
                    <div class="text-xs font-medium uppercase tracking-wide text-slate-400">Ghi chú hồ sơ</div>
                    <p class="mt-1 text-slate-700 dark:text-slate-200">{{ $r->note }}</p>
                </div>
            @endif
        </div>

        {{-- Right: decision --}}
        <div class="space-y-4">
            {{-- Rule risk panel (Module 0 — rule-based, KHÔNG LLM) --}}
            @php($riskColors = ['red' => ['border-red-200 bg-red-50 dark:bg-red-500/10', 'text-red-700 dark:text-red-300'], 'amber' => ['border-amber-200 bg-amber-50 dark:bg-amber-500/10', 'text-amber-700 dark:text-amber-300'], 'slate' => ['border-slate-200 bg-slate-50 dark:bg-white/5', 'text-slate-600 dark:text-slate-300']])
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-gray-900">
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-slate-900 dark:text-white">Đánh giá rủi ro</h3>
                    @if (empty($risk))
                        <x-x2.status-badge label="Không có cảnh báo" tone="green" />
                    @else
                        <x-x2.status-badge :label="count($risk).' cảnh báo'" :tone="$riskTone" />
                    @endif
                </div>
                @if (empty($risk))
                    <p class="mt-3 text-sm text-slate-500 dark:text-slate-400">Không phát hiện rủi ro theo bộ quy tắc. Vẫn nên đối chiếu giấy tờ trước khi duyệt.</p>
                @else
                    <ul class="mt-3 space-y-2.5">
                        @foreach ($risk as $f)
                            @php($tone = \App\Support\Rules\RiskLevel::tone($f['level']))
                            @php($c = $riskColors[$tone] ?? $riskColors['slate'])
                            <li class="rounded-xl border {{ $c[0] }} p-3">
                                <div class="flex items-center gap-2">
                                    <span class="text-xs font-semibold uppercase tracking-wide {{ $c[1] }}">{{ \App\Support\Rules\RiskLevel::label($f['level']) }}</span>
                                </div>
                                <p class="mt-1 text-sm font-medium text-slate-800 dark:text-slate-100">{{ $f['message'] }}</p>
                                @if (! empty($f['checklist']))
                                    <ul class="mt-1.5 space-y-1 text-xs text-slate-500 dark:text-slate-400">
                                        @foreach ($f['checklist'] as $item)
                                            <li class="flex gap-1.5"><span class="mt-0.5">•</span><span>{{ $item }}</span></li>
                                        @endforeach
                                    </ul>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-gray-900">
                <h3 class="text-base font-semibold text-slate-900 dark:text-white">Quyết định xử lý <span class="text-x2-red">*</span></h3>
                @if ($isBlocked && ! $canOverride)
                    <p class="mt-3 rounded-lg border border-red-200 bg-red-50 p-2.5 text-xs font-medium text-red-700 dark:bg-red-500/10 dark:text-red-300">Hồ sơ vi phạm chính sách — chỉ HQ/SuperAdmin mới được phê duyệt.</p>
                @elseif ($isBlocked && $canOverride)
                    <p class="mt-3 rounded-lg border border-red-200 bg-red-50 p-2.5 text-xs font-medium text-red-700 dark:bg-red-500/10 dark:text-red-300">Có cảnh báo chặn duyệt. Phê duyệt = <b>override</b> (bắt buộc nhập lý do, ghi audit).</p>
                @endif
                <div class="mt-4 grid grid-cols-3 gap-2">
                    @if ($isBlocked && ! $canOverride)
                        <button type="button" disabled
                            class="flex cursor-not-allowed flex-col items-center gap-1 rounded-xl border border-slate-200 bg-slate-50 py-3 text-xs font-semibold text-slate-300 dark:border-white/10 dark:bg-white/5">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 0 0 2-2v-6a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2Zm10-10V7a4 4 0 0 0-8 0v4h8Z"/></svg>
                            Phê duyệt
                        </button>
                    @else
                    <button type="button" wire:click="decide('approve')" wire:confirm="{{ $isBlocked ? 'Override cảnh báo chặn duyệt và phê duyệt hồ sơ này?' : 'Xác nhận phê duyệt hồ sơ này?' }}"
                        class="flex flex-col items-center gap-1 rounded-xl border py-3 text-xs font-semibold transition {{ $isBlocked ? 'border-red-200 bg-red-50 text-red-600 hover:bg-red-100' : 'border-green-200 bg-green-50 text-green-600 hover:bg-green-100' }}">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                        {{ $isBlocked ? 'Duyệt (override)' : 'Phê duyệt' }}
                    </button>
                    @endif
                    <button type="button" wire:click="decide('need_more')"
                        class="flex flex-col items-center gap-1 rounded-xl border border-amber-200 bg-amber-50 py-3 text-xs font-semibold text-amber-600 transition hover:bg-amber-100">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 3h6l4 4v14H6V5a2 2 0 0 1 2-2Z"/></svg>
                        Yêu cầu bổ sung
                    </button>
                    <button type="button" wire:click="decide('reject')"
                        class="flex flex-col items-center gap-1 rounded-xl border border-red-200 bg-red-50 py-3 text-xs font-semibold text-red-600 transition hover:bg-red-100">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 10l4 4m0-4l-4 4m8-2a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                        Từ chối
                    </button>
                </div>
                <div class="mt-4">
                    <label class="mb-1 block text-sm font-medium text-slate-600 dark:text-slate-300">Lý do / ghi chú xử lý</label>
                    <textarea wire:model="note" rows="4" maxlength="500" placeholder="Nhập lý do (bắt buộc khi từ chối / yêu cầu bổ sung)…"
                        class="w-full rounded-lg border-slate-200 text-sm focus:border-x2-primary focus:ring-x2-primary"></textarea>
                </div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-gray-900">
                <h3 class="text-sm font-semibold text-slate-900 dark:text-white">Điều kiện cần kiểm tra</h3>
                <ul class="mt-3 space-y-2 text-sm text-slate-600 dark:text-slate-300">
                    <li class="flex items-center gap-2"><svg class="h-4 w-4 text-x2-green" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m5 13 4 4L19 7"/></svg> Thông tin cá nhân khớp giấy tờ</li>
                    <li class="flex items-center gap-2"><span class="h-4 w-4 rounded border border-slate-300"></span> Giấy tờ hợp lệ, còn hiệu lực</li>
                    <li class="flex items-center gap-2"><span class="h-4 w-4 rounded border border-slate-300"></span> Không trùng lặp hồ sơ</li>
                </ul>
            </div>
        </div>
    </div>
</x-filament-panels::page>
