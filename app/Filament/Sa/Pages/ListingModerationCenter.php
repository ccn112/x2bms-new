<?php

namespace App\Filament\Sa\Pages;

use App\Filament\Concerns\ModeratesRealEstateListings;
use App\Filament\Concerns\PlatformScreen;
use App\Models\RealEstateListing;
use App\Models\Tenant;
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
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * Duyệt tin rao BĐS — cấp NỀN TẢNG (/sa), mọi tenant.
 *
 * Chốt lại 2026-07-30 (sau bản nháp đầu chỉ nhận tin BQL đẩy lên): SA phải
 * xử lý được MỌI tin rao ở mọi dự án, KỂ CẢ tin chưa từng được BQL đụng tới —
 * lý do thực tế là có dự án không có người trực hoặc BQL bỏ quên, tin rao
 * không thể treo vô thời hạn chỉ vì không ai chủ động đẩy lên. Cột/badge
 * "Đẩy lên SA" + filter tương ứng chỉ là TÍN HIỆU ƯU TIÊN để SA biết tin nào
 * BQL chủ động xin ý kiến, KHÔNG phải điều kiện để hành động — SA duyệt/từ
 * chối được cả những tin chưa từng escalate.
 *
 * Dùng chung `ModeratesRealEstateListings` với /admin — cùng một quy tắc
 * khoá theo bản ghi (lockForUpdate) để hai cấp không thể duyệt/từ chối ngược
 * nhau, xem docblock của trait đó.
 */
class ListingModerationCenter extends Page implements HasTable
{
    use InteractsWithTable;
    use ModeratesRealEstateListings;
    use PlatformScreen;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-home-modern';

    protected static string|\UnitEnum|null $navigationGroup = 'Nền tảng (SuperAdmin)';

    protected static ?string $navigationLabel = 'Duyệt tin rao BĐS';

    protected static ?int $navigationSort = 30;

    protected static ?string $title = 'Duyệt tin rao BĐS — toàn nền tảng';

    protected static ?string $slug = 'listings/moderation';

    protected string $view = 'filament.pages.listing-moderation-center';

    /** Chỉ SuperAdmin thật (không mở cho HQ operator) — xem PlatformScreen. */
    protected static function platformFeature(): ?string
    {
        return null;
    }

    public static function getNavigationBadge(): ?string
    {
        $count = RealEstateListing::withoutGlobalScope('tenant')
            ->where('approval_status', RealEstateListing::APPROVAL_PENDING)
            ->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    protected function getViewData(): array
    {
        $pending = RealEstateListing::withoutGlobalScope('tenant')->where('approval_status', RealEstateListing::APPROVAL_PENDING);

        return [
            'kpis' => [
                ['label' => 'Chờ duyệt (mọi tenant)', 'value' => (clone $pending)->count(), 'accent' => 'amber'],
                ['label' => 'Được BQL đẩy lên', 'value' => (clone $pending)->whereNotNull('escalated_at')->count(), 'accent' => 'red'],
                ['label' => 'Chờ >3 ngày, chưa đẩy lên', 'value' => (clone $pending)->whereNull('escalated_at')->where('created_at', '<=', now()->subDays(3))->count(), 'accent' => 'red'],
                ['label' => 'Đã duyệt (mọi tenant)', 'value' => RealEstateListing::withoutGlobalScope('tenant')->where('approval_status', RealEstateListing::APPROVAL_APPROVED)->count(), 'accent' => 'green'],
            ],
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            // withoutGlobalScope('tenant') tường minh — SA phải thấy MỌI tenant,
            // không dựa vào việc tài khoản platform admin tình cờ có tenant_id
            // null (trong seed demo, platform admin đầu tiên vẫn mang tenant_id
            // thật), nếu chỉ dựa incidental đó thì đổi seed/tài khoản khác là vỡ.
            ->query(
                RealEstateListing::withoutGlobalScope('tenant')
                    ->with(['tenant', 'project', 'apartment', 'createdBy'])
            )
            ->defaultSort('created_at', 'asc')
            ->columns([
                TextColumn::make('tenant.name')->label('Tenant')->searchable(),
                TextColumn::make('project.name')->label('Dự án')->searchable(),
                TextColumn::make('apartment.code')->label('Căn hộ')->placeholder('—'),
                TextColumn::make('title')->label('Tiêu đề')->wrap()->limit(50)->searchable(),
                TextColumn::make('type')->label('Loại')->badge()
                    ->formatStateUsing(fn (string $state) => $state === 'rent' ? 'Cho thuê' : 'Bán')
                    ->color(fn (string $state) => $state === 'rent' ? 'info' : 'primary'),
                TextColumn::make('price')->label('Giá')->money('VND')->sortable(),
                TextColumn::make('approval_status')->label('Trạng thái duyệt')->badge()
                    ->formatStateUsing(fn (string $state) => ['pending' => 'Chờ duyệt', 'approved' => 'Đã duyệt', 'rejected' => 'Bị từ chối'][$state] ?? $state)
                    ->color(fn (string $state) => ['pending' => 'warning', 'approved' => 'success', 'rejected' => 'danger'][$state] ?? 'gray'),
                TextColumn::make('escalated_at')->label('Được BQL đẩy lên')->badge()
                    ->formatStateUsing(fn ($state) => $state ? 'Có — '.$state->diffForHumans() : 'Chưa')
                    ->color(fn ($state) => $state ? 'danger' : 'gray'),
                TextColumn::make('created_at')->label('Đăng lúc')
                    ->formatStateUsing(fn ($state) => $state?->diffForHumans())
                    ->sortable(),
                TextColumn::make('createdBy.name')->label('Người đăng')->placeholder('—')->toggleable(),
            ])
            ->filters([
                SelectFilter::make('tenant_id')->label('Tenant')
                    ->options(fn () => Tenant::orderBy('name')->pluck('name', 'id')),
                SelectFilter::make('approval_status')->label('Trạng thái duyệt')
                    ->options(['pending' => 'Chờ duyệt', 'approved' => 'Đã duyệt', 'rejected' => 'Bị từ chối']),
                TernaryFilter::make('escalated')
                    ->label('Được BQL đẩy lên')
                    ->queries(
                        true: fn (Builder $q) => $q->whereNotNull('escalated_at'),
                        false: fn (Builder $q) => $q->whereNull('escalated_at'),
                    ),
            ])
            ->recordActions([
                Action::make('approve')
                    ->label('Duyệt')->icon('heroicon-m-check')->color('success')
                    ->visible(fn (RealEstateListing $record) => ! $record->isApproved())
                    ->requiresConfirmation()
                    ->action(function (RealEstateListing $record): void {
                        $this->approveListing($record);
                        Notification::make()->title('Đã duyệt tin rao')->success()->send();
                    }),
                Action::make('reject')
                    ->label('Từ chối')->icon('heroicon-m-x-mark')->color('danger')
                    ->visible(fn (RealEstateListing $record) => $record->approval_status !== RealEstateListing::APPROVAL_REJECTED)
                    ->schema([
                        Textarea::make('reason')->label('Lý do từ chối')->required()->rows(3)
                            ->helperText('Cư dân sẽ nhìn thấy lý do này ở "Tin rao của tôi".'),
                    ])
                    ->action(function (array $data, RealEstateListing $record): void {
                        try {
                            $this->rejectListing($record, $data['reason'] ?? '');
                        } catch (\InvalidArgumentException $e) {
                            Notification::make()->title($e->getMessage())->danger()->send();

                            return;
                        }
                        Notification::make()->title('Đã từ chối tin rao')->warning()->send();
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('approveBulk')
                        ->label('Duyệt hàng loạt')->icon('heroicon-m-check-circle')->color('success')
                        ->requiresConfirmation()
                        ->action(function (Collection $records): void {
                            $eligible = $records->reject(fn (RealEstateListing $r) => $r->isApproved());
                            $eligible->each(fn (RealEstateListing $r) => $this->approveListing($r));
                            Notification::make()->title('Đã duyệt '.$eligible->count().' tin rao')->success()->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                ]),
            ])
            ->emptyStateHeading('Không có tin rao nào')
            ->striped()
            ->paginated([25, 50, 100]);
    }
}
