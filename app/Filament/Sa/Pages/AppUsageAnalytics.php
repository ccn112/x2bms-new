<?php

namespace App\Filament\Sa\Pages;

use App\Models\AppScreenDailyStat;
use App\Models\AppScreenReport;
use App\Models\MobileDevice;
use App\Models\StoreInstallStat;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Sử dụng app + hàng chờ báo lỗi — /sa (chốt 2026-07-30).
 *
 * ## BA con số về "người dùng", đừng trộn
 *
 * 1. **Lượt cài (từ store)** — `store_install_stats`, do Google/Apple báo. Tính cả
 *    người tải rồi không mở lần nào. **Không chia được theo tenant/dự án** (một app
 *    dùng chung cho mọi chung cư).
 * 2. **Thiết bị đã đăng ký** — `mobile_devices`, thiết bị đã gọi API. Chia được theo
 *    tenant. Người tải app rồi chưa mở thì KHÔNG có ở đây.
 * 3. **Thiết bị hoạt động** — `app_screen_daily_stats.unique_devices`, thực sự có mở
 *    màn trong ngày.
 *
 * Gọi con số 2 hay 3 là "số lượt cài" là **báo cáo sai cho chủ dự án**. Nhãn trên
 * màn này ghi rõ nguồn của từng con số, và khi chưa cấu hình key store thì hiện
 * "chưa cấu hình" chứ không lấy con số khác thay vào.
 */
class AppUsageAnalytics extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-device-phone-mobile';

    protected static string|\UnitEnum|null $navigationGroup = 'Nền tảng SuperAdmin';

    protected static ?string $navigationLabel = 'Sử dụng app & báo lỗi';

    protected static ?int $navigationSort = 60;

    protected static ?string $title = 'Sử dụng app cư dân & hàng chờ báo lỗi';

    protected static ?string $slug = 'app-usage';

    protected string $view = 'filament.pages.app-usage-analytics';

    public static function getNavigationBadge(): ?string
    {
        $n = AppScreenReport::whereNotIn('status', ['resolved', 'wont_fix'])->count();

        return $n > 0 ? (string) $n : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    /** @return array<string, mixed> */
    public function getViewData(): array
    {
        $since = today()->subDays(29);

        // --- Lượt cài từ store (30 ngày) ---
        $installs = StoreInstallStat::where('stat_date', '>=', $since)->sum('installs');
        $hasStoreData = StoreInstallStat::where('stat_date', '>=', $since)->exists();

        // --- Thiết bị đã đăng ký, tách theo có/không đăng nhập ---
        $devicesTotal = MobileDevice::whereNull('revoked_at')->count();
        $devicesIdentified = MobileDevice::whereNull('revoked_at')->whereNotNull('user_id')->count();

        // --- Thiết bị hoạt động 30 ngày (từ bảng tổng hợp) ---
        $activeDevices = (int) AppScreenDailyStat::where('stat_date', '>=', $since)->max('unique_devices');

        return [
            'kpis' => [
                [
                    'label' => 'Lượt cài 30 ngày (từ store)',
                    'value' => $hasStoreData ? number_format((int) $installs, 0, ',', '.') : 'Chưa cấu hình',
                    'accent' => $hasStoreData ? 'primary' : 'gray',
                ],
                [
                    'label' => 'Thiết bị đã đăng ký',
                    'value' => number_format($devicesTotal, 0, ',', '.'),
                    'accent' => 'info',
                ],
                [
                    // Hiệu số này mới là thứ đáng nhìn: tải app nhưng không đăng nhập
                    // = kích hoạt tài khoản đang tắc ở đâu đó.
                    'label' => 'Trong đó ẨN DANH (chưa đăng nhập)',
                    'value' => number_format($devicesTotal - $devicesIdentified, 0, ',', '.'),
                    'accent' => 'warning',
                ],
                [
                    'label' => 'Thiết bị hoạt động (ngày cao nhất/30 ngày)',
                    'value' => number_format($activeDevices, 0, ',', '.'),
                    'accent' => 'success',
                ],
            ],
            'topScreens' => $this->topScreens($since),
            'hasTelemetry' => AppScreenDailyStat::where('stat_date', '>=', $since)->exists(),
        ];
    }

    /**
     * Màn nào được dùng nhiều nhất — câu hỏi chính chủ dự án đặt ra.
     *
     * Đọc từ bảng TỔNG HỢP, không phải bảng thô: đếm trên vài trăm triệu dòng thô là
     * không khả thi, và bảng thô còn bị dọn theo hạn lưu.
     *
     * @return array<int, array<string, mixed>>
     */
    private function topScreens(Carbon $since): array
    {
        return AppScreenDailyStat::query()
            ->selectRaw('screen_key')
            ->selectRaw('SUM(views) AS views')
            ->selectRaw('SUM(actions) AS actions')
            // CHÚ Ý: đây là TỔNG của các giá trị theo ngày, KHÔNG phải số thiết bị
            // riêng biệt của cả kỳ (một người mở 30 ngày sẽ được đếm 30 lần). Muốn
            // số riêng biệt cả kỳ thì phải đếm lại trên bảng thô.
            ->selectRaw('SUM(unique_devices) AS device_days')
            ->selectRaw('AVG(avg_duration_ms) AS avg_ms')
            ->where('stat_date', '>=', $since)
            ->groupBy('screen_key')
            ->orderByDesc(DB::raw('SUM(views)'))
            ->limit(15)
            ->get()
            ->map(fn ($r) => [
                'screen_key' => $r->screen_key,
                'views' => (int) $r->views,
                'actions' => (int) $r->actions,
                'device_days' => (int) $r->device_days,
                'avg_seconds' => $r->avg_ms === null ? null : round(((float) $r->avg_ms) / 1000, 1),
            ])
            ->all();
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(AppScreenReport::query()->with(['user', 'assignedTo']))
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')->label('Gửi lúc')->dateTime('d/m/Y H:i')->sortable(),
                TextColumn::make('kind')->label('Loại')->badge()
                    ->formatStateUsing(fn (string $state) => [
                        'bug' => 'Lỗi', 'idea' => 'Góp ý', 'other' => 'Khác',
                    ][$state] ?? $state)
                    ->color(fn (string $state) => $state === 'bug' ? 'danger' : 'gray'),
                TextColumn::make('screen_key')->label('Màn')->searchable()
                    ->placeholder('— không rõ —'),
                TextColumn::make('message')->label('Nội dung')->wrap()->limit(90)->searchable(),
                TextColumn::make('user.name')->label('Người gửi')
                    ->placeholder('ẩn danh')->searchable(),
                TextColumn::make('platform')->label('Nền tảng')->badge()->placeholder('—'),
                TextColumn::make('app_version')->label('Phiên bản')->placeholder('—'),
                TextColumn::make('status')->label('Trạng thái')->badge()
                    ->formatStateUsing(fn (string $state) => [
                        'new' => 'Mới', 'triaged' => 'Đã phân loại', 'in_progress' => 'Đang xử lý',
                        'resolved' => 'Đã xử lý', 'wont_fix' => 'Không xử lý',
                    ][$state] ?? $state)
                    ->color(fn (string $state) => [
                        'new' => 'danger', 'triaged' => 'warning', 'in_progress' => 'info',
                        'resolved' => 'success',
                    ][$state] ?? 'gray'),
                TextColumn::make('assignedTo.name')->label('Phụ trách')->placeholder('—')->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')->label('Trạng thái')->options([
                    'new' => 'Mới', 'triaged' => 'Đã phân loại', 'in_progress' => 'Đang xử lý',
                    'resolved' => 'Đã xử lý', 'wont_fix' => 'Không xử lý',
                ]),
                SelectFilter::make('kind')->label('Loại')->options([
                    'bug' => 'Lỗi', 'idea' => 'Góp ý', 'other' => 'Khác',
                ]),
                SelectFilter::make('platform')->label('Nền tảng')->options([
                    'android' => 'Android', 'ios' => 'iOS', 'web' => 'Web',
                ]),
            ])
            ->recordActions([
                Action::make('setStatus')
                    ->label('Cập nhật')->icon('heroicon-m-pencil-square')->color('primary')
                    ->schema([
                        Select::make('status')->label('Trạng thái')
                            ->options([
                                'new' => 'Mới', 'triaged' => 'Đã phân loại',
                                'in_progress' => 'Đang xử lý', 'resolved' => 'Đã xử lý',
                                'wont_fix' => 'Không xử lý',
                            ])->required(),
                        Textarea::make('resolution_note')->label('Ghi chú xử lý')
                            ->helperText('Ghi lại đã sửa gì / vì sao không sửa — người sau đọc lại được.')
                            ->rows(3)->maxLength(2000),
                    ])
                    ->fillForm(fn (AppScreenReport $record) => [
                        'status' => $record->status,
                        'resolution_note' => $record->resolution_note,
                    ])
                    ->action(function (AppScreenReport $record, array $data): void {
                        $closing = in_array($data['status'], ['resolved', 'wont_fix'], true);

                        $record->forceFill([
                            'status' => $data['status'],
                            'resolution_note' => $data['resolution_note'] ?? null,
                            'assigned_to_id' => $record->assigned_to_id ?? auth()->id(),
                            // Đóng thì đóng dấu thời điểm; mở lại thì XOÁ dấu, nếu
                            // không báo cáo "đang xử lý" vẫn mang mốc đã xử lý.
                            'resolved_at' => $closing ? ($record->resolved_at ?? now()) : null,
                        ])->save();

                        Notification::make()->title('Đã cập nhật báo lỗi')->success()->send();
                    }),
            ]);
    }
}
