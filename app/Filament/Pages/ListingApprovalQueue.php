<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\ModeratesRealEstateListings;
use App\Models\AuditLog;
use App\Models\Project;
use App\Models\RealEstateListing;
use App\Support\Context\CurrentContext;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use App\Filament\Concerns\AdminListingBreadcrumbs;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * Duyệt tin rao BĐS (real_estate_listings) cho BQL — /admin.
 *
 * Nền tảng API/model đã có từ trước (ListingController::moderate,
 * ListingFeedPublisher, ListingAccessService) nhưng CHƯA có màn web nào để
 * BQL bấm duyệt — trước bản này BQL chỉ có thể duyệt qua app/API thô. Màn
 * này KHÔNG viết lại logic duyệt/đăng feed, chỉ gọi
 * `ModeratesRealEstateListings` (dùng chung với /sa) để hai nơi luôn khớp.
 *
 * ## Phạm vi dữ liệu — vì sao lọc theo project_id chứ không chỉ tenant
 *
 * `RealEstateListing` chỉ có `BelongsToTenant` (không có `BelongsToProject`),
 * nên global scope model chỉ chặn được ở mức TENANT (công ty vận hành), chưa
 * chặn ở mức DỰ ÁN. BQL trong app này làm việc theo NGỮ CẢNH MỘT DỰ ÁN
 * (`CurrentContext::projectId()` — xem docblock của chính class đó), giống
 * cách `StatementApprovalQueue` lọc theo `buildingIds()`. Lọc tường minh theo
 * `project_id` (+ `tenant_id` phòng hờ) ở `table()->query()` bên dưới là
 * chốt cách ly BẮT BUỘC: một BQL dự án A không được thấy/thao tác tin dự án
 * B dù cùng một tenant, và Filament tự resolve record CHO MỌI action từ
 * chính query này — record ngoài phạm vi không thể bị tác động dù có sửa ID
 * trên request.
 */
class ListingApprovalQueue extends Page implements HasTable
{
    use AdminListingBreadcrumbs;
    use InteractsWithTable;
    use ModeratesRealEstateListings;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-home-modern';

    // "Vận hành": tin rao là một luồng nội dung/vận hành cộng đồng của dự án
    // (cùng nhóm với sự kiện/bình chọn), không phải hồ sơ cư dân — xem
    // Events/CommunityPosts cũng thuộc nhóm này về mặt nghiệp vụ.
    protected static string|\UnitEnum|null $navigationGroup = 'Vận hành';

    protected static ?string $navigationLabel = 'Duyệt tin rao';

    protected static ?int $navigationSort = 40;

    protected static ?string $title = 'Duyệt tin rao BĐS';

    protected static ?string $slug = 'listings/approvals';

    protected string $view = 'filament.pages.listing-approval-queue';

    public static function getNavigationBadge(): ?string
    {
        $projectId = app(CurrentContext::class)->projectId();
        if ($projectId === null) {
            return null;
        }

        $count = RealEstateListing::query()
            ->where('project_id', $projectId)
            ->where('approval_status', RealEstateListing::APPROVAL_PENDING)
            ->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    private function currentProject(): ?Project
    {
        return app(CurrentContext::class)->project();
    }

    /** @return Builder<RealEstateListing> */
    private function scopedQuery(): Builder
    {
        $ctx = app(CurrentContext::class);

        return RealEstateListing::query()
            ->where('project_id', $ctx->projectId() ?? 0)
            ->when($ctx->tenantId(), fn (Builder $q, int $t) => $q->where('tenant_id', $t));
    }

    protected function getViewData(): array
    {
        $pending = (clone $this->scopedQuery())->where('approval_status', RealEstateListing::APPROVAL_PENDING);

        return [
            'kpis' => [
                ['label' => 'Chờ duyệt', 'value' => (clone $pending)->count(), 'accent' => 'amber'],
                ['label' => 'Đã đẩy lên SuperAdmin', 'value' => (clone $pending)->whereNotNull('escalated_at')->count(), 'accent' => 'red'],
                ['label' => 'Đã duyệt', 'value' => (clone $this->scopedQuery())->where('approval_status', RealEstateListing::APPROVAL_APPROVED)->count(), 'accent' => 'green'],
                ['label' => 'Bị từ chối', 'value' => (clone $this->scopedQuery())->where('approval_status', RealEstateListing::APPROVAL_REJECTED)->count(), 'accent' => 'gray'],
            ],
            'project' => $this->currentProject(),
        ];
    }

    /**
     * Action này CỐ Ý đặt ở `$table->headerActions()` (toolbar của bảng),
     * KHÔNG phải `getHeaderActions()` cấp Page — theme /admin hiện tại của dự
     * án ẩn/không kích hoạt được khu vực header-action riêng của Page (đã xác
     * minh: nút "Soạn thông báo" có sẵn của `NotificationCenter` — một màn
     * KHÔNG liên quan gì tới đợt việc này — cũng bị y hệt, nên đây là giới
     * hạn có sẵn của theme, không phải lỗi mới). Toolbar của bảng vẫn hiển
     * thị/bấm được bình thường (đã verify HTTP), nên đặt action ở đây để BQL
     * dùng được NGAY, thay vì chờ sửa theme (ngoài phạm vi việc này).
     */
    private function autoApproveSettingAction(): Action
    {
        $project = $this->currentProject();

        return Action::make('autoApproveSetting')
            ->label('Cài đặt duyệt tự động')
            ->icon('heroicon-o-cog-6-tooth')
            ->color('gray')
            // Đặt ở đây (không phải trang "Cấu hình dự án kế thừa" —
            // ProjectSettingsPreview — vì màn đó là bản preview READ-ONLY
            // những gì HQ/SuperAdmin cấu hình, BQL chỉ xem + xin override,
            // trong khi listings_auto_approve là cờ vận hành của riêng
            // luồng tin rao mà BQL được toàn quyền tự bật/tắt) đặt ngay
            // cạnh màn duyệt cho dễ liên hệ nhân-quả: tắt cờ này thì tin
            // mới vào hàng chờ hiện ở chính bảng bên dưới.
            ->schema([
                Toggle::make('listings_auto_approve')
                    ->label('Tự động duyệt tin rao mới')
                    ->helperText('Bật: tin lên feed ngay khi cư dân đăng. Tắt: tin vào hàng chờ, BQL phải duyệt thủ công ở bảng bên dưới.')
                    ->default((bool) $project?->listings_auto_approve),
            ])
            ->visible(fn () => $project !== null)
            ->action(function (array $data) use ($project): void {
                if ($project === null) {
                    return;
                }
                $project->forceFill(['listings_auto_approve' => (bool) $data['listings_auto_approve']])->save();

                $user = auth()->user();
                AuditLog::create([
                    'tenant_id' => $project->tenant_id,
                    'building_id' => $user?->building_id,
                    'user_id' => $user?->id,
                    'actor_name' => $user?->name,
                    'action' => 'listing.auto_approve_setting',
                    'subject_type' => 'Project',
                    'subject_id' => $project->id,
                    'description' => ($data['listings_auto_approve'] ? 'Bật' : 'Tắt').' duyệt tự động tin rao cho dự án '.$project->name,
                ]);

                Notification::make()
                    ->title($data['listings_auto_approve'] ? 'Đã bật duyệt tự động' : 'Đã tắt duyệt tự động')
                    ->success()->send();
            });
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->scopedQuery()->with(['project', 'apartment', 'createdBy']))
            ->headerActions([$this->autoApproveSettingAction()])
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('title')->label('Tiêu đề')->searchable()->wrap()->limit(60),
                TextColumn::make('project.name')->label('Dự án')->toggleable(),
                TextColumn::make('apartment.code')->label('Căn hộ')->placeholder('—'),
                TextColumn::make('createdBy.name')->label('Người đăng')->placeholder('—'),
                TextColumn::make('type')->label('Loại')->badge()
                    ->formatStateUsing(fn (string $state) => $state === 'rent' ? 'Cho thuê' : 'Bán')
                    ->color(fn (string $state) => $state === 'rent' ? 'info' : 'primary'),
                TextColumn::make('price')->label('Giá')->money('VND')->sortable(),
                TextColumn::make('status')->label('Giao dịch')->badge()
                    ->formatStateUsing(fn (string $state) => ['active' => 'Còn rao', 'pending' => 'Chờ', 'sold' => 'Đã bán', 'rented' => 'Đã cho thuê', 'expired' => 'Hết hạn'][$state] ?? $state)
                    ->color(fn (string $state) => $state === 'active' ? 'success' : 'gray'),
                TextColumn::make('approval_status')->label('Trạng thái duyệt')->badge()
                    ->formatStateUsing(fn (string $state) => ['pending' => 'Chờ duyệt', 'approved' => 'Đã duyệt', 'rejected' => 'Bị từ chối'][$state] ?? $state)
                    ->color(fn (string $state) => ['pending' => 'warning', 'approved' => 'success', 'rejected' => 'danger'][$state] ?? 'gray'),
                TextColumn::make('escalated_at')->label('Đẩy lên SA')->badge()
                    ->formatStateUsing(fn ($state) => $state ? 'Đã đẩy lên' : '—')
                    ->color(fn ($state) => $state ? 'danger' : 'gray'),
                TextColumn::make('interest_count')->label('Quan tâm')->numeric()->sortable(),
                TextColumn::make('inquiry_count')->label('Lead')->numeric()->sortable(),
                TextColumn::make('created_at')->label('Ngày đăng')->dateTime('d/m/Y H:i')->sortable(),
            ])
            ->filters([
                SelectFilter::make('approval_status')->label('Trạng thái duyệt')
                    ->options(['pending' => 'Chờ duyệt', 'approved' => 'Đã duyệt', 'rejected' => 'Bị từ chối']),
                SelectFilter::make('type')->label('Loại')
                    ->options(['sale' => 'Bán', 'rent' => 'Cho thuê']),
                TernaryFilter::make('escalated')
                    ->label('Đã đẩy lên SuperAdmin')
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
                    ->modalDescription('Tin sẽ hiển thị công khai và tự sinh bài trong nhóm "Quan tâm dự án".')
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
                Action::make('escalate')
                    ->label('Đẩy lên SuperAdmin')->icon('heroicon-m-arrow-trending-up')->color('warning')
                    ->visible(fn (RealEstateListing $record) => $record->approval_status === RealEstateListing::APPROVAL_PENDING)
                    ->schema([
                        Textarea::make('note')->label('Vì sao cần SuperAdmin xét')->required()->rows(3)
                            ->helperText('Ví dụ: nghi ngờ môi giới giả, giá bất thường, tranh chấp căn hộ…'),
                    ])
                    ->action(function (array $data, RealEstateListing $record): void {
                        try {
                            $this->escalateListing($record, $data['note'] ?? '');
                        } catch (\InvalidArgumentException $e) {
                            Notification::make()->title($e->getMessage())->danger()->send();

                            return;
                        }
                        Notification::make()->title('Đã đẩy tin rao lên SuperAdmin')->success()->send();
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
            ->emptyStateHeading('Chưa có tin rao nào')
            ->striped()
            ->paginated([10, 25, 50]);
    }
}
