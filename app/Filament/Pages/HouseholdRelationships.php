<?php

namespace App\Filament\Pages;

use App\Models\ResidentApartmentRelation;
use App\Support\Context\CurrentContext;
use BackedEnum;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Builder;

/**
 * BQL-01-06 — Quan hệ hộ gia đình (Household Relationship Binding).
 * Groups resident-apartment relations by apartment so BQL can review each household's
 * members, head-of-household and roles. Data = resident_apartment_relations (+ resident,
 * apartment) scoped to the project's buildings.
 */
class HouseholdRelationships extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-users';

    protected static string|\UnitEnum|null $navigationGroup = 'Cư dân & Căn hộ';

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

    /** @return Builder<ResidentApartmentRelation> */
    private function scoped(): Builder
    {
        $bids = app(CurrentContext::class)->buildingIds() ?: [0];

        return ResidentApartmentRelation::query()
            ->whereHas('apartment', fn (Builder $q) => $q->whereIn('building_id', $bids));
    }

    protected function getViewData(): array
    {
        $relations = (clone $this->scoped())
            ->with(['resident', 'apartment.building', 'apartment.floor'])
            ->when($this->roleFilter !== 'all', fn (Builder $q) => $q->where('role', $this->roleFilter))
            ->when($this->search !== '', fn (Builder $q) => $q->where(fn (Builder $w) => $w
                ->whereHas('apartment', fn (Builder $a) => $a->where('code', 'like', '%'.$this->search.'%'))
                ->orWhereHas('resident', fn (Builder $r) => $r->where('full_name', 'like', '%'.$this->search.'%'))))
            ->get();

        // Group into households by apartment.
        $households = $relations->groupBy('apartment_id')->map(function ($rels) {
            $apt = $rels->first()->apartment;
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
                'apartment_id' => $apt?->id,
                'code' => $apt?->code ?? '—',
                'building' => $apt?->building?->name,
                'floor' => $apt?->floor?->name,
                'members' => $members,
                'has_head' => $rels->contains('is_primary', true),
                'count' => $rels->count(),
            ];
        })->sortBy('code')->values();

        $allScoped = (clone $this->scoped())->get();

        return [
            'households' => $households,
            'kpis' => [
                ['label' => 'Số hộ', 'value' => $allScoped->pluck('apartment_id')->unique()->count(), 'accent' => 'blue'],
                ['label' => 'Chủ hộ', 'value' => $allScoped->where('is_primary', true)->count(), 'accent' => 'green'],
                ['label' => 'Tổng thành viên', 'value' => $allScoped->count(), 'accent' => 'teal'],
                ['label' => 'Hộ chưa có chủ hộ', 'value' => $allScoped->groupBy('apartment_id')->filter(fn ($r) => ! $r->contains('is_primary', true))->count(), 'accent' => 'amber'],
            ],
        ];
    }
}
