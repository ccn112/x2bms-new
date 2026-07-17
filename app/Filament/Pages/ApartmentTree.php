<?php

namespace App\Filament\Pages;

use App\Models\Apartment;
use App\Models\Building;
use App\Models\Floor;
use App\Support\Context\CurrentContext;
use BackedEnum;
use Filament\Pages\Page;

/**
 * BQL-01-02 — Cây căn hộ theo tòa / tầng (Apartment Tree View).
 * Left tree of Building → Floor (with counts); right grid of apartments for the selected
 * scope with status. Lets BQL navigate the physical hierarchy. Data scoped to the project.
 */
class ApartmentTree extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-building-office-2';

    protected static string|\UnitEnum|null $navigationGroup = 'Cư dân & Căn hộ';

    // Submenu: nằm dưới "Hồ sơ căn hộ" (ApartmentDirectory) trong sidebar.
    protected static ?string $navigationParentItem = 'Hồ sơ căn hộ';

    protected static ?string $navigationLabel = 'Cây căn hộ';

    protected static ?int $navigationSort = 3;

    protected static ?string $title = 'Cây căn hộ theo tòa / tầng';

    protected static ?string $slug = 'apartments/tree';

    protected string $view = 'filament.pages.apartment-tree';

    public ?int $buildingId = null;

    public ?int $floorId = null;

    public const STATUS = [
        'occupied' => ['Đang ở', 'green'],
        'vacant' => ['Trống', 'slate'],
        'available' => ['Trống', 'slate'],
        'maintenance' => ['Bảo trì', 'amber'],
        'renovating' => ['Đang sửa', 'amber'],
        'reserved' => ['Đã đặt', 'blue'],
    ];

    /** @return array<int> */
    private function buildingIds(): array
    {
        return app(CurrentContext::class)->buildingIds() ?: [0];
    }

    public function selectBuilding(int $id): void
    {
        $this->buildingId = $this->buildingId === $id ? null : $id;
        $this->floorId = null;
    }

    public function selectFloor(int $buildingId, int $floorId): void
    {
        $this->buildingId = $buildingId;
        $this->floorId = $this->floorId === $floorId ? null : $floorId;
    }

    protected function getViewData(): array
    {
        $bids = $this->buildingIds();

        $tree = Building::query()->whereIn('id', $bids)->orderBy('name')->get()
            ->map(fn (Building $b) => [
                'id' => $b->id,
                'name' => $b->name,
                'apartments' => Apartment::where('building_id', $b->id)->count(),
                'floors' => Floor::where('building_id', $b->id)->orderBy('level')->get()
                    ->map(fn (Floor $f) => [
                        'id' => $f->id,
                        'name' => $f->name ?: ('Tầng '.$f->level),
                        'apartments' => Apartment::where('floor_id', $f->id)->count(),
                    ])->all(),
            ])->all();

        // Apartments for the selected scope.
        $q = Apartment::query()->whereIn('building_id', $bids)->with('floor');
        if ($this->floorId) {
            $q->where('floor_id', $this->floorId);
        } elseif ($this->buildingId) {
            $q->where('building_id', $this->buildingId);
        }
        $apartments = $q->orderBy('code')->limit(300)->get()
            ->map(fn (Apartment $a) => [
                'id' => $a->id,
                'code' => $a->code,
                'status' => $a->status,
                'floor' => $a->floor?->name,
                'type' => $a->type,
                'area' => $a->area_sqm,
            ])->all();

        $scopeApts = Apartment::query()->whereIn('building_id', $bids);

        return [
            'tree' => $tree,
            'apartments' => $apartments,
            'scopeLabel' => $this->scopeLabel($tree),
            'kpis' => [
                ['label' => 'Tổng căn hộ', 'value' => (clone $scopeApts)->count(), 'accent' => 'blue'],
                ['label' => 'Đang ở', 'value' => (clone $scopeApts)->where('status', 'occupied')->count(), 'accent' => 'green'],
                ['label' => 'Số tòa', 'value' => count($tree), 'accent' => 'teal'],
                ['label' => 'Số tầng', 'value' => collect($tree)->sum(fn ($b) => count($b['floors'])), 'accent' => 'amber'],
            ],
        ];
    }

    private function scopeLabel(array $tree): string
    {
        $b = collect($tree)->firstWhere('id', $this->buildingId);
        if (! $b) {
            return 'Tất cả căn hộ';
        }
        if ($this->floorId) {
            $f = collect($b['floors'])->firstWhere('id', $this->floorId);

            return $b['name'].' · '.($f['name'] ?? '');
        }

        return $b['name'];
    }
}
