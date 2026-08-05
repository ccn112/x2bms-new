<?php

namespace App\Filament\Pages;

use App\Models\Notification as NotificationModel;
use App\Models\NotificationDeliveryLog;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use App\Filament\Concerns\AdminListingBreadcrumbs;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

/**
 * BQL — SỔ GỬI thông báo (audit, N3). READ-ONLY. Tra cứu về sau: một thông báo đã
 * gửi cho AI, qua KÊNH gì, trạng thái gửi/nhận/đọc, thời điểm, chi phí. Scope theo
 * quyền xem thông báo của BQL (Notification::visibleTo) nên không lộ dữ liệu dự án khác.
 *
 * Đây là màn CHỈ ĐỌC (không sửa được bản ghi gửi) — đúng bất biến tiền/độ nhạy (G9/G10).
 */
class NotificationDeliveryAudit extends Page implements HasTable
{
    use AdminListingBreadcrumbs;
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static string|\UnitEnum|null $navigationGroup = 'Vận hành';

    protected static ?string $navigationLabel = 'Sổ gửi thông báo';

    protected static ?int $navigationSort = 4;

    protected static ?string $title = 'Sổ gửi thông báo (audit)';

    protected static ?string $slug = 'notifications/delivery-audit';

    protected string $view = 'filament.pages.notification-delivery-audit';

    public const STATUS = [
        'queued' => ['Chờ gửi', 'gray'],
        'sent' => ['Đã gửi', 'info'],
        'delivered' => ['Đã nhận', 'success'],
        'read' => ['Đã đọc', 'success'],
        'failed' => ['Thất bại', 'danger'],
        'suppressed' => ['Bỏ (tắt kênh)', 'warning'],
        'bounced' => ['Trả lại', 'danger'],
    ];

    public const CHANNEL = [
        'push' => 'Push', 'app' => 'Trong app', 'email' => 'Email',
        'zalo' => 'Zalo', 'whatsapp' => 'WhatsApp', 'telegram' => 'Telegram',
        'xspace' => 'X.Space', 'sms' => 'SMS', 'postal' => 'Thư tay',
    ];

    public function table(Table $table): Table
    {
        $visibleIds = NotificationModel::query()->visibleTo(auth()->user())->select('id');

        return $table
            ->query(
                NotificationDeliveryLog::query()
                    ->whereIn('notification_id', $visibleIds)
                    ->with(['notification:id,title,type', 'user:id,name'])
            )
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('notification.title')->label('Thông báo')->wrap()->searchable()->weight('medium')
                    ->description(fn (NotificationDeliveryLog $r) => $r->topic ? 'Topic: '.$r->topic : null),
                TextColumn::make('channel')->label('Kênh')->badge()->color('gray')
                    ->formatStateUsing(fn (?string $s) => self::CHANNEL[$s] ?? $s),
                TextColumn::make('user.name')->label('Người nhận')
                    ->formatStateUsing(fn ($state, NotificationDeliveryLog $r) => $r->topic ? '— (broadcast topic)' : ($state ?? '—')),
                TextColumn::make('status')->label('Trạng thái')->badge()
                    ->formatStateUsing(fn (?string $s) => self::STATUS[$s][0] ?? $s)
                    ->color(fn (?string $s) => self::STATUS[$s][1] ?? 'gray'),
                TextColumn::make('sent_at')->label('Gửi')->dateTime('d/m/Y H:i')->placeholder('—'),
                TextColumn::make('delivered_at')->label('Nhận')->dateTime('d/m/Y H:i')->placeholder('—'),
                TextColumn::make('read_at')->label('Đọc')->dateTime('d/m/Y H:i')->placeholder('—'),
                TextColumn::make('cost')->label('Chi phí')->money('VND')->placeholder('—')->toggleable(),
                TextColumn::make('provider_message_id')->label('Mã NCC')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('error')->label('Lỗi')->color('danger')->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('channel')->label('Kênh')->options(self::CHANNEL),
                SelectFilter::make('status')->label('Trạng thái')->options(collect(self::STATUS)->map(fn ($v) => $v[0])->all()),
            ])
            ->emptyStateHeading('Chưa có lượt gửi nào')
            ->emptyStateDescription('Sổ gửi ghi nhận mỗi lần một thông báo được đẩy qua một kênh tới một người.');
    }
}
