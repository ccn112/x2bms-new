<?php

namespace App\Filament\Pages;

use App\Models\AccessCard;
use App\Models\AuditLog;
use App\Models\Resident;
use App\Support\Context\CurrentContext;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
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
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * BQL-02-06/07 — Danh sách & cấp thẻ truy cập (Access Card List + Issue).
 * Access cards scoped to the project's buildings, KPI cards + Filament table with
 * issue-new / revoke / reactivate actions (audited). UI follows BQL-02-06.
 */
class AccessCards extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-credit-card';

    protected static string|\UnitEnum|null $navigationGroup = 'An ninh & Kiểm soát';

    protected static ?string $navigationLabel = 'Thẻ & quyền ra vào';

    protected static ?int $navigationSort = 21;

    protected static ?string $title = 'Danh sách thẻ & phương tiện truy cập';

    protected static ?string $slug = 'access/cards';

    protected string $view = 'filament.pages.access-cards';

    public const STATUS = [
        'active' => ['Đang hoạt động', 'green'],
        'revoked' => ['Đã thu hồi', 'red'],
        'expired' => ['Hết hạn', 'slate'],
        'pending' => ['Chờ kích hoạt', 'amber'],
    ];

    public const TYPE = ['rfid' => 'Thẻ từ', 'qr' => 'QR', 'face' => 'Khuôn mặt', 'fingerprint' => 'Vân tay'];

    /** @return Builder<AccessCard> */
    private function scoped(): Builder
    {
        return AccessCard::query()->whereIn('building_id', app(CurrentContext::class)->buildingIds() ?: [0]);
    }

    protected function getViewData(): array
    {
        $soon = now()->addDays(30);

        return [
            'kpis' => [
                ['label' => 'Tổng thẻ', 'value' => (clone $this->scoped())->count(), 'accent' => 'blue'],
                ['label' => 'Đang hoạt động', 'value' => (clone $this->scoped())->where('status', 'active')->count(), 'accent' => 'green'],
                ['label' => 'Sinh trắc học', 'value' => (clone $this->scoped())->where('is_biometric', true)->count(), 'accent' => 'teal'],
                ['label' => 'Sắp hết hạn', 'value' => (clone $this->scoped())->whereNotNull('valid_to')->whereBetween('valid_to', [now(), $soon])->count(), 'accent' => 'amber'],
                ['label' => 'Đã thu hồi', 'value' => (clone $this->scoped())->where('status', 'revoked')->count(), 'accent' => 'red'],
            ],
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->scoped()->with(['apartment', 'resident']))
            ->defaultSort('created_at', 'desc')
            ->headerActions([
                Action::make('issue')->label('Cấp thẻ mới')->icon('heroicon-m-plus')->button()
                    ->schema([
                        Select::make('resident_id')->label('Cư dân')
                            ->options(fn () => Resident::whereIn('building_id', app(CurrentContext::class)->buildingIds() ?: [0])->orderBy('full_name')->pluck('full_name', 'id'))
                            ->searchable()->required(),
                        TextInput::make('card_no')->label('Mã thẻ')->required()->default('CARD-'.now()->format('ymdHis')),
                        Select::make('type')->label('Loại thẻ')->options(self::TYPE)->default('rfid')->required(),
                        Toggle::make('is_biometric')->label('Có sinh trắc học'),
                        DatePicker::make('valid_from')->label('Hiệu lực từ')->default(now()),
                        DatePicker::make('valid_to')->label('Hiệu lực đến'),
                    ])
                    ->action(function (array $data): void {
                        $ctx = app(CurrentContext::class);
                        $resident = Resident::find($data['resident_id']);
                        AccessCard::create([
                            'tenant_id' => $ctx->tenantId(),
                            'building_id' => $resident?->building_id ?? ($ctx->buildingIds()[0] ?? null),
                            'resident_id' => $data['resident_id'],
                            'apartment_id' => $resident?->apartment_id,
                            'card_no' => $data['card_no'],
                            'type' => $data['type'],
                            'is_biometric' => $data['is_biometric'] ?? false,
                            'valid_from' => $data['valid_from'] ?? null,
                            'valid_to' => $data['valid_to'] ?? null,
                            'status' => 'active',
                        ]);
                        $this->audit('card.issue', 'Cấp thẻ '.$data['card_no'].' cho '.($resident?->full_name ?? '—'));
                        Notification::make()->title('Đã cấp thẻ '.$data['card_no'])->success()->send();
                    }),
            ])
            ->columns([
                TextColumn::make('card_no')->label('Mã thẻ')->searchable()->color('primary')->weight('medium'),
                TextColumn::make('type')->label('Loại thẻ')->badge()->color('gray')
                    ->formatStateUsing(fn (?string $s): string => self::TYPE[$s] ?? ($s ?: '—')),
                IconColumn::make('is_biometric')->label('Sinh trắc')->boolean(),
                TextColumn::make('resident.full_name')->label('Cư dân / Căn hộ')->searchable()->placeholder('—')
                    ->description(fn (AccessCard $c): ?string => $c->apartment?->code),
                TextColumn::make('valid_from')->label('Hiệu lực')->date('d/m/Y')->placeholder('—')
                    ->description(fn (AccessCard $c): ?string => $c->valid_to ? 'đến '.$c->valid_to->format('d/m/Y') : 'không thời hạn'),
                TextColumn::make('status')->label('Trạng thái')->badge()
                    ->formatStateUsing(fn (?string $s): string => self::STATUS[$s][0] ?? ($s ?? '—'))
                    ->color(fn (?string $s): string => self::STATUS[$s][1] ?? 'gray'),
            ])
            ->filters([
                SelectFilter::make('type')->label('Loại thẻ')->options(self::TYPE),
                SelectFilter::make('status')->label('Trạng thái')->options(collect(self::STATUS)->map(fn ($v) => $v[0])->all()),
                TernaryFilter::make('is_biometric')->label('Sinh trắc học'),
            ])
            ->recordActions([
                Action::make('revoke')->label('Thu hồi')->icon('heroicon-m-no-symbol')->iconButton()->color('danger')
                    ->visible(fn (AccessCard $c) => $c->status === 'active')->requiresConfirmation()
                    ->action(function (AccessCard $c): void {
                        $c->update(['status' => 'revoked']);
                        $this->audit('card.revoke', 'Thu hồi thẻ '.$c->card_no);
                        Notification::make()->title('Đã thu hồi thẻ '.$c->card_no)->warning()->send();
                    }),
                Action::make('reactivate')->label('Kích hoạt lại')->icon('heroicon-m-arrow-path')->iconButton()->color('success')
                    ->visible(fn (AccessCard $c) => $c->status === 'revoked')->requiresConfirmation()
                    ->action(function (AccessCard $c): void {
                        $c->update(['status' => 'active']);
                        $this->audit('card.reactivate', 'Kích hoạt lại thẻ '.$c->card_no);
                        Notification::make()->title('Đã kích hoạt lại thẻ '.$c->card_no)->success()->send();
                    }),
            ])
            ->emptyStateHeading('Chưa có thẻ truy cập')
            ->emptyStateIcon('heroicon-o-credit-card')
            ->striped()
            ->paginated([10, 25, 50]);
    }

    private function audit(string $action, string $description): void
    {
        $user = auth()->user();
        AuditLog::create([
            'tenant_id' => $user->tenant_id, 'building_id' => $user->building_id,
            'user_id' => $user->id, 'actor_name' => $user->name,
            'action' => $action, 'description' => $description,
        ]);
    }
}
