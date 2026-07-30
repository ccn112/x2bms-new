<x-filament-panels::page>
    <x-x2.action-bar title="Sử dụng app cư dân"
        subtitle="Số liệu 30 ngày · màn nào được dùng nhiều nhất · hàng chờ báo lỗi cư dân gửi từ app." />

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        @foreach ($kpis as $kpi)
            <x-x2.kpi-card :label="$kpi['label']" :value="$kpi['value']" :accent="$kpi['accent']" />
        @endforeach
    </div>

    {{--
        Ba con số về "người dùng" KHÁC nhau về bản chất; ghi rõ ngay trên màn để
        không ai lấy con số này báo cáo thành con số kia.
    --}}
    <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 text-sm text-gray-600
                dark:border-gray-700 dark:bg-gray-900/40 dark:text-gray-300">
        <p class="font-semibold mb-1">Ba con số này không thay thế được nhau:</p>
        <ul class="list-disc ps-5 space-y-1">
            <li><strong>Lượt cài</strong> — do Google Play / App Store báo, tính cả người tải rồi không mở lần nào.
                <em>Không chia được theo dự án</em> vì cả hệ thống dùng chung một app.</li>
            <li><strong>Thiết bị đã đăng ký</strong> — đã mở app và gọi API. Người tải rồi chưa mở thì không có ở đây.</li>
            <li><strong>Thiết bị hoạt động</strong> — thực sự có mở màn trong ngày.</li>
        </ul>
    </div>

    <x-filament::section heading="Màn được dùng nhiều nhất (30 ngày)">
        @if (! $hasTelemetry)
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Chưa có dữ liệu. Nhật ký chỉ xuất hiện sau khi bản app có gửi nhật ký được phát hành,
                và lệnh tổng hợp <code>x2:aggregate-telemetry</code> đã chạy (02:00 hằng ngày).
            </p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="text-start text-gray-500 dark:text-gray-400">
                        <tr>
                            <th class="py-2 text-start font-medium">Màn</th>
                            <th class="py-2 text-end font-medium">Lượt xem</th>
                            <th class="py-2 text-end font-medium">Thao tác</th>
                            <th class="py-2 text-end font-medium">Thiết bị·ngày</th>
                            <th class="py-2 text-end font-medium">TB thời gian</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @foreach ($topScreens as $s)
                            <tr>
                                <td class="py-2 font-mono text-xs">{{ $s['screen_key'] }}</td>
                                <td class="py-2 text-end">{{ number_format($s['views'], 0, ',', '.') }}</td>
                                <td class="py-2 text-end">{{ number_format($s['actions'], 0, ',', '.') }}</td>
                                <td class="py-2 text-end">{{ number_format($s['device_days'], 0, ',', '.') }}</td>
                                <td class="py-2 text-end">
                                    {{ $s['avg_seconds'] === null ? '—' : $s['avg_seconds'].'s' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <p class="mt-3 text-xs text-gray-500 dark:text-gray-400">
                <strong>Thiết bị·ngày</strong> là tổng số thiết bị riêng biệt của TỪNG NGÀY cộng lại,
                không phải số thiết bị riêng biệt của cả kỳ — một người mở màn 30 ngày được tính 30.
                Dùng để so sánh giữa các màn, đừng đọc như số người.
            </p>
        @endif
    </x-filament::section>

    <x-filament::section heading="Hàng chờ báo lỗi từ app">
        {{ $this->table }}
    </x-filament::section>
</x-filament-panels::page>
