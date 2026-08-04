<?php

namespace App\Filament\Pages;

use App\Models\Notification as NotificationModel;
use App\Services\Notifications\NotificationAnalyticsService;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

/**
 * 07-10 — Hiệu quả thông báo. Mọi số lấy từ {@see NotificationAnalyticsService} (query
 * test được độc lập), scope theo `Notification::visibleTo` nên không lộ tenant/dự án khác.
 * READ-ONLY.
 */
class NotificationAnalytics extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar';

    protected static string|\UnitEnum|null $navigationGroup = 'Vận hành';

    protected static ?string $navigationLabel = 'Hiệu quả thông báo';

    protected static ?int $navigationSort = 6;

    protected static ?string $title = 'Hiệu quả thông báo';

    protected static ?string $slug = 'notifications/analytics';

    protected string $view = 'filament.pages.notification-analytics';

    /** Nhãn kênh (khớp màn sổ gửi). */
    public const CHANNEL = [
        'push' => 'Push', 'app' => 'Trong app', 'email' => 'Email',
        'zalo' => 'Zalo', 'whatsapp' => 'WhatsApp', 'telegram' => 'Telegram',
        'xspace' => 'X.Space', 'sms' => 'SMS', 'postal' => 'Thư tay',
    ];

    private function service(): NotificationAnalyticsService
    {
        return app(NotificationAnalyticsService::class);
    }

    protected function getViewData(): array
    {
        $u = auth()->user();
        $s = $this->service()->summary($u);
        $channels = $this->service()->channelBreakdown($u);

        return [
            'kpis' => [
                ['label' => 'Đã phát hành', 'value' => number_format($s['published']), 'accent' => 'blue'],
                ['label' => 'Tổng người nhận', 'value' => number_format($s['recipients']), 'accent' => 'teal'],
                ['label' => 'Tỉ lệ đã đọc (open-rate)', 'value' => $s['open_rate'].'%', 'sub' => number_format($s['reads']).'/'.number_format($s['recipients']), 'accent' => 'green'],
                ['label' => 'Chi phí kênh trả phí', 'value' => number_format($this->service()->paidCost($u)).' đ', 'sub' => 'SMS/Zalo/WhatsApp', 'accent' => 'amber'],
            ],
            'channels' => $channels,
            'channelLabels' => self::CHANNEL,
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->service()->base(auth()->user())->with('audiences'))
            ->defaultSort('published_at', 'desc')
            ->columns([
                TextColumn::make('title')->label('Thông báo')->wrap()->searchable()->weight('medium')
                    ->description(fn (NotificationModel $r) => $r->code),
                TextColumn::make('type')->label('Loại')->badge()->color('gray')
                    ->formatStateUsing(fn (?string $s) => NotificationCenter::TYPE[$s] ?? $s),
                TextColumn::make('recipient_count')->label('Người nhận')->numeric()->sortable(),
                TextColumn::make('read_count')->label('Đã đọc')->numeric()->sortable(),
                TextColumn::make('open_rate')->label('Tỉ lệ đọc')
                    ->state(fn (NotificationModel $r) => $r->recipient_count > 0 ? round($r->read_count / $r->recipient_count * 100, 1).'%' : '—')
                    ->badge()
                    ->color(fn (NotificationModel $r) => match (true) {
                        $r->recipient_count === 0 => 'gray',
                        $r->read_count / $r->recipient_count >= 0.6 => 'success',
                        $r->read_count / $r->recipient_count >= 0.3 => 'warning',
                        default => 'danger',
                    }),
                TextColumn::make('published_at')->label('Phát hành')->dateTime('d/m/Y H:i')->sortable(),
            ])
            ->filters([
                SelectFilter::make('type')->label('Loại')->options(NotificationCenter::TYPE),
            ])
            ->emptyStateHeading('Chưa có thông báo đã phát hành')
            ->emptyStateDescription('Số liệu hiệu quả tính trên các thông báo đã phát hành trong phạm vi của bạn.');
    }
}
