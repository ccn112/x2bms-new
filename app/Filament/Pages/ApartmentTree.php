<?php

namespace App\Filament\Pages;

use App\Models\AccessCard;
use App\Models\Apartment;
use App\Models\Building;
use App\Models\Debt;
use App\Models\Floor;
use App\Models\ResidentApartmentRelation;
use App\Models\Vehicle;
use App\Support\Context\CurrentContext;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\HtmlString;

/**
 * BQL-01-07 — Cây căn hộ. Trái: cây Dự án → Tòa → Tầng → căn (giữ thiết kế duyệt).
 * Giữa: chuyển Danh sách ↔ Layout mặt bằng (nút Layout chỉ bật khi tòa có ảnh layout).
 * Danh sách theo tầng KHÔNG scroll ngang; ô hiện màu trạng thái + m² + chủ hộ.
 * (Layout = upload ảnh mặt bằng + hotspot: đợt sau — owner 2026-07-17.)
 */
class ApartmentTree extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-group';

    protected static string|\UnitEnum|null $navigationGroup = 'Cư dân & Căn hộ';

    protected static ?string $navigationParentItem = 'Hồ sơ căn hộ';

    protected static ?string $navigationLabel = 'Cây căn hộ';

    protected static ?int $navigationSort = 3;

    protected static ?string $title = 'Cây căn hộ';

    protected static ?string $slug = 'apartments/tree';

    protected string $view = 'filament.pages.apartment-tree';

    public ?int $buildingId = null;

    public ?int $expandedFloorId = null;

    public ?int $apartmentId = null;

    public string $viewMode = 'list'; // list | layout

    public const STATUS = [
        'occupied' => ['Đã gắn', 'green'],
        'vacant' => ['Trống', 'slate'],
        'pending_attach' => ['Chờ gắn', 'amber'],
        'maintenance' => ['Bảo trì', 'violet'],
        'handover_pending' => ['Chờ bàn giao', 'amber'],
        'locked' => ['Khóa', 'red'],
    ];

    private const ROLE = ['owner' => 'Chủ sở hữu', 'tenant' => 'Người thuê', 'member' => 'Thành viên'];

    private function buildingIds(): array
    {
        return app(CurrentContext::class)->buildingIds() ?: [0];
    }

    public function mount(): void
    {
        $this->buildingId ??= Building::whereIn('id', $this->buildingIds())->orderBy('name')->value('id');
    }

    public function getBreadcrumbs(): array
    {
        return [
            url('/admin') => $this->crumb('heroicon-m-home', 'Tổng quan'),
            url('/admin/apartments') => $this->crumb('heroicon-m-home-modern', 'Hồ sơ căn hộ'),
            $this->crumb('heroicon-m-rectangle-group', 'Cây căn hộ'),
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
            Action::make('attach')->label('Gắn cư dân')->icon('heroicon-m-user-plus')->color('gold')
                ->url(url('/admin/residents/binding-queue')),
        ];
    }

    public function selectBuilding(int $id): void
    {
        $this->buildingId = $id;
        $this->expandedFloorId = null;
        $this->apartmentId = null;
    }

    public function toggleFloor(int $id): void
    {
        $this->expandedFloorId = $this->expandedFloorId === $id ? null : $id;
    }

    public function selectApartment(int $id): void
    {
        $this->apartmentId = $this->apartmentId === $id ? null : $id;
    }

    public function setView(string $mode): void
    {
        if ($mode === 'list' || ($mode === 'layout' && $this->buildingHasLayout())) {
            $this->viewMode = $mode;
        }
    }

    /** Tòa đang chọn đã có ảnh layout mặt bằng chưa (option C — chưa build → luôn false). */
    private function buildingHasLayout(): bool
    {
        return false;
    }

    protected function getViewData(): array
    {
        $bids = $this->buildingIds();

        $buildings = Building::whereIn('id', $bids)->orderBy('name')->get()->map(fn (Building $b) => [
            'id' => $b->id,
            'name' => $b->name,
            'floorCount' => Floor::where('building_id', $b->id)->count(),
        ])->all();

        // Cây trái: tầng của tòa đang chọn; tầng đang mở → kèm căn.
        $treeFloors = [];
        if ($this->buildingId) {
            $floors = Floor::where('building_id', $this->buildingId)->orderByDesc('level')->orderByDesc('name')->get();
            foreach ($floors as $f) {
                $treeFloors[] = [
                    'id' => $f->id,
                    'name' => $f->name ?: ('Tầng '.$f->level),
                    'expanded' => $f->id === $this->expandedFloorId,
                    'units' => $f->id === $this->expandedFloorId
                        ? Apartment::where('floor_id', $f->id)->orderBy('code')->get(['id', 'code'])
                            ->map(fn ($a) => ['id' => $a->id, 'code' => $a->code])->all()
                        : [],
                ];
            }
        }

        // Lưới danh sách theo tầng (KHÔNG scroll ngang → wrap): kèm m² + chủ hộ.
        $grid = [];
        if ($this->buildingId) {
            $aptIds = Apartment::where('building_id', $this->buildingId)->pluck('id');
            $relsByApt = ResidentApartmentRelation::whereIn('apartment_id', $aptIds)->with('resident')->get()
                ->groupBy('apartment_id');

            $floors = Floor::where('building_id', $this->buildingId)->orderByDesc('level')->orderByDesc('name')->get();
            foreach ($floors as $f) {
                $units = Apartment::where('floor_id', $f->id)->orderBy('code')->get()
                    ->map(function (Apartment $a) use ($relsByApt) {
                        $rels = $relsByApt->get($a->id);
                        $owner = $rels?->firstWhere('role', 'owner')?->resident
                            ?? $rels?->firstWhere('is_primary', true)?->resident
                            ?? $rels?->first()?->resident;

                        return [
                            'id' => $a->id,
                            'code' => $a->code,
                            'status' => $a->status,
                            'label' => self::STATUS[$a->status][0] ?? $a->status,
                            'tone' => self::STATUS[$a->status][1] ?? 'slate',
                            'area' => $a->area_sqm ? number_format((float) $a->area_sqm, 1, ',', '.').' m²' : null,
                            'owner' => $rels && $rels->count() ? ($owner?->full_name) : null,
                        ];
                    })->all();
                if ($units) {
                    $grid[] = ['floor' => $f->name ?: ('Tầng '.$f->level), 'units' => $units];
                }
            }
        }

        return [
            'projectName' => app(CurrentContext::class)->project()?->name ?? 'Dự án',
            'buildings' => $buildings,
            'buildingId' => $this->buildingId,
            'buildingName' => collect($buildings)->firstWhere('id', $this->buildingId)['name'] ?? '—',
            'treeFloors' => $treeFloors,
            'grid' => $grid,
            'viewMode' => $this->viewMode,
            'hasLayout' => $this->buildingHasLayout(),
            'legend' => [
                ['Đã gắn cư dân', 'bg-emerald-500'], ['Trống', 'bg-slate-300'],
                ['Chờ gắn', 'bg-amber-500'], ['Bảo trì', 'bg-violet-500'],
            ],
            'selected' => $this->apartmentId ? $this->apartmentDetail($this->apartmentId) : null,
        ];
    }

    private function apartmentDetail(int $id): ?array
    {
        $a = Apartment::whereIn('building_id', $this->buildingIds())->with(['floor', 'building'])->find($id);
        if (! $a) {
            return null;
        }

        $relations = ResidentApartmentRelation::where('apartment_id', $a->id)->with('resident')->get()
            ->filter(fn ($r) => $r->resident);

        return [
            'id' => $a->id,
            'code' => $a->code,
            'statusLabel' => self::STATUS[$a->status][0] ?? $a->status,
            'statusTone' => self::STATUS[$a->status][1] ?? 'slate',
            'building' => $a->building?->name,
            'floor' => $a->floor?->name,
            'type' => $a->type,
            'area' => $a->area_sqm ? number_format((float) $a->area_sqm, 1, ',', '.').' m²' : '—',
            'direction' => $a->direction ?? '—',
            'residents' => $relations->map(fn ($r) => [
                'name' => $r->resident->full_name,
                'role' => self::ROLE[$r->role] ?? $r->role,
                'isOwner' => $r->role === 'owner',
                'cccd' => $r->resident->id_no ?? '—',
                'phone' => $r->resident->phone ?? $r->resident->contact_phone ?? '—',
            ])->values()->all(),
            'vehicles' => Vehicle::where('apartment_id', $a->id)->get()->map(fn (Vehicle $v) => [
                'plate' => $v->plate_no,
                'type' => optional($v->type)->label() ?? $v->type,
            ])->all(),
            'cards' => AccessCard::where('apartment_id', $a->id)->get()->map(fn (AccessCard $c) => [
                'no' => $c->card_no,
                'type' => $c->is_biometric ? 'Sinh trắc' : 'Thẻ cư dân',
            ])->all(),
            'debtTotal' => (float) Debt::where('apartment_id', $a->id)->where('is_overdue', true)->sum('amount'),
        ];
    }
}
