<x-filament-panels::page>
    <x-x2.action-bar
        title="Cài đặt bảo mật tích hợp"
        subtitle="Secret rotation · IP allowlist · HMAC · OAuth callback · rate limit · audit retention · replay · tắt khẩn cấp." />

    @if ($emergencyOn)
        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700">
            ⚠️ Chế độ TẮT KHẨN CẤP đang bật — toàn bộ tích hợp đang bị tạm ngưng.
        </div>
    @endif

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <x-x2.section-card title="Secret rotation">
            <div class="text-2xl font-semibold text-slate-800">{{ $rotation['rotation_days'] ?? 90 }} <span class="text-sm font-normal text-slate-400">ngày</span></div>
            <div class="mt-1 text-xs text-slate-400">Báo trước {{ $rotation['expiration_notice_days'] ?? 7 }} ngày</div>
        </x-x2.section-card>
        <x-x2.section-card title="HMAC signature">
            <div class="text-2xl font-semibold text-slate-800">{{ $hmac['algorithm'] ?? 'HMAC SHA256' }}</div>
            <div class="mt-1 text-xs text-slate-400">Header {{ $hmac['signature_header'] ?? 'X-Request-Signature' }} · skew {{ $hmac['clock_skew_minutes'] ?? 5 }}′</div>
        </x-x2.section-card>
        <x-x2.section-card title="Rate limit mặc định">
            <div class="text-2xl font-semibold text-slate-800">{{ number_format($rate['default'] ?? 1000) }}<span class="text-sm font-normal text-slate-400">/{{ $rate['window'] ?? '1 phút' }}</span></div>
            <div class="mt-1 text-xs text-slate-400">Burst {{ $rate['burst'] ?? 200 }}</div>
        </x-x2.section-card>
        <x-x2.section-card title="Audit retention">
            <div class="text-2xl font-semibold text-slate-800">{{ $retention['days'] ?? 180 }} <span class="text-sm font-normal text-slate-400">ngày</span></div>
            <div class="mt-1 text-xs text-slate-400">Replay protection: {{ ($replay['enabled'] ?? false) ? 'Bật' : 'Tắt' }}</div>
        </x-x2.section-card>
    </div>

    <x-x2.section-card title="Chính sách bảo mật">
        <ul class="divide-y divide-slate-50 text-sm">
            @foreach ($policies as $p)
                <li class="flex items-center justify-between py-2">
                    <span class="text-slate-700">{{ str_replace('_', ' ', $p->policy_key) }}</span>
                    <span class="rounded-full px-2 py-0.5 text-[11px] font-medium {{ $p->is_enabled ? 'bg-emerald-50 text-emerald-600' : 'bg-slate-100 text-slate-500' }}">
                        {{ $p->is_enabled ? 'Bật' : 'Tắt' }}
                    </span>
                </li>
            @endforeach
        </ul>
    </x-x2.section-card>

    {{ $this->table }}
</x-filament-panels::page>
