<?php

namespace App\Filament\Pages;

use App\Models\AuditLog;
use App\Models\Resident;
use App\Models\ResidentApartmentRelation;
use App\Models\ResidentApprovalRequest;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\HtmlString;

/**
 * WEB-UX RES-LIST / RES-ACT / RES-BULK — Danh sách cư dân.
 * Filament table (search / filter / sort / pagination / row + bulk actions)
 * on the X2-BMS shell, with KPI cards above.
 */
class ResidentDirectory extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-users';

    protected static string|\UnitEnum|null $navigationGroup = 'Cư dân & Căn hộ';

    protected static ?string $navigationLabel = 'Cư dân';

    protected static ?int $navigationSort = 1;

    protected static ?string $title = 'Danh sách cư dân';

    protected static ?string $slug = 'residents';

    protected string $view = 'filament.pages.resident-directory';

    /** DS-01-05 tab row: all|owner|tenant|pending|locked (scopes the table). */
    public ?string $activeTab = 'all';

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
        $this->resetTableSearch();
    }

    /** Apply the active tab as a query scope (shared by table + tab counts). */
    private function scopeByTab(\Illuminate\Database\Eloquent\Builder $q, string $tab): \Illuminate\Database\Eloquent\Builder
    {
        return match ($tab) {
            'owner' => $q->whereHas('apartmentRelations', fn ($r) => $r->where('role', 'owner')),
            'tenant' => $q->whereHas('apartmentRelations', fn ($r) => $r->where('role', 'tenant')),
            'pending' => $q->where('status', 'pending'),
            'locked' => $q->where('status', 'inactive'),
            default => $q,
        };
    }

    /** @var array<string, array{0:string,1:string}> status => [label, color] */
    private const STATUS = [
        'active' => ['Hoạt động', 'success'],
        'pending' => ['Chờ duyệt', 'warning'],
        'inactive' => ['Đã khóa', 'danger'],
    ];

    private const ROLES = ['owner' => 'Chủ sở hữu', 'tenant' => 'Người thuê', 'member' => 'Thành viên'];

    protected function getViewData(): array
    {
        $buildingIds = app(\App\Support\Context\CurrentContext::class)->buildingIds();
        $base = fn () => Resident::query()->whereIn('building_id', $buildingIds);

        $total = $base()->count();
        $active = (clone $base())->where('status', 'active')->count();
        $pending = (clone $base())->where('status', 'pending')->count();
        $locked = (clone $base())->where('status', 'inactive')->count();
        $updatedToday = (clone $base())->whereDate('updated_at', now())->count();

        // KPIs are context-wide totals (DS-01: never react to table filters/tabs).
        return [
            'kpis' => [
                ['label' => 'Tổng cư dân', 'value' => number_format($total, 0, ',', '.'), 'accent' => 'blue', 'icon' => 'heroicon-o-users', 'sub' => '100% tổng cư dân'],
                ['label' => 'Đang hoạt động', 'value' => number_format($active, 0, ',', '.'), 'accent' => 'green', 'icon' => 'heroicon-o-arrow-trending-up', 'sub' => $total ? round($active / $total * 100, 1).'%' : '0%'],
                ['label' => 'Chờ duyệt', 'value' => number_format($pending, 0, ',', '.'), 'accent' => 'amber', 'icon' => 'heroicon-o-clock', 'sub' => 'Hồ sơ mới'],
                ['label' => 'Đã khóa', 'value' => number_format($locked, 0, ',', '.'), 'accent' => 'red', 'icon' => 'heroicon-o-lock-closed', 'sub' => $total ? round($locked / $total * 100, 1).'%' : '0%'],
                ['label' => 'Cập nhật gần đây', 'value' => number_format($updatedToday, 0, ',', '.'), 'accent' => 'teal', 'icon' => 'heroicon-o-clock', 'sub' => 'Hôm nay'],
            ],
            'tabs' => [
                ['key' => 'all', 'label' => 'Tất cả', 'count' => $total],
                ['key' => 'owner', 'label' => 'Chủ sở hữu', 'count' => $this->scopeByTab($base(), 'owner')->count()],
                ['key' => 'tenant', 'label' => 'Người thuê', 'count' => $this->scopeByTab($base(), 'tenant')->count()],
                ['key' => 'pending', 'label' => 'Chờ duyệt', 'count' => $pending],
                ['key' => 'locked', 'label' => 'Đã khóa', 'count' => $locked],
            ],
        ];
    }

    public function table(Table $table): Table
    {
        $ctx = app(\App\Support\Context\CurrentContext::class);
        $buildingIds = $ctx->buildingIds();
        $buildingOptions = $ctx->buildings()->pluck('name', 'id')->all();

        return $table
            ->query($this->scopeByTab(
                Resident::query()->whereIn('building_id', $buildingIds)->with(['apartmentRelations.apartment.floor', 'building']),
                $this->activeTab ?? 'all',
            ))
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('code')
                    ->label('Mã CD')
                    ->searchable()
                    ->sortable()
                    ->color('gray'),
                TextColumn::make('full_name')
                    ->label('Họ và tên')
                    ->searchable()
                    ->color('primary')
                    ->url(fn (Resident $r): string => url('/admin/residents/'.$r->id.'/detail'))
                    ->description(fn (Resident $r): ?string => $r->email),
                TextColumn::make('phone')
                    ->label('SĐT')
                    ->searchable()
                    ->icon('heroicon-m-phone'),
                TextColumn::make('building.name')
                    ->label('Tòa')
                    ->badge()
                    ->color('gray')
                    ->toggleable(),
                TextColumn::make('apartment')
                    ->label('Căn hộ')
                    ->state(fn (Resident $r): string => $r->primaryRelation()?->apartment?->code ?? '—'),
                TextColumn::make('role')
                    ->label('Loại cư dân')
                    ->badge()
                    ->color('gray')
                    ->state(fn (Resident $r): string => self::ROLES[$r->primaryRelation()?->role] ?? '—'),
                TextColumn::make('status')
                    ->label('Trạng thái')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => self::STATUS[$state][0] ?? $state)
                    ->color(fn (string $state): string => self::STATUS[$state][1] ?? 'gray'),
                TextColumn::make('created_at')
                    ->label('Ngày tạo')
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('building_id')
                    ->label('Tòa')
                    ->options($buildingOptions),
                SelectFilter::make('status')
                    ->label('Trạng thái')
                    ->options([
                        'active' => 'Hoạt động',
                        'pending' => 'Chờ duyệt',
                        'inactive' => 'Đã khóa',
                    ]),
            ])
            ->recordActions([
                Action::make('quickView')
                    ->label('Xem nhanh')
                    ->iconButton()
                    ->icon('heroicon-m-eye')
                    ->color('gray')
                    ->slideOver()
                    ->modalHeading('Hồ sơ nhanh')
                    ->modalContent(fn ($record) => view('filament.pages.partials.resident-quick', ['r' => $record]))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Đóng'),
                Action::make('edit')
                    ->label('Sửa')
                    ->iconButton()
                    ->icon('heroicon-m-pencil-square')
                    ->color('gray')
                    ->url(fn (Resident $r): string => url('/fila/residents/'.$r->id.'/edit')),
                ActionGroup::make([
                    Action::make('linkApartment')->label('Gắn căn hộ')->icon('heroicon-m-home')->url('#'),
                    Action::make('resendActivation')->label('Gửi lại mã kích hoạt')->icon('heroicon-m-key')->url('#'),
                    Action::make('notify')->label('Gửi thông báo')->icon('heroicon-m-megaphone')->url('#'),
                    Action::make('createTask')->label('Tạo công việc')->icon('heroicon-m-clipboard-document-list')->url('#'),
                    Action::make('lock')
                        ->label('Khóa tài khoản')
                        ->icon('heroicon-m-lock-closed')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading('Khóa tài khoản cư dân')
                        ->modalDescription('Cư dân sẽ không thể đăng nhập cho tới khi được mở khóa.')
                        ->schema([
                            Textarea::make('reason')->label('Lý do khóa')->required()->rows(3),
                        ])
                        ->action(function (Resident $r, array $data): void {
                            $r->update(['status' => 'inactive']);
                            $this->audit('resident.lock', "Khóa tài khoản {$r->full_name}: {$data['reason']}");
                            Notification::make()->title('Đã khóa tài khoản')->success()->send();
                        }),
                    Action::make('history')->label('Lịch sử')->icon('heroicon-m-clock')->url('#'),
                ])->icon('heroicon-m-ellipsis-vertical')->color('gray'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('approve')
                        ->label('Duyệt / kích hoạt')
                        ->icon('heroicon-m-check-circle')
                        ->color('success')
                        ->modalHeading('Bulk Approve — Duyệt cư dân hàng loạt')
                        ->modalWidth('5xl')
                        ->steps([
                            Step::make('Kiểm tra hợp lệ')
                                ->description('Xem lại & kiểm tra dữ liệu')
                                ->icon('heroicon-o-clipboard-document-check')
                                ->schema([
                                    Placeholder::make('validation')
                                        ->hiddenLabel()
                                        ->content(fn ($livewire) => $this->approvalSummary($livewire->getSelectedTableRecords())),
                                ]),
                            Step::make('Cấu hình duyệt')
                                ->description('Vai trò, kích hoạt, thông báo')
                                ->icon('heroicon-o-adjustments-horizontal')
                                ->schema([
                                    Select::make('role')
                                        ->label('Vai trò mặc định')
                                        ->options(self::ROLES)
                                        ->default('owner'),
                                    DatePicker::make('activated_at')
                                        ->label('Ngày kích hoạt')
                                        ->default(now())
                                        ->native(false),
                                    Toggle::make('notify')
                                        ->label('Gửi thông báo kích hoạt cho cư dân')
                                        ->default(true),
                                ]),
                            Step::make('Xác nhận')
                                ->description('Hoàn tất')
                                ->icon('heroicon-o-check-badge')
                                ->schema([
                                    Placeholder::make('confirm')
                                        ->hiddenLabel()
                                        ->content(fn ($livewire) => new HtmlString(
                                            '<p class="text-sm text-gray-600">Hệ thống sẽ kích hoạt <span class="font-semibold text-gray-900">'
                                            .$livewire->getSelectedTableRecords()->where('status', 'pending')->count()
                                            .'</span> cư dân hợp lệ (đang chờ duyệt). Các cư dân không hợp lệ sẽ được bỏ qua.</p>'
                                        )),
                                ]),
                        ])
                        ->action(function (Collection $records, array $data): void {
                            $eligible = $records->where('status', 'pending');
                            $eligible->each->update(['status' => 'active']);
                            $this->audit('resident.bulk_approve', 'Kích hoạt '.$eligible->count().'/'.$records->count().' cư dân (vai trò '.($data['role'] ?? '—').')');
                            Notification::make()
                                ->title('Đã kích hoạt '.$eligible->count().' cư dân')
                                ->body($records->count() - $eligible->count() > 0 ? 'Bỏ qua '.($records->count() - $eligible->count()).' hồ sơ không hợp lệ.' : null)
                                ->success()->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                    BulkAction::make('lock')
                        ->label('Khóa tài khoản')
                        ->icon('heroicon-m-lock-closed')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(function (Collection $records): void {
                            $records->each->update(['status' => 'inactive']);
                            $this->audit('resident.bulk_lock', 'Khóa '.$records->count().' cư dân');
                            Notification::make()->title('Đã khóa '.$records->count().' cư dân')->warning()->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                    BulkAction::make('export')
                        ->label('Xuất Excel')
                        ->icon('heroicon-m-arrow-down-tray')
                        ->color('gray')
                        ->action(fn () => Notification::make()->title('Đang chuẩn bị file xuất…')->info()->send()),
                ]),
            ])
            ->emptyStateHeading('Không tìm thấy cư dân phù hợp')
            ->emptyStateDescription('Không có kết quả nào khớp với bộ lọc hiện tại.')
            ->emptyStateIcon('heroicon-o-users')
            ->emptyStateActions([
                Action::make('clearFilters')
                    ->label('Xóa bộ lọc')
                    ->icon('heroicon-m-x-mark')
                    ->color('gray')
                    ->action(function (): void {
                        $this->resetTableFiltersForm();
                        $this->resetTableSearch();
                    }),
                Action::make('addResident')
                    ->label('Thêm cư dân mới')
                    ->icon('heroicon-m-plus')
                    ->url(url('/fila/residents/create')),
            ])
            ->striped()
            ->paginated([10, 25, 50, 100]);
    }

    /** Valid/needs-review summary for the bulk-approve wizard (RES-BULK-02). */
    private function approvalSummary(Collection $records): HtmlString
    {
        $total = $records->count();
        $valid = $records->filter(fn ($r) => $r->status === 'pending' && filled($r->phone))->count();
        $review = $total - $valid;

        $card = fn (string $label, int $value, string $color) => '<div class="flex-1 rounded-xl border border-gray-100 px-4 py-3">'
            .'<div class="text-xs text-gray-500">'.$label.'</div>'
            .'<div class="mt-1 text-2xl font-bold text-'.$color.'">'.$value.'</div></div>';

        return new HtmlString(
            '<div class="flex gap-3">'
            .$card('Tổng dữ liệu', $total, 'gray-900')
            .$card('Hợp lệ', $valid, 'success-600')
            .$card('Cần xem xét', $review, 'warning-600')
            .'</div>'
            .'<p class="mt-3 text-sm text-gray-500">Chỉ những hồ sơ <b>đang chờ duyệt</b> và có số điện thoại mới được kích hoạt. '
            .($review > 0 ? '<span class="text-warning-600 font-medium">'.$review.' hồ sơ</span> cần xem xét sẽ được bỏ qua.' : 'Tất cả hồ sơ đều hợp lệ.').'</p>'
        );
    }

    private function audit(string $action, string $description): void
    {
        $user = auth()->user();
        AuditLog::create([
            'tenant_id' => $user->tenant_id,
            'building_id' => $user->building_id,
            'user_id' => $user->id,
            'actor_name' => $user->name,
            'action' => $action,
            'description' => $description,
        ]);
    }
}
