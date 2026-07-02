<?php

namespace App\Filament\Pages;

use App\Models\AuditLog;
use App\Models\Vehicle;
use App\Support\Context\CurrentContext;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * BQL-02-04/05 — Danh sách & duyệt yêu cầu đăng ký xe (Vehicle Requests).
 * Vehicle registrations scoped to the project's buildings, with KPI cards + a Filament
 * table (search/filter/pagination) and approve/reject/revoke actions that transition
 * the vehicle status and write audit. UI follows BQL-02-04.
 */
class VehicleRequests extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-truck';

    protected static string|\UnitEnum|null $navigationGroup = 'An ninh & Kiểm soát';

    protected static ?string $navigationLabel = 'Duyệt đăng ký xe';

    protected static ?int $navigationSort = 20;

    protected static ?string $title = 'Danh sách yêu cầu xe';

    protected static ?string $slug = 'access/vehicle-requests';

    protected string $view = 'filament.pages.vehicle-requests';

    public const STATUS = [
        'pending' => ['Chờ duyệt', 'amber'],
        'reviewing' => ['Đang rà soát', 'blue'],
        'need_more' => ['Cần bổ sung', 'amber'],
        'active' => ['Đã phê duyệt', 'green'],
        'approved' => ['Đã phê duyệt', 'green'],
        'revoked' => ['Đã thu hồi', 'red'],
        'rejected' => ['Từ chối', 'red'],
        'expired' => ['Hết hạn', 'slate'],
    ];

    public const TYPE = ['car' => 'Ô tô', 'motorbike' => 'Xe máy', 'ev' => 'Xe điện', 'bicycle' => 'Xe đạp'];

    /** @return Builder<Vehicle> */
    private function scoped(): Builder
    {
        return Vehicle::query()->whereIn('building_id', app(CurrentContext::class)->buildingIds() ?: [0]);
    }

    protected function getViewData(): array
    {
        $soon = now()->addDays(30);

        return [
            'kpis' => [
                ['label' => 'Tổng phương tiện', 'value' => (clone $this->scoped())->count(), 'accent' => 'blue'],
                ['label' => 'Đang hoạt động', 'value' => (clone $this->scoped())->where('status', 'active')->count(), 'accent' => 'green'],
                ['label' => 'Chờ duyệt', 'value' => (clone $this->scoped())->whereIn('status', ['pending', 'reviewing', 'need_more'])->count(), 'accent' => 'amber'],
                ['label' => 'Sắp hết hạn', 'value' => (clone $this->scoped())->whereNotNull('valid_to')->whereBetween('valid_to', [now(), $soon])->count(), 'accent' => 'amber'],
                ['label' => 'Phí gửi xe / tháng', 'value' => number_format((float) (clone $this->scoped())->sum('monthly_fee') / 1e6, 1).' tr', 'accent' => 'teal'],
            ],
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->scoped()->with(['apartment', 'resident']))
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('id')->label('Mã yêu cầu')->color('primary')
                    ->formatStateUsing(fn ($state, Vehicle $record): string => 'XE-'.str_pad((string) $record->id, 6, '0', STR_PAD_LEFT)),
                TextColumn::make('resident.full_name')->label('Cư dân / Căn hộ')->searchable()->placeholder('—')
                    ->description(fn (Vehicle $v): ?string => $v->apartment?->code),
                TextColumn::make('type')->label('Loại xe')->badge()->color('gray')
                    ->formatStateUsing(fn (?string $s): string => self::TYPE[$s] ?? ($s ?: '—')),
                TextColumn::make('plate_no')->label('Biển số')->searchable()->weight('medium'),
                TextColumn::make('parking_card_no')->label('Khu đỗ / Thẻ xe')->placeholder('—'),
                TextColumn::make('monthly_fee')->label('Phí / tháng')->money('VND')->sortable(),
                TextColumn::make('valid_to')->label('Hiệu lực đến')->date('d/m/Y')->placeholder('Không thời hạn')
                    ->color(fn (Vehicle $v): string => $v->valid_to && $v->valid_to->isPast() ? 'danger' : 'gray'),
                TextColumn::make('status')->label('Trạng thái')->badge()
                    ->formatStateUsing(fn (?string $s): string => self::STATUS[$s][0] ?? ($s ?? '—'))
                    ->color(fn (?string $s): string => self::STATUS[$s][1] ?? 'gray'),
            ])
            ->filters([
                SelectFilter::make('type')->label('Loại xe')->options(self::TYPE),
                SelectFilter::make('status')->label('Trạng thái')->options(collect(self::STATUS)->map(fn ($v) => $v[0])->unique()->all()),
            ])
            ->recordActions([
                Action::make('approve')->label('Duyệt')->icon('heroicon-m-check')->iconButton()->color('success')
                    ->visible(fn (Vehicle $v) => in_array($v->status, ['pending', 'reviewing', 'need_more']))
                    ->requiresConfirmation()
                    ->action(fn (Vehicle $v) => $this->transitionVehicles(collect([$v]), 'active', 'vehicle.approve', 'Phê duyệt xe')),
                Action::make('reject')->label('Từ chối')->icon('heroicon-m-x-mark')->iconButton()->color('danger')
                    ->visible(fn (Vehicle $v) => in_array($v->status, ['pending', 'reviewing', 'need_more']))
                    ->schema([Textarea::make('note')->label('Lý do từ chối')->required()->rows(3)])
                    ->action(fn (Vehicle $v, array $data) => $this->transitionVehicles(collect([$v]), 'rejected', 'vehicle.reject', 'Từ chối xe', $data['note'] ?? null)),
                Action::make('revoke')->label('Thu hồi')->icon('heroicon-m-no-symbol')->iconButton()->color('gray')
                    ->visible(fn (Vehicle $v) => in_array($v->status, ['active', 'approved']))
                    ->requiresConfirmation()
                    ->action(fn (Vehicle $v) => $this->transitionVehicles(collect([$v]), 'revoked', 'vehicle.revoke', 'Thu hồi xe')),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('approve')->label('Phê duyệt nhanh')->icon('heroicon-m-check-circle')->color('success')
                        ->requiresConfirmation()
                        ->action(fn (Collection $records) => $this->transitionVehicles($records, 'active', 'vehicle.approve', 'Phê duyệt xe'))
                        ->deselectRecordsAfterCompletion(),
                    BulkAction::make('reject')->label('Từ chối')->icon('heroicon-m-x-circle')->color('danger')
                        ->schema([Textarea::make('note')->label('Lý do')->required()->rows(3)])
                        ->action(fn (Collection $records, array $data) => $this->transitionVehicles($records, 'rejected', 'vehicle.reject', 'Từ chối xe', $data['note'] ?? null))
                        ->deselectRecordsAfterCompletion(),
                ]),
            ])
            ->emptyStateHeading('Chưa có yêu cầu xe')
            ->emptyStateIcon('heroicon-o-truck')
            ->striped()
            ->paginated([10, 25, 50]);
    }

    private function transitionVehicles(Collection $records, string $status, string $action, string $verb, ?string $note = null): void
    {
        $records->each->update(['status' => $status]);
        $user = auth()->user();
        AuditLog::create([
            'tenant_id' => $user->tenant_id, 'building_id' => $user->building_id,
            'user_id' => $user->id, 'actor_name' => $user->name,
            'action' => $action, 'description' => $verb.' '.$records->count().' phương tiện'.($note ? ': '.$note : ''),
        ]);
        Notification::make()->title($verb.' '.$records->count().' phương tiện')->success()->send();
    }
}
