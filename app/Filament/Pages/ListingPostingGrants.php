<?php

namespace App\Filament\Pages;

use App\Models\Apartment;
use App\Models\AuditLog;
use App\Models\ListingPostingGrant;
use App\Support\Context\CurrentContext;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Quyền rao tin đã XÁC MINH cho người thuê/môi giới — /admin.
 *
 * Chủ căn (role=owner trong resident_apartment_relations) tự có quyền rao,
 * KHÔNG cần cấp ở đây (xem `ListingAccessService::residentAllowedToPost`).
 * Màn này chỉ dành cho trường hợp người KHÁC (người thuê/môi giới) cần BQL
 * xác minh rõ ràng cho một (căn hộ, người) cụ thể — khoá unique
 * (apartment_id, resident_id) ở migration đã đảm bảo không cấp trùng.
 *
 * `ListingPostingGrant`/`Apartment` không dùng `BelongsToProject`, và tài
 * khoản SuperAdmin (is_platform_admin) làm việc thử ở /admin sẽ có global
 * scope tenant/project MỞ (xem BelongsToProject::currentProjectIds) — nên
 * lọc TƯỜNG MINH theo dự án hiện tại ở mọi truy vấn dưới đây, không dựa vào
 * scope ngầm của model.
 */
class ListingPostingGrants extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-key';

    protected static string|\UnitEnum|null $navigationGroup = 'Vận hành';

    protected static ?string $navigationLabel = 'Quyền rao tin';

    protected static ?int $navigationSort = 41;

    protected static ?string $title = 'Quyền rao tin đã xác minh';

    protected static ?string $slug = 'listings/posting-grants';

    protected string $view = 'filament.pages.listing-posting-grants';

    /** @return Builder<Apartment> */
    private function projectApartments(): Builder
    {
        $projectId = app(CurrentContext::class)->projectId() ?? 0;

        return Apartment::query()->whereHas('building', fn (Builder $q) => $q->where('project_id', $projectId));
    }

    /**
     * Đặt ở `$table->headerActions()` chứ không phải `getHeaderActions()` cấp
     * Page — xem comment tương tự ở `ListingApprovalQueue::autoApproveSettingAction()`
     * (theme /admin hiện tại không kích hoạt được khu vực header-action riêng
     * của Page, đã verify bằng một màn có sẵn khác không liên quan tới việc
     * này — `NotificationCenter` — cũng bị y hệt).
     */
    private function grantAction(): Action
    {
        return Action::make('grant')
            ->label('Cấp quyền rao')
            ->icon('heroicon-m-plus')
            ->color('primary')
            ->schema([
                Select::make('apartment_id')
                    ->label('Căn hộ')
                    ->options(fn () => $this->projectApartments()->pluck('code', 'id'))
                    ->searchable()->required()->live(),
                Select::make('resident_id')
                    ->label('Người được cấp quyền (thuê/môi giới)')
                    // Cố ý loại role=owner khỏi danh sách: chủ căn đã có
                    // quyền rao mặc định, cấp thêm cho họ là dư thừa và dễ
                    // gây hiểu nhầm "phải được cấp mới được rao".
                    ->options(function (Get $get) {
                        $apartmentId = $get('apartment_id');
                        if (! $apartmentId) {
                            return [];
                        }

                        return Apartment::find($apartmentId)?->residents()
                            ->wherePivot('role', '!=', 'owner')
                            ->pluck('residents.full_name', 'residents.id') ?? [];
                    })
                    ->searchable()->required()
                    ->helperText('Chỉ liệt kê người KHÔNG phải chủ căn — chủ căn tự có quyền rao.'),
                Textarea::make('note')->label('Ghi chú')->rows(2)
                    ->helperText('Ví dụ: hợp đồng thuê số…, môi giới được chủ nhà uỷ quyền qua văn bản ngày…'),
            ])
            ->action(function (array $data): void {
                $ctx = app(CurrentContext::class);
                $grant = ListingPostingGrant::query()->updateOrCreate(
                    ['apartment_id' => $data['apartment_id'], 'resident_id' => $data['resident_id']],
                    [
                        'tenant_id' => $ctx->tenantId(),
                        'granted_by_user_id' => auth()->id(),
                        'status' => ListingPostingGrant::STATUS_ACTIVE,
                        'note' => $data['note'] ?? null,
                    ],
                );

                $this->audit('listing.grant.create', $grant, 'Cấp quyền rao cho resident #'.$grant->resident_id.' — căn #'.$grant->apartment_id);
                Notification::make()->title('Đã cấp quyền rao')->success()->send();
            });
    }

    public function table(Table $table): Table
    {
        $projectId = app(CurrentContext::class)->projectId() ?? 0;

        return $table
            // Sub-select thay vì pluck rồi whereIn mảng: cùng kiểu với
            // BelongsToProject::bootBelongsToProject để tránh chạy hai lượt
            // truy vấn apartments không cần thiết trên mỗi lần render bảng.
            ->query(
                ListingPostingGrant::query()
                    ->whereIn('apartment_id', function ($q) use ($projectId) {
                        $q->select('apartments.id')->from('apartments')
                            ->join('buildings', 'apartments.building_id', '=', 'buildings.id')
                            ->where('buildings.project_id', $projectId);
                    })
                    ->with(['apartment', 'resident', 'grantedBy'])
            )
            ->headerActions([$this->grantAction()])
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('apartment.code')->label('Căn hộ'),
                TextColumn::make('resident.full_name')->label('Người được cấp'),
                TextColumn::make('status')->label('Trạng thái')->badge()
                    ->formatStateUsing(fn (string $state) => $state === ListingPostingGrant::STATUS_ACTIVE ? 'Đang hiệu lực' : 'Đã thu hồi')
                    ->color(fn (string $state) => $state === ListingPostingGrant::STATUS_ACTIVE ? 'success' : 'gray'),
                TextColumn::make('grantedBy.name')->label('Người cấp')->placeholder('—'),
                TextColumn::make('note')->label('Ghi chú')->limit(40)->placeholder('—'),
                TextColumn::make('created_at')->label('Ngày cấp')->dateTime('d/m/Y H:i')->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->label('Trạng thái')
                    ->options([ListingPostingGrant::STATUS_ACTIVE => 'Đang hiệu lực', ListingPostingGrant::STATUS_REVOKED => 'Đã thu hồi']),
            ])
            ->recordActions([
                Action::make('revoke')
                    ->label('Thu hồi')->icon('heroicon-m-x-circle')->color('danger')
                    ->visible(fn (ListingPostingGrant $record) => $record->isActive())
                    ->requiresConfirmation()
                    ->action(function (ListingPostingGrant $record): void {
                        $record->forceFill(['status' => ListingPostingGrant::STATUS_REVOKED])->save();
                        $this->audit('listing.grant.revoke', $record, 'Thu hồi quyền rao resident #'.$record->resident_id.' — căn #'.$record->apartment_id);
                        Notification::make()->title('Đã thu hồi quyền rao')->warning()->send();
                    }),
                Action::make('reactivate')
                    ->label('Kích hoạt lại')->icon('heroicon-m-arrow-path')->color('success')
                    ->visible(fn (ListingPostingGrant $record) => ! $record->isActive())
                    ->requiresConfirmation()
                    ->action(function (ListingPostingGrant $record): void {
                        $record->forceFill(['status' => ListingPostingGrant::STATUS_ACTIVE])->save();
                        $this->audit('listing.grant.reactivate', $record, 'Kích hoạt lại quyền rao resident #'.$record->resident_id.' — căn #'.$record->apartment_id);
                        Notification::make()->title('Đã kích hoạt lại quyền rao')->success()->send();
                    }),
            ])
            ->emptyStateHeading('Chưa cấp quyền rao nào')
            ->striped();
    }

    private function audit(string $action, ListingPostingGrant $grant, string $description): void
    {
        $user = auth()->user();
        AuditLog::create([
            'tenant_id' => $grant->tenant_id,
            'building_id' => $user?->building_id,
            'user_id' => $user?->id,
            'actor_name' => $user?->name,
            'action' => $action,
            'subject_type' => 'ListingPostingGrant',
            'subject_id' => $grant->id,
            'description' => $description,
        ]);
    }
}
