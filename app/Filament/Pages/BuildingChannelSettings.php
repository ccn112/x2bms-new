<?php

namespace App\Filament\Pages;

use App\Models\Building;
use App\Models\BuildingNotificationChannel;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

/**
 * BQL — CẤU HÌNH KÊNH GỬI (theo tòa), ADR-002. Khai tham số nhà cung cấp cho từng
 * KÊNH ở cấp TÒA: email (gửi thật, Elastic Email) + Zalo/WhatsApp/Telegram/X.Space
 * (CỔNG CHỜ — lưu tham số, chưa đấu nối). `MultiChannelNotifier` đọc cấu hình này khi
 * gửi để biết bật/tắt + trạng thái cổng chờ.
 *
 * Scope theo tenant (BelongsToTenant) nên BQL chỉ thấy tòa của mình.
 */
class BuildingChannelSettings extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-signal';

    protected static string|\UnitEnum|null $navigationGroup = 'Vận hành';

    protected static ?string $navigationLabel = 'Cấu hình kênh gửi';

    protected static ?int $navigationSort = 5;

    protected static ?string $title = 'Cấu hình kênh gửi (theo tòa)';

    protected static ?string $slug = 'notifications/channel-settings';

    protected string $view = 'filament.pages.building-channel-settings';

    public const STATUS = [
        BuildingNotificationChannel::STATUS_ACTIVE => ['Đang hoạt động', 'success'],
        BuildingNotificationChannel::STATUS_PENDING => ['Cổng chờ', 'warning'],
    ];

    /** Gợi ý tham số cho từng kênh (đổ sẵn khi khai mới). */
    private const CONFIG_HINTS = [
        'email' => ['from_name' => '', 'from_address' => '', 'reply_to' => ''],
        'zalo' => ['oa_id' => '', 'access_token' => '', 'template_id' => ''],
        'whatsapp' => ['phone_number_id' => '', 'access_token' => '', 'template_namespace' => ''],
        'telegram' => ['bot_token' => '', 'default_chat_id' => ''],
        'xspace' => ['workspace_id' => '', 'webhook_url' => '', 'api_key' => ''],
    ];

    protected function getHeaderActions(): array
    {
        return [
            Action::make('configure')
                ->label('Khai báo kênh cho tòa')->icon('heroicon-m-plus')->color('primary')
                ->modalHeading('Khai báo / cập nhật kênh gửi của tòa')->modalWidth('xl')
                ->schema($this->formSchema())
                ->action(fn (array $data) => $this->save($data)),
        ];
    }

    /** @return array<int, \Filament\Forms\Components\Component> */
    private function formSchema(): array
    {
        return [
            Select::make('building_id')->label('Tòa')->required()->searchable()
                ->options(fn () => Building::query()->orderBy('name')->pluck('name', 'id')->all()),
            Select::make('channel')->label('Kênh')->required()->live()
                ->options(BuildingNotificationChannel::CHANNEL_LABELS)
                ->afterStateUpdated(fn ($state, callable $set) => $set('config', self::CONFIG_HINTS[$state] ?? [])),
            Toggle::make('enabled')->label('Bật kênh')->default(true)
                ->helperText('Tắt = tòa không gửi qua kênh này (sổ gửi ghi "suppressed").'),
            Select::make('status')->label('Trạng thái')
                ->options(['pending' => 'Cổng chờ (đã khai, chưa đấu nối)', 'active' => 'Đang hoạt động (đã đấu nối provider)'])
                ->default('pending')->required()
                ->helperText('Hiện chỉ Email hỗ trợ "Đang hoạt động". Kênh khác để "Cổng chờ".'),
            KeyValue::make('config')->label('Tham số provider')
                ->keyLabel('Tham số')->valueLabel('Giá trị')->reorderable(false)
                ->helperText('VD email: from_name / from_address / reply_to. Zalo: oa_id / access_token. Telegram: bot_token / default_chat_id. X.Space: workspace_id / webhook_url / api_key.'),
            TextInput::make('note')->label('Ghi chú')->maxLength(255),
        ];
    }

    private function save(array $data): void
    {
        BuildingNotificationChannel::updateOrCreate(
            ['building_id' => $data['building_id'], 'channel' => $data['channel']],
            [
                'tenant_id' => Building::find($data['building_id'])?->tenant_id,
                'enabled' => $data['enabled'] ?? true,
                'status' => $data['status'] ?? 'pending',
                'config' => array_filter($data['config'] ?? [], fn ($v) => $v !== null && $v !== ''),
                'note' => $data['note'] ?? null,
                'verified_at' => ($data['status'] ?? 'pending') === 'active' ? now() : null,
                'updated_by_id' => auth()->id(),
            ],
        );

        Notification::make()->title('Đã lưu cấu hình kênh cho tòa')->success()->send();
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(BuildingNotificationChannel::query()->with('building:id,name'))
            ->defaultSort('building_id')
            ->columns([
                TextColumn::make('building.name')->label('Tòa')->searchable()->weight('medium'),
                TextColumn::make('channel')->label('Kênh')->badge()->color('gray')
                    ->formatStateUsing(fn (?string $s) => BuildingNotificationChannel::CHANNEL_LABELS[$s] ?? $s),
                IconColumn::make('enabled')->label('Bật')->boolean(),
                TextColumn::make('status')->label('Trạng thái')->badge()
                    ->formatStateUsing(fn (?string $s) => self::STATUS[$s][0] ?? $s)
                    ->color(fn (?string $s) => self::STATUS[$s][1] ?? 'gray'),
                TextColumn::make('config')->label('Tham số')
                    ->formatStateUsing(fn ($state) => is_array($state) && $state ? implode(', ', array_keys($state)) : '—')
                    ->color('gray'),
                TextColumn::make('note')->label('Ghi chú')->toggleable()->placeholder('—'),
                TextColumn::make('updated_at')->label('Cập nhật')->dateTime('d/m/Y H:i')->toggleable(),
            ])
            ->filters([
                SelectFilter::make('channel')->label('Kênh')->options(BuildingNotificationChannel::CHANNEL_LABELS),
                SelectFilter::make('status')->label('Trạng thái')->options(collect(self::STATUS)->map(fn ($v) => $v[0])->all()),
            ])
            ->recordActions([
                Action::make('edit')
                    ->label('Sửa')->iconButton()->icon('heroicon-m-pencil-square')->color('primary')
                    ->modalHeading('Sửa cấu hình kênh')->modalWidth('xl')
                    ->fillForm(fn (BuildingNotificationChannel $r) => [
                        'building_id' => $r->building_id, 'channel' => $r->channel,
                        'enabled' => $r->enabled, 'status' => $r->status,
                        'config' => $r->config ?? [], 'note' => $r->note,
                    ])
                    ->schema($this->formSchema())
                    ->action(fn (array $data) => $this->save($data)),
            ])
            ->emptyStateHeading('Chưa khai kênh gửi nào')
            ->emptyStateDescription('Khai tham số provider cho từng tòa: email gửi thật, các kênh khác là cổng chờ.')
            ->emptyStateIcon('heroicon-o-signal');
    }
}
