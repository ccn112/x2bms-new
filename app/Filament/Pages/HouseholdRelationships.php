<?php

namespace App\Filament\Pages;

use App\Models\Apartment;
use App\Models\ResidentApartmentRelation;
use App\Support\Context\CurrentContext;
use BackedEnum;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Builder;
use Livewire\WithPagination;

/**
 * BQL-01-06 — Quan hệ hộ gia đình (Household Relationship Binding).
 * Groups resident-apartment relations by apartment so BQL can review each household's
 * members, head-of-household and roles. Data = resident_apartment_relations (+ resident,
 * apartment) scoped to the project's buildings.
 */
class HouseholdRelationships extends Page
{
    use WithPagination;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-users';

    protected static string|\UnitEnum|null $navigationGroup = 'Cư dân & Căn hộ';

    protected static ?string $navigationParentItem = 'Cư dân';

    protected static ?string $navigationLabel = 'Quan hệ hộ gia đình';

    protected static ?int $navigationSort = 6;

    protected static ?string $title = 'Quan hệ hộ gia đình';

    protected static ?string $slug = 'households';

    protected string $view = 'filament.pages.household-relationships';

    public string $search = '';

    public string $roleFilter = 'all';

    public const ROLES = [
        'owner' => ['Chủ sở hữu', 'green'],
        'tenant' => ['Người thuê', 'blue'],
        'member' => ['Thành viên', 'slate'],
    ];

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedRoleFilter(): void
    {
        $this->resetPage();
    }

    /** Builder quan hệ scope theo tòa (cho KPI aggregate). */
    private function scopedRelations(): Builder
    {
        $bids = app(CurrentContext::class)->buildingIds() ?: [0];

        return ResidentApartmentRelation::query()
            ->whereHas('apartment', fn (Builder $q) => $q->whereIn('building_id', $bids));
    }

    protected function getViewData(): array
    {
        $bids = app(CurrentContext::class)->buildingIds() ?: [0];

        // Phân trang theo CĂN HỘ (mỗi căn = 1 hộ) — tránh nạp toàn bộ quan hệ.
        $apartmentsPage = Apartment::query()
            ->whereIn('building_id', $bids)
            ->whereHas('apartmentRelations', function (Builder $q) {
                if ($this->roleFilter !== 'all') {
                    $q->where('role', $this->roleFilter);
                }
            })
            ->when($this->search !== '', fn (Builder $q) => $q->where(fn (Builder $w) => $w
                ->where('code', 'like', '%'.$this->search.'%')
                ->orWhereHas('apartmentRelations.resident', fn (Builder $r) => $r->where('full_name', 'like', '%'.$this->search.'%'))))
            ->with(['building', 'floor'])
            ->orderBy('code')
            ->paginate(24);

        // Nạp quan hệ CHỈ cho các căn ở trang hiện tại.
        $relsByApt = ResidentApartmentRelation::query()
            ->whereIn('apartment_id', $apartmentsPage->getCollection()->pluck('id'))
            ->when($this->roleFilter !== 'all', fn (Builder $q) => $q->where('role', $this->roleFilter))
            ->with('resident')
            ->get()->groupBy('apartment_id');

        $households = $apartmentsPage->getCollection()->map(function (Apartment $apt) use ($relsByApt) {
            $rels = $relsByApt->get($apt->id) ?? collect();
            $members = $rels->sortByDesc('is_primary')->map(fn (ResidentApartmentRelation $rel) => [
                'name' => $rel->resident?->full_name ?? '—',
                'role' => $rel->role,
                'is_primary' => (bool) $rel->is_primary,
                'rel_to_head' => $rel->resident?->relationship_to_head,
                'phone' => $rel->resident?->phone,
                'start' => $rel->start_date,
                'resident_id' => $rel->resident_id,
            ])->values();

            return [
                'apartment_id' => $apt->id,
                'code' => $apt->code ?? '—',
                'building' => $apt->building?->name,
                'floor' => $apt->floor?->name,
                'members' => $members,
                'has_head' => $rels->contains('is_primary', true),
                'count' => $rels->count(),
            ];
        })->values();

        // KPI = aggregate toàn dự án (không theo trang).
        $soHo = $this->scopedRelations()->distinct('apartment_id')->count('apartment_id');
        $hoCoChuHo = $this->scopedRelations()->where('is_primary', true)->distinct('apartment_id')->count('apartment_id');

        return [
            'households' => $households,
            'apartmentsPage' => $apartmentsPage,
            'kpis' => [
                ['label' => 'Số hộ', 'value' => number_format($soHo), 'accent' => 'blue'],
                ['label' => 'Chủ hộ', 'value' => number_format($hoCoChuHo), 'accent' => 'green'],
                ['label' => 'Tổng thành viên', 'value' => number_format($this->scopedRelations()->count()), 'accent' => 'teal'],
                ['label' => 'Hộ chưa có chủ hộ', 'value' => number_format(max(0, $soHo - $hoCoChuHo)), 'accent' => 'amber'],
            ],
        ];
    }
}
