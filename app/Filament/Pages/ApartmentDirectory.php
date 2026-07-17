<?php

namespace App\Filament\Pages;

use App\Models\Apartment;
use App\Models\AuditLog;
use App\Models\Debt;
use App\Models\Floor;
use App\Models\ResidentApartmentRelation;
use App\Support\Context\CurrentContext;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;

/**
 * BQL-01-05 — Danh sách căn hộ (master data). Bám thiết kế: tiêu đề ở topbar,
 * tab trang (Danh sách / Cây / Duyệt gắn) cùng hàng action, KPI 5 card theo context,
 * bộ lọc đầy đủ, bảng Filament chuẩn (search/sort/filter/row+bulk/pagination).
 * Scope theo CurrentContext (dự án hiện tại) — cùng khuôn với ResidentDirectory.
 */
class ApartmentDirectory extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-home-modern';

    protected static string|\UnitEnum|null $navigationGroup = 'Cư dân & Căn hộ';

    protected static ?string $navigationLabel = 'Hồ sơ căn hộ';

    protected static ?int $navigationSort = 2;

    protected static ?string $title = 'Hồ sơ căn hộ';

    protected static ?string $slug = 'apartments';

    protected string $view = 'filament.pages.apartment-directory';

    // --- X2FilterBar state (business filters own the query, not Filament's panel) ---
    /** @var array<string,string> map property → nhãn chip */
    private const FILTER_LABELS = [
        'fBuilding' => 'Tòa', 'fFloor' => 'Tầng', 'fType' => 'Loại căn',
        'fStatus' => 'Trạng thái', 'fHolder' => 'Chủ thể',
        'fAreaMin' => 'DT từ', 'fAreaMax' => 'DT đến',
        'fUpdatedFrom' => 'Cập nhật từ', 'fUpdatedTo' => 'Cập nhật đến', 'fDebt' => 'Công nợ',
    ];

    /** Bộ lọc nâng cao (trong drawer) — để tính badge số lượng. */
    private const ADVANCED_KEYS = ['fAreaMin', 'fAreaMax', 'fUpdatedFrom', 'fUpdatedTo', 'fDebt'];

    // Inline filters
    public ?string $fBuilding = null;
    public ?string $fFloor = null;
    public ?string $fType = null;
    public ?string $fStatus = null;
    public ?string $fHolder = null;   // owner | tenant
    public ?string $fSearch = null;
    // Advanced filters (drawer)
    public ?string $fAreaMin = null;
    public ?string $fAreaMax = null;
    public ?string $fUpdatedFrom = null;
    public ?string $fUpdatedTo = null;
    public ?string $fDebt = null;     // with | none

    /** @var array<string,array<string,string>>|null memo bảng options filter */
    private ?array $filterOptionsCache = null;

    /** Reset phân trang khi bất kỳ filter nào đổi (Task 5: filter tác động query). */
    public function updated(string $property): void
    {
        if (str_starts_with($property, 'f') && array_key_exists($property, $this->allFilterProps())) {
            $this->refreshTable();
        }
    }

    /** Áp filter mới vào bảng: về trang 1 + XÓA cache records của Filament
        (resetPage không xóa cache → nếu thiếu, bảng vẫn hiện kết quả mặc định cũ). */
    private function refreshTable(): void
    {
        $this->resetPage($this->getTablePaginationPageName());
        $this->flushCachedTableRecords();
    }

    /** @return array<string,bool> tất cả tên property filter (để nhận diện trong updated). */
    private function allFilterProps(): array
    {
        return array_flip([...array_keys(self::FILTER_LABELS), 'fSearch']);
    }

    /** Trạng thái sử dụng căn: [nhãn, màu badge Filament]. */
    private const STATUS = [
        'occupied' => ['Đã ở', 'success'],
        'vacant' => ['Trống', 'gray'],
        'pending_attach' => ['Chờ gắn cư dân', 'warning'],
        'maintenance' => ['Đang sửa chữa', 'info'],
        'handover_pending' => ['Chờ bàn giao', 'warning'],
        'locked' => ['Khóa', 'danger'],
    ];

    private const ROLES = ['owner' => 'Chủ sở hữu', 'tenant' => 'Người thuê', 'member' => 'Thành viên'];

    /** Map màu badge Filament → class Tailwind (dùng cho card mobile). */
    private const BADGE = [
        'success' => 'bg-emerald-50 text-emerald-700',
        'gray' => 'bg-slate-100 text-slate-600',
        'warning' => 'bg-amber-50 text-amber-700',
        'info' => 'bg-sky-50 text-sky-700',
        'danger' => 'bg-red-50 text-red-700',
    ];

    /** Cột bật/tắt hiển thị (dropdown "Cột" trong filter bar). key => nhãn. */
    private const COLS = [
        'code' => 'Mã căn', 'building' => 'Tòa', 'floor' => 'Tầng', 'area' => 'Diện tích', 'type' => 'Loại căn',
        'status' => 'Trạng thái', 'holder' => 'Chủ thể', 'residents' => 'Số cư dân', 'debt' => 'Công nợ', 'updated' => 'Cập nhật',
    ];

    /** @var array<string,bool> cột đang hiển thị (khởi tạo tất cả true để checkbox khớp). */
    public array $cols = [];

    public function mount(): void
    {
        $this->cols = array_fill_keys(array_keys(self::COLS), true);
    }

    public function applyCols(): void {}

    public function resetCols(): void
    {
        $this->cols = array_fill_keys(array_keys(self::COLS), true);
    }

    private function colShown(string $key): bool
    {
        return $this->cols[$key] ?? true;
    }

    private function ctx(): CurrentContext
    {
        return app(CurrentContext::class);
    }

    private function buildingIds(): array
    {
        return $this->ctx()->buildingIds() ?: [0];
    }


    /** Breadcrumb Filament — mỗi mục có icon; 2 mục đầu click được, mục cuối = trang hiện tại. */
    public function getBreadcrumbs(): array
    {
        return [
            url('/admin') => $this->crumb('heroicon-m-home', 'Tổng quan'),
            url('/admin/residents') => $this->crumb('heroicon-m-user-group', 'Cư dân & Căn hộ'),
            $this->crumb('heroicon-m-home-modern', 'Hồ sơ căn hộ'), // key int → trang hiện tại (không link)
        ];
    }

    /** Nhãn breadcrumb kèm icon (HtmlString → Filament render raw thay vì escape). */
    private function crumb(string $icon, string $label): HtmlString
    {
        return new HtmlString(
            '<span class="inline-flex items-center gap-1">'
            .svg($icon, 'h-4 w-4 shrink-0 opacity-70')->toHtml()
            .'<span>'.e($label).'</span>'
            .'</span>'
        );
    }

    /** Action trang ở header Filament mặc định (phải), ngang hàng breadcrumb. */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('import')
                ->label('Nhập dữ liệu')
                ->icon('heroicon-m-arrow-up-tray')
                ->color('gray')
                ->action('notifyImport'),
            Action::make('export')
                ->label('Xuất dữ liệu')
                ->icon('heroicon-m-arrow-down-tray')
                ->color('gray')
                ->action('export'),
            Action::make('create')
                ->label('Thêm căn hộ')
                ->icon('heroicon-m-plus')
                ->color('gold') // nền vàng brand CTA
                ->url(url('/fila/apartments/create')),
        ];
    }

    protected function getViewData(): array
    {
        // KPI tính lại theo BỘ LỌC hiện tại (owner 2026-07-17: KPI phản ánh kết quả lọc,
        // không còn "bất biến theo context"). Dùng chung filteredQuery() với bảng.
        $base = fn () => $this->filteredQuery();

        $total = $base()->count();
        $occupied = (clone $base())->where('status', 'occupied')->count();
        $vacant = (clone $base())->where('status', 'vacant')->count();
        $pendingAttach = (clone $base())->where('status', 'pending_attach')->count();

        $aptIds = (clone $base())->pluck('id');
        $withDebt = Debt::whereIn('apartment_id', $aptIds)->where('is_overdue', true)->distinct()->count('apartment_id');

        $pct = fn (int $n) => $total ? number_format($n / $total * 100, 1, ',', '.').'%' : '0%';

        return [
            'filterOptions' => $this->filterOptions(),
            'activeChips' => $this->activeChips(),
            'advancedCount' => $this->advancedCount(),
            'columnToggle' => self::COLS,
            'kpis' => [
                ['label' => 'Tổng căn', 'value' => number_format($total, 0, ',', '.'), 'accent' => 'blue', 'icon' => 'heroicon-o-building-office-2', 'sub' => '100% tổng danh mục'],
                ['label' => 'Đã ở', 'value' => number_format($occupied, 0, ',', '.'), 'accent' => 'green', 'icon' => 'heroicon-o-user-group', 'sub' => $pct($occupied)],
                ['label' => 'Trống', 'value' => number_format($vacant, 0, ',', '.'), 'accent' => 'teal', 'icon' => 'heroicon-o-home', 'sub' => $pct($vacant)],
                ['label' => 'Đang duyệt gắn', 'value' => number_format($pendingAttach, 0, ',', '.'), 'accent' => 'amber', 'icon' => 'heroicon-o-clock', 'sub' => $pct($pendingAttach)],
                ['label' => 'Nợ phí', 'value' => number_format($withDebt, 0, ',', '.'), 'accent' => 'red', 'icon' => 'heroicon-o-banknotes', 'sub' => $pct($withDebt).' căn có nợ'],
            ],
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            // Closure (không phải Builder tĩnh): Filament cache Table nên nếu truyền
            // Builder dựng sẵn thì filter mới KHÔNG vào query. Closure được gọi lại
            // mỗi lần lấy records → đọc đúng $this->f* hiện tại.
            ->query(fn (): Builder => $this->filteredQuery())
            ->defaultSort('code')
            ->columns([
                TextColumn::make('code')
                    ->label('Mã căn')
                    ->sortable()
                    ->color('primary')
                    ->weight('medium')
                    ->url(fn (Apartment $a): string => url('/admin/apartments/'.$a->id.'/profile'))
                    ->visible(fn (): bool => $this->colShown('code')),
                TextColumn::make('building.name')
                    ->label('Tòa')
                    ->badge()
                    ->color('gray')
                    ->sortable()
                    ->visible(fn (): bool => $this->colShown('building')),
                TextColumn::make('floor.name')
                    ->label('Tầng')
                    ->placeholder('—')
                    ->sortable()
                    ->visible(fn (): bool => $this->colShown('floor')),
                TextColumn::make('area_sqm')
                    ->label('Diện tích (m²)')
                    ->formatStateUsing(fn ($state): string => $state ? number_format((float) $state, 1, ',', '.') : '—')
                    ->sortable()
                    ->visible(fn (): bool => $this->colShown('area')),
                TextColumn::make('type')
                    ->label('Loại căn')
                    ->placeholder('—')
                    ->visible(fn (): bool => $this->colShown('type')),
                TextColumn::make('status')
                    ->label('Trạng thái ở')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => self::STATUS[$state][0] ?? ($state ?? '—'))
                    ->color(fn (?string $state): string => self::STATUS[$state][1] ?? 'gray')
                    ->visible(fn (): bool => $this->colShown('status')),
                TextColumn::make('current_holder')
                    ->label('Chủ thể hiện tại')
                    ->state(fn (Apartment $a): string => $this->holderFor($a)['name'])
                    ->description(fn (Apartment $a): ?string => $this->holderFor($a)['role'])
                    ->visible(fn (): bool => $this->colShown('holder')),
                TextColumn::make('resident_count')
                    ->label('Số cư dân')
                    ->alignCenter()
                    ->state(fn (Apartment $a): int => ResidentApartmentRelation::where('apartment_id', $a->id)->count())
                    ->visible(fn (): bool => $this->colShown('residents')),
                TextColumn::make('debt')
                    ->label('Công nợ (VND)')
                    ->alignEnd()
                    ->state(fn (Apartment $a): float => (float) Debt::where('apartment_id', $a->id)->where('is_overdue', true)->sum('amount'))
                    ->formatStateUsing(fn (float $state): string => number_format($state, 0, ',', '.'))
                    ->color(fn (float $state): string => $state > 0 ? 'danger' : 'success')
                    ->visible(fn (): bool => $this->colShown('debt')),
                TextColumn::make('updated_at')
                    ->label('Cập nhật')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->color('gray')
                    ->visible(fn (): bool => $this->colShown('updated')),
            ])
            ->recordActions([
                Action::make('view')
                    ->label('Hồ sơ')
                    ->iconButton()->icon('heroicon-m-eye')->color('gray')
                    ->url(fn (Apartment $a): string => url('/admin/apartments/'.$a->id.'/profile')),
                Action::make('edit')
                    ->label('Sửa')
                    ->iconButton()->icon('heroicon-m-pencil-square')->color('gray')
                    ->url(fn (Apartment $a): string => url('/fila/apartments/'.$a->id.'/edit')),
                ActionGroup::make([
                    Action::make('attachResident')->label('Gắn cư dân')->icon('heroicon-m-user-plus')->url(url('/admin/residents/binding-queue')),
                    Action::make('changeStatus')->label('Đổi trạng thái')->icon('heroicon-m-arrow-path')->url('#'),
                    Action::make('dossier')->label('Xuất hồ sơ căn')->icon('heroicon-m-document-arrow-down')->url('#'),
                    Action::make('history')->label('Lịch sử')->icon('heroicon-m-clock')->url(url('/admin/apartments/tree')),
                ])->icon('heroicon-m-ellipsis-vertical')->color('gray'),
            ])
            // Bulk action hiện thẳng thành nút (không gom dropdown).
            ->toolbarActions([
                BulkAction::make('export')
                    ->label('Xuất dữ liệu')
                    ->icon('heroicon-m-arrow-down-tray')
                    ->color('gray')
                    ->action(fn () => Notification::make()->title('Đang chuẩn bị file xuất…')->info()->send()),
            ])
            ->emptyStateHeading('Không tìm thấy căn hộ phù hợp')
            ->emptyStateDescription('Không có kết quả nào khớp với bộ lọc hiện tại.')
            ->emptyStateIcon('heroicon-o-home-modern')
            ->striped()
            ->paginated([10, 25, 50, 100]);
    }

    /** Query bảng đã áp toàn bộ filter nghiệp vụ (dùng chung cho bảng + export). */
    private function filteredQuery(): Builder
    {
        return Apartment::query()
            ->whereIn('building_id', $this->buildingIds())
            ->with(['floor', 'building'])
            ->when($this->fBuilding, fn (Builder $q, $v) => $q->where('building_id', $v))
            ->when($this->fFloor, fn (Builder $q, $v) => $q->where('floor_id', $v))
            ->when($this->fType, fn (Builder $q, $v) => $q->where('type', $v))
            ->when($this->fStatus, fn (Builder $q, $v) => $q->where('status', $v))
            ->when($this->fHolder, fn (Builder $q, $v) => $q->whereHas('residents', fn ($r) => $r->where('resident_apartment_relations.role', $v)))
            ->when($this->fSearch, fn (Builder $q, $v) => $q->where(fn (Builder $w) => $w
                ->where('code', 'like', "%{$v}%")
                ->orWhereHas('residents', fn ($r) => $r
                    ->where('full_name', 'like', "%{$v}%")
                    ->orWhere('phone', 'like', "%{$v}%"))))
            ->when($this->fAreaMin, fn (Builder $q, $v) => $q->where('area_sqm', '>=', (float) $v))
            ->when($this->fAreaMax, fn (Builder $q, $v) => $q->where('area_sqm', '<=', (float) $v))
            ->when($this->fUpdatedFrom, fn (Builder $q, $v) => $q->whereDate('updated_at', '>=', $v))
            ->when($this->fUpdatedTo, fn (Builder $q, $v) => $q->whereDate('updated_at', '<=', $v))
            ->when($this->fDebt === 'with', fn (Builder $q) => $q->whereIn('id', $this->overdueApartmentIds()))
            ->when($this->fDebt === 'none', fn (Builder $q) => $q->whereNotIn('id', $this->overdueApartmentIds()));
    }

    /** ID các căn (trong context) đang có công nợ quá hạn. */
    private function overdueApartmentIds(): array
    {
        return Debt::whereIn('apartment_id', Apartment::query()->whereIn('building_id', $this->buildingIds())->select('id'))
            ->where('is_overdue', true)
            ->distinct()
            ->pluck('apartment_id')
            ->all();
    }

    /** Bảng options cho các select inline (memo trong 1 request). */
    private function filterOptions(): array
    {
        if ($this->filterOptionsCache !== null) {
            return $this->filterOptionsCache;
        }

        $bids = $this->buildingIds();

        return $this->filterOptionsCache = [
            'buildings' => $this->ctx()->buildings()->pluck('name', 'id')->all(),
            'floors' => Floor::whereIn('building_id', $bids)->orderBy('name')->pluck('name', 'id')->all(),
            'types' => Apartment::whereIn('building_id', $bids)->whereNotNull('type')->distinct()->orderBy('type')->pluck('type', 'type')->all(),
            'statuses' => collect(self::STATUS)->map(fn ($v) => $v[0])->all(),
            'holders' => ['owner' => 'Chủ sở hữu', 'tenant' => 'Người thuê', 'member' => 'Thành viên'],
        ];
    }

    /** Chip filter đang bật (nhãn + giá trị hiển thị + key để xóa). */
    private function activeChips(): array
    {
        $opts = $this->filterOptions();
        $display = [
            'fBuilding' => fn ($v) => $opts['buildings'][$v] ?? $v,
            'fFloor' => fn ($v) => $opts['floors'][$v] ?? $v,
            'fType' => fn ($v) => $opts['types'][$v] ?? $v,
            'fStatus' => fn ($v) => $opts['statuses'][$v] ?? $v,
            'fHolder' => fn ($v) => $opts['holders'][$v] ?? $v,
            'fAreaMin' => fn ($v) => $v.' m²',
            'fAreaMax' => fn ($v) => $v.' m²',
            'fUpdatedFrom' => fn ($v) => $v,
            'fUpdatedTo' => fn ($v) => $v,
            'fDebt' => fn ($v) => $v === 'with' ? 'Có công nợ' : 'Không nợ',
        ];

        $chips = [];
        foreach (self::FILTER_LABELS as $key => $label) {
            $val = $this->{$key};
            if (filled($val)) {
                $chips[] = ['key' => $key, 'label' => $label, 'value' => ($display[$key])($val)];
            }
        }

        return $chips;
    }

    /** Số filter nâng cao đang bật (badge trên nút "Bộ lọc nâng cao"). */
    private function advancedCount(): int
    {
        return count(array_filter(self::ADVANCED_KEYS, fn ($k) => filled($this->{$k})));
    }

    /** Xóa 1 filter (chip ×). */
    public function clearFilter(string $key): void
    {
        if (array_key_exists($key, $this->allFilterProps())) {
            $this->{$key} = null;
            $this->refreshTable();
        }
    }

    /** Xóa tất cả filter (kể cả search + nâng cao). */
    public function clearAllFilters(): void
    {
        foreach (array_keys($this->allFilterProps()) as $key) {
            $this->{$key} = null;
        }
        $this->refreshTable();
    }

    /** Xóa riêng cụm filter nâng cao (nút "Đặt lại" trong drawer). */
    public function clearAdvanced(): void
    {
        foreach (self::ADVANCED_KEYS as $key) {
            $this->{$key} = null;
        }
        $this->refreshTable();
    }

    /** Dữ liệu hiển thị 1 card mobile (Task 7). Public để blade gọi trực tiếp. */
    public function cardMeta(Apartment $a): array
    {
        $holder = $this->holderFor($a);
        $color = self::STATUS[$a->status][1] ?? 'gray';

        return [
            'statusLabel' => self::STATUS[$a->status][0] ?? ($a->status ?? '—'),
            'statusBadgeClass' => self::BADGE[$color] ?? self::BADGE['gray'],
            'holderName' => $holder['name'],
            'holderRole' => $holder['role'],
            'residentCount' => ResidentApartmentRelation::where('apartment_id', $a->id)->count(),
            'debt' => (float) Debt::where('apartment_id', $a->id)->where('is_overdue', true)->sum('amount'),
        ];
    }

    /** Chủ thể hiện tại của căn: ưu tiên chủ sở hữu, rồi người thuê. */
    private function holderFor(Apartment $a): array
    {
        $rel = ResidentApartmentRelation::where('apartment_id', $a->id)
            ->orderByRaw("FIELD(role,'owner','tenant','member')")
            ->with('resident')
            ->first();

        return [
            'name' => $rel?->resident?->full_name ?? '—',
            'role' => $rel ? (self::ROLES[$rel->role] ?? null) : null,
        ];
    }

    public function notifyImport(): void
    {
        Notification::make()->title('Nhập dữ liệu căn hộ')->body('Trình nhập liệu 6 bước sẽ được bổ sung ở đợt Import/Export.')->info()->send();
    }

    /** Xuất CSV theo context hiện tại + ghi audit. */
    public function export()
    {
        // Task 5: export dùng đúng filter state hiện tại (context + filter nghiệp vụ).
        $rows = $this->filteredQuery()->orderBy('code')->get();
        $scope = $this->activeChips() ? ' (đã lọc)' : '';
        $this->audit('apartment.export', 'Xuất danh sách căn hộ'.$scope.' ('.$rows->count().' dòng)', 'Apartment');

        $filename = 'apartments_'.now()->format('Ymd_His').'.csv';

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fwrite($out, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($out, ['Mã căn', 'Tòa', 'Tầng', 'Diện tích (m²)', 'Loại căn', 'Trạng thái', 'Chủ thể hiện tại', 'Số cư dân', 'Công nợ (VND)']);
            foreach ($rows as $a) {
                $holder = $this->holderFor($a);
                fputcsv($out, [
                    $a->code,
                    $a->building?->name ?? '',
                    $a->floor?->name ?? '',
                    (float) $a->area_sqm,
                    $a->type,
                    self::STATUS[$a->status][0] ?? $a->status,
                    $holder['name'],
                    ResidentApartmentRelation::where('apartment_id', $a->id)->count(),
                    (float) Debt::where('apartment_id', $a->id)->where('is_overdue', true)->sum('amount'),
                ]);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function audit(string $action, string $description, ?string $subjectType = null): void
    {
        $user = auth()->user();
        AuditLog::create([
            'tenant_id' => $user?->tenant_id,
            'building_id' => $user?->building_id,
            'user_id' => $user?->id,
            'actor_name' => $user?->name,
            'action' => $action,
            'subject_type' => $subjectType,
            'description' => $description,
        ]);
    }
}
