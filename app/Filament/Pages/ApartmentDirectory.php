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
use Filament\Actions\BulkActionGroup;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

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

    private function ctx(): CurrentContext
    {
        return app(CurrentContext::class);
    }

    private function buildingIds(): array
    {
        return $this->ctx()->buildingIds() ?: [0];
    }

    protected function getViewData(): array
    {
        $bids = $this->buildingIds();
        $base = fn () => Apartment::query()->whereIn('building_id', $bids);

        $total = $base()->count();
        $occupied = (clone $base())->where('status', 'occupied')->count();
        $vacant = (clone $base())->where('status', 'vacant')->count();
        $pendingAttach = (clone $base())->where('status', 'pending_attach')->count();

        $aptIds = (clone $base())->pluck('id');
        $withDebt = Debt::whereIn('apartment_id', $aptIds)->where('is_overdue', true)->distinct()->count('apartment_id');

        $pct = fn (int $n) => $total ? number_format($n / $total * 100, 1, ',', '.').'%' : '0%';

        // KPI = tổng theo context, BẤT BIẾN theo filter bảng.
        return [
            'activeTab' => 'list',
            'tabs' => [
                ['key' => 'list', 'label' => 'Danh sách căn hộ', 'url' => url('/admin/apartments')],
                ['key' => 'tree', 'label' => 'Cây căn hộ', 'url' => url('/admin/apartments/tree')],
                ['key' => 'binding', 'label' => 'Duyệt gắn căn hộ', 'url' => url('/admin/residents/binding-queue')],
            ],
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
        $ctx = $this->ctx();
        $bids = $this->buildingIds();
        $buildingOptions = $ctx->buildings()->pluck('name', 'id')->all();
        $floorOptions = Floor::whereIn('building_id', $bids)->orderBy('name')->pluck('name', 'id')->all();
        $typeOptions = Apartment::whereIn('building_id', $bids)->whereNotNull('type')->distinct()->orderBy('type')->pluck('type', 'type')->all();
        $statusOptions = collect(self::STATUS)->map(fn ($v) => $v[0])->all();

        return $table
            ->query(Apartment::query()->whereIn('building_id', $bids)->with(['floor', 'building']))
            ->defaultSort('code')
            ->searchPlaceholder('Tìm kiếm mã căn, chủ sở hữu, người thuê…')
            ->columns([
                TextColumn::make('code')
                    ->label('Mã căn')
                    ->searchable()
                    ->sortable()
                    ->color('primary')
                    ->weight('medium')
                    ->url(fn (Apartment $a): string => url('/admin/apartments/'.$a->id.'/profile')),
                TextColumn::make('building.name')
                    ->label('Tòa')
                    ->badge()
                    ->color('gray')
                    ->sortable(),
                TextColumn::make('floor.name')
                    ->label('Tầng')
                    ->placeholder('—')
                    ->sortable(),
                TextColumn::make('area_sqm')
                    ->label('Diện tích (m²)')
                    ->formatStateUsing(fn ($state): string => $state ? number_format((float) $state, 1, ',', '.') : '—')
                    ->sortable(),
                TextColumn::make('type')
                    ->label('Loại căn')
                    ->placeholder('—'),
                TextColumn::make('status')
                    ->label('Trạng thái ở')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => self::STATUS[$state][0] ?? ($state ?? '—'))
                    ->color(fn (?string $state): string => self::STATUS[$state][1] ?? 'gray'),
                TextColumn::make('current_holder')
                    ->label('Chủ thể hiện tại')
                    ->state(fn (Apartment $a): string => $this->holderFor($a)['name'])
                    ->description(fn (Apartment $a): ?string => $this->holderFor($a)['role']),
                TextColumn::make('resident_count')
                    ->label('Số cư dân')
                    ->alignCenter()
                    ->state(fn (Apartment $a): int => ResidentApartmentRelation::where('apartment_id', $a->id)->count()),
                TextColumn::make('debt')
                    ->label('Công nợ (VND)')
                    ->alignEnd()
                    ->state(fn (Apartment $a): float => (float) Debt::where('apartment_id', $a->id)->where('is_overdue', true)->sum('amount'))
                    ->formatStateUsing(fn (float $state): string => number_format($state, 0, ',', '.'))
                    ->color(fn (float $state): string => $state > 0 ? 'danger' : 'success'),
                TextColumn::make('updated_at')
                    ->label('Cập nhật')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->color('gray'),
            ])
            ->filters([
                SelectFilter::make('building_id')->label('Tòa')->options($buildingOptions),
                SelectFilter::make('floor_id')->label('Tầng')->options($floorOptions),
                SelectFilter::make('type')->label('Loại căn')->options($typeOptions),
                SelectFilter::make('status')->label('Trạng thái sử dụng')->options($statusOptions),
                SelectFilter::make('holder')
                    ->label('Chủ sở hữu/Người thuê')
                    ->options(['owner' => 'Chủ sở hữu', 'tenant' => 'Người thuê'])
                    ->query(fn (Builder $q, array $data): Builder => filled($data['value'] ?? null)
                        ? $q->whereHas('residents', fn ($r) => $r->where('resident_apartment_relations.role', $data['value']))
                        : $q),
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
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('export')
                        ->label('Xuất dữ liệu')
                        ->icon('heroicon-m-arrow-down-tray')
                        ->color('gray')
                        ->action(fn () => Notification::make()->title('Đang chuẩn bị file xuất…')->info()->send()),
                ]),
            ])
            ->emptyStateHeading('Không tìm thấy căn hộ phù hợp')
            ->emptyStateDescription('Không có kết quả nào khớp với bộ lọc hiện tại.')
            ->emptyStateIcon('heroicon-o-home-modern')
            ->striped()
            ->paginated([10, 25, 50, 100]);
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
        $rows = Apartment::query()->whereIn('building_id', $this->buildingIds())->with('floor', 'building')->orderBy('code')->get();
        $this->audit('apartment.export', 'Xuất danh sách căn hộ ('.$rows->count().' dòng)', 'Apartment');

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
