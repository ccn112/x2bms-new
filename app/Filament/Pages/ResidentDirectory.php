<?php

namespace App\Filament\Pages;

use App\Models\AuditLog;
use App\Models\Resident;
use App\Models\ResidentApartmentRelation;
use App\Models\ResidentApprovalRequest;
use App\Filament\Concerns\ImportsResidentsFromExcel;
use App\Filament\Concerns\ResetsResidentPassword;
use App\Support\Context\CurrentContext;
use App\Support\Export\ExportsCsv;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
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
    use ResetsResidentPassword;
    use ExportsCsv;
    use ImportsResidentsFromExcel;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-users';

    protected static string|\UnitEnum|null $navigationGroup = 'Cư dân & Căn hộ';

    protected static ?string $navigationLabel = 'Cư dân';

    protected static ?int $navigationSort = 1;

    protected static ?string $title = 'Danh sách cư dân';

    protected static ?string $slug = 'residents';

    protected string $view = 'filament.pages.resident-directory';

    // --- X2FilterBar (không dùng tab — theo style danh sách căn hộ) ---
    public ?string $fBuilding = null;
    public ?string $fRole = null;    // owner|tenant|member (thay tab Loại cư dân)
    public ?string $fStatus = null;  // active|pending|inactive (thay tab Trạng thái)
    public ?string $fSearch = null;
    public ?string $fCreatedFrom = null;
    public ?string $fCreatedTo = null;
    public ?string $fHasApartment = null; // yes | no

    private const FILTER_LABELS = [
        'fBuilding' => 'Tòa', 'fRole' => 'Loại cư dân', 'fStatus' => 'Trạng thái',
        'fCreatedFrom' => 'Tạo từ', 'fCreatedTo' => 'Tạo đến', 'fHasApartment' => 'Gắn căn',
    ];

    private const ADVANCED_KEYS = ['fCreatedFrom', 'fCreatedTo', 'fHasApartment'];

    /** status → tone của x-x2.status-badge (green|amber|red|slate) cho card mobile. */
    private const STATUS_TONE = ['active' => 'green', 'pending' => 'amber', 'inactive' => 'red'];

    /** Cột bật/tắt hiển thị (dropdown "Cột" nhóm cùng filter bar). key => nhãn. */
    private const COLS = [
        'code' => 'Mã CD', 'avatar' => 'Ảnh', 'full_name' => 'Họ và tên', 'phone' => 'SĐT', 'building' => 'Tòa',
        'apartment' => 'Căn hộ', 'role' => 'Loại cư dân', 'status' => 'Trạng thái', 'created_at' => 'Ngày tạo',
    ];

    /** @var array<string,bool> cột đang hiển thị (khởi tạo tất cả true để checkbox khớp thực tế). */
    public array $cols = [];

    public function mount(): void
    {
        $this->cols = array_fill_keys(array_keys(self::COLS), true);
    }

    /** Nút "Áp dụng" — deferred wire:model commit khi bấm (không cần logic thêm). */
    public function applyCols(): void {}

    public function resetCols(): void
    {
        $this->cols = array_fill_keys(array_keys(self::COLS), true);
    }

    private function colShown(string $key): bool
    {
        return $this->cols[$key] ?? true;
    }

    public function getBreadcrumbs(): array
    {
        return [
            url('/admin') => $this->crumb('heroicon-m-home', 'Tổng quan'),
            $this->crumb('heroicon-m-users', 'Cư dân'),
        ];
    }

    private function crumb(string $icon, string $label): HtmlString
    {
        return new HtmlString('<span class="inline-flex items-center gap-1">'
            .svg($icon, 'h-4 w-4 shrink-0 opacity-70')->toHtml().'<span>'.e($label).'</span></span>');
    }

    protected function getHeaderActions(): array
    {
        return [
            $this->residentImportAction(),
            Action::make('export')->label('Xuất dữ liệu')->icon('heroicon-m-arrow-down-tray')->color('gray')
                ->action('export'),
            Action::make('create')->label('Thêm cư dân')->icon('heroicon-m-plus')->color('gold')
                ->url(url('/fila/residents/create')),
        ];
    }

    /** Đổi filter/tab → về trang 1 + XÓA cache records (bắt buộc, xem LISTING_PAGE_STANDARD). */
    private function refreshTable(): void
    {
        $this->resetPage($this->getTablePaginationPageName());
        $this->flushCachedTableRecords();
        $this->resetTableSearch();
    }

    public function updated(string $property): void
    {
        if (str_starts_with($property, 'f') && array_key_exists($property, array_flip([...array_keys(self::FILTER_LABELS), 'fSearch']))) {
            $this->refreshTable();
        }
    }

    private function buildingIds(): array
    {
        return app(CurrentContext::class)->buildingIds() ?: [0];
    }

    /** Áp toàn bộ filter nghiệp vụ (dùng chung bảng + KPI + export). */
    private function applyFilters(\Illuminate\Database\Eloquent\Builder $q): \Illuminate\Database\Eloquent\Builder
    {
        return $q
            ->when($this->fBuilding, fn ($q, $v) => $q->where('building_id', $v))
            ->when($this->fRole, fn ($q, $v) => $q->whereHas('apartmentRelations', fn ($r) => $r->where('role', $v)))
            ->when($this->fStatus, fn ($q, $v) => $q->where('status', $v))
            ->when($this->fSearch, fn ($q, $v) => $q->where(fn ($w) => $w
                ->where('code', 'like', "%{$v}%")->orWhere('full_name', 'like', "%{$v}%")
                ->orWhere('phone', 'like', "%{$v}%")->orWhere('email', 'like', "%{$v}%")))
            ->when($this->fCreatedFrom, fn ($q, $v) => $q->whereDate('created_at', '>=', $v))
            ->when($this->fCreatedTo, fn ($q, $v) => $q->whereDate('created_at', '<=', $v))
            ->when($this->fHasApartment === 'yes', fn ($q) => $q->whereHas('apartmentRelations'))
            ->when($this->fHasApartment === 'no', fn ($q) => $q->whereDoesntHave('apartmentRelations'));
    }

    private function baseQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return $this->applyFilters(Resident::query()->whereIn('building_id', $this->buildingIds()));
    }

    /** Query bảng (kèm eager load). */
    private function filteredQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return $this->applyFilters(
            Resident::query()->whereIn('building_id', $this->buildingIds())
                ->with(['apartmentRelations.apartment.floor', 'building'])
        );
    }

    private function filterOptions(): array
    {
        $ctx = app(CurrentContext::class);

        return [
            'buildings' => $ctx->buildings()->pluck('name', 'id')->all(),
            'roles' => self::ROLES,
            'statuses' => collect(self::STATUS)->map(fn ($v) => $v[0])->all(),
        ];
    }

    private function activeChips(): array
    {
        $opts = $this->filterOptions();
        $display = [
            'fBuilding' => fn ($v) => $opts['buildings'][$v] ?? $v,
            'fRole' => fn ($v) => self::ROLES[$v] ?? $v,
            'fStatus' => fn ($v) => self::STATUS[$v][0] ?? $v,
            'fCreatedFrom' => fn ($v) => $v, 'fCreatedTo' => fn ($v) => $v,
            'fHasApartment' => fn ($v) => $v === 'yes' ? 'Đã gắn căn' : 'Chưa gắn căn',
        ];
        $chips = [];
        foreach (self::FILTER_LABELS as $key => $label) {
            if (filled($this->{$key})) {
                $chips[] = ['key' => $key, 'label' => $label, 'value' => ($display[$key])($this->{$key})];
            }
        }

        return $chips;
    }

    private function advancedCount(): int
    {
        return count(array_filter(self::ADVANCED_KEYS, fn ($k) => filled($this->{$k})));
    }

    public function clearFilter(string $key): void
    {
        if (array_key_exists($key, array_flip([...array_keys(self::FILTER_LABELS), 'fSearch']))) {
            $this->{$key} = null;
            $this->refreshTable();
        }
    }

    public function clearAllFilters(): void
    {
        foreach ([...array_keys(self::FILTER_LABELS), 'fSearch'] as $key) {
            $this->{$key} = null;
        }
        $this->refreshTable();
    }

    public function clearAdvanced(): void
    {
        foreach (self::ADVANCED_KEYS as $key) {
            $this->{$key} = null;
        }
        $this->refreshTable();
    }

    /** Dữ liệu card mobile. */
    public function cardMeta(Resident $r): array
    {
        $ap = $r->primaryRelation()?->apartment;

        return [
            'apartment' => $ap?->code ?? '—',
            'apartmentId' => $ap?->id,
            'role' => self::ROLES[$r->primaryRelation()?->role] ?? '—',
            'statusLabel' => self::STATUS[$r->status][0] ?? $r->status,
            'statusTone' => self::STATUS_TONE[$r->status] ?? 'slate',
        ];
    }

    /** Xuất CSV cư dân theo filter hiện tại + audit. Streaming CSV dùng trait chung. */
    public function export()
    {
        $rows = $this->filteredQuery()->reorder()->orderBy('code')->get();
        $this->audit('resident.export', 'Xuất danh sách cư dân ('.$rows->count().' dòng)');

        return $this->streamCsv(
            $rows,
            ['Mã CD', 'Họ tên', 'SĐT', 'Email', 'Tòa', 'Căn hộ', 'Loại', 'Trạng thái'],
            fn (Resident $r): array => [
                $r->code, $r->full_name, $r->phone, $r->email, $r->building?->name,
                $r->primaryRelation()?->apartment?->code ?? '',
                self::ROLES[$r->primaryRelation()?->role] ?? '',
                self::STATUS[$r->status][0] ?? $r->status,
            ],
            'residents',
        );
    }

    /** @var array<string, array{0:string,1:string}> status => [label, color Filament] */
    private const STATUS = [
        'active' => ['Hoạt động', 'success'],
        'pending' => ['Chờ duyệt', 'warning'],
        'inactive' => ['Tạm khóa', 'danger'],
    ];

    private const ROLES = ['owner' => 'Chủ sở hữu', 'tenant' => 'Người thuê', 'member' => 'Thành viên'];

    protected function getViewData(): array
    {
        // KPI = breakdown theo trạng thái, tính lại theo BỘ LỌC hiện tại (owner 2026-07-17).
        // 1 query gộp theo status thay vì 4 lần count().
        $byStatus = $this->baseQuery()->select('status')->selectRaw('count(*) as c')->groupBy('status')->pluck('c', 'status');
        $total = (int) $byStatus->sum();
        $active = (int) ($byStatus['active'] ?? 0);
        $pending = (int) ($byStatus['pending'] ?? 0);
        $locked = (int) ($byStatus['inactive'] ?? 0);
        $missing = $this->baseQuery()->where(fn ($q) => $q->whereNull('id_no')->orWhere('id_no', ''))->count();
        $pct = fn (int $n) => $total ? round($n / $total * 100, 1).'%' : '0%';

        return [
            'kpis' => [
                ['label' => 'Tổng cư dân', 'value' => number_format($total, 0, ',', '.'), 'accent' => 'blue', 'icon' => 'heroicon-o-users', 'sub' => '100% kết quả lọc'],
                ['label' => 'Hoạt động', 'value' => number_format($active, 0, ',', '.'), 'accent' => 'green', 'icon' => 'heroicon-o-arrow-trending-up', 'sub' => $pct($active)],
                ['label' => 'Chờ duyệt', 'value' => number_format($pending, 0, ',', '.'), 'accent' => 'amber', 'icon' => 'heroicon-o-clock', 'sub' => $pct($pending)],
                ['label' => 'Tạm khóa', 'value' => number_format($locked, 0, ',', '.'), 'accent' => 'red', 'icon' => 'heroicon-o-lock-closed', 'sub' => $pct($locked)],
                ['label' => 'Thiếu dữ liệu', 'value' => number_format($missing, 0, ',', '.'), 'accent' => 'teal', 'icon' => 'heroicon-o-exclamation-triangle', 'sub' => $pct($missing).' (thiếu CCCD)'],
            ],
            'filterOptions' => $this->filterOptions(),
            'activeChips' => $this->activeChips(),
            'advancedCount' => $this->advancedCount(),
            'columnToggle' => self::COLS,
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            // Closure (Filament v4 cache Table) — đọc đúng filter + tab hiện tại.
            ->query(fn (): \Illuminate\Database\Eloquent\Builder => $this->filteredQuery())
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('code')
                    ->label('Mã CD')
                    ->sortable()
                    ->color('gray')
                    ->visible(fn (): bool => $this->colShown('code')),
                ImageColumn::make('avatar_url')
                    ->label('Ảnh')
                    ->circular()
                    ->size(36)
                    ->visible(fn (): bool => $this->colShown('avatar')),
                TextColumn::make('full_name')
                    ->label('Họ và tên')
                    ->color('primary')
                    ->url(fn (Resident $r): string => url('/admin/residents/'.$r->id.'/detail'))
                    ->description(fn (Resident $r): ?string => $r->email)
                    ->visible(fn (): bool => $this->colShown('full_name')),
                TextColumn::make('phone')
                    ->label('SĐT')
                    ->icon('heroicon-m-phone')
                    ->visible(fn (): bool => $this->colShown('phone')),
                TextColumn::make('building.name')
                    ->label('Tòa')
                    ->badge()
                    ->color('gray')
                    ->visible(fn (): bool => $this->colShown('building')),
                TextColumn::make('apartment')
                    ->label('Căn hộ')
                    ->state(fn (Resident $r): string => $r->primaryRelation()?->apartment?->code ?? '—')
                    ->color(fn (Resident $r): ?string => $r->primaryRelation()?->apartment ? 'primary' : null)
                    ->url(fn (Resident $r): ?string => ($ap = $r->primaryRelation()?->apartment) ? url('/admin/apartments/'.$ap->id.'/profile') : null)
                    ->visible(fn (): bool => $this->colShown('apartment')),
                TextColumn::make('role')
                    ->label('Loại cư dân')
                    ->badge()
                    ->color('gray')
                    ->state(fn (Resident $r): string => self::ROLES[$r->primaryRelation()?->role] ?? '—')
                    ->visible(fn (): bool => $this->colShown('role')),
                TextColumn::make('status')
                    ->label('Trạng thái')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => self::STATUS[$state][0] ?? $state)
                    ->color(fn (string $state): string => self::STATUS[$state][1] ?? 'gray')
                    ->visible(fn (): bool => $this->colShown('status')),
                TextColumn::make('created_at')
                    ->label('Ngày tạo')
                    ->date('d/m/Y')
                    ->sortable()
                    ->visible(fn (): bool => $this->colShown('created_at')),
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
                Action::make('resetPassword')
                    ->label('Đặt lại mật khẩu')
                    ->iconButton()
                    ->icon('heroicon-m-key')
                    ->color('warning')
                    ->modalWidth('md')
                    ->modalHeading(fn (Resident $r): string => 'Đặt lại mật khẩu — '.$r->full_name)
                    ->modalSubmitActionLabel('Thực hiện')
                    ->schema($this->resetPasswordSchema())
                    ->action(fn (Resident $r, array $data) => $this->handleResidentPasswordReset($r, $data)),
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
            // Bulk action hiện thẳng thành nút (không gom dropdown "Tác vụ hàng loạt").
            ->toolbarActions([
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
            ])
            ->emptyStateHeading('Không tìm thấy cư dân phù hợp')
            ->emptyStateDescription('Không có kết quả nào khớp với bộ lọc hiện tại.')
            ->emptyStateIcon('heroicon-o-users')
            ->emptyStateActions([
                Action::make('clearFilters')
                    ->label('Xóa bộ lọc')
                    ->icon('heroicon-m-x-mark')
                    ->color('gray')
                    ->action(fn () => $this->clearAllFilters()),
                Action::make('addResident')
                    ->label('Thêm cư dân mới')
                    ->icon('heroicon-m-plus')
                    ->url(url('/fila/residents/create')),
            ])
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
