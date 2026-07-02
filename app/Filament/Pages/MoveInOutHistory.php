<?php

namespace App\Filament\Pages;

use App\Models\ApartmentStatusHistory;
use App\Models\ResidentApartmentRelation;
use App\Support\Context\CurrentContext;
use BackedEnum;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Builder;

/**
 * BQL-01-07 — Lịch sử chuyển đến / chuyển đi (Move-in / Move-out History).
 * A chronological log of occupancy changes: move-ins (resident_apartment_relations
 * start_date) and apartment status transitions (apartment_status_histories), scoped to
 * the project's buildings. Read-only; feeds occupancy KPIs.
 */
class MoveInOutHistory extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-arrows-right-left';

    protected static string|\UnitEnum|null $navigationGroup = 'Cư dân & Căn hộ';

    protected static ?string $navigationLabel = 'Lịch sử chuyển đến/đi';

    protected static ?int $navigationSort = 7;

    protected static ?string $title = 'Lịch sử chuyển đến / chuyển đi';

    protected static ?string $slug = 'move-history';

    protected string $view = 'filament.pages.move-in-out-history';

    public string $typeFilter = 'all';

    /** @return array<int> */
    private function buildingIds(): array
    {
        return app(CurrentContext::class)->buildingIds() ?: [0];
    }

    protected function getViewData(): array
    {
        $bids = $this->buildingIds();

        // Move-in events from relations.
        $moveIns = ResidentApartmentRelation::query()
            ->whereHas('apartment', fn (Builder $q) => $q->whereIn('building_id', $bids))
            ->whereNotNull('start_date')
            ->with(['resident', 'apartment.building'])
            ->get()
            ->map(fn (ResidentApartmentRelation $r) => [
                'type' => 'in',
                'date' => $r->start_date ? \Illuminate\Support\Carbon::parse($r->start_date) : null,
                'apartment' => $r->apartment?->code ?? '—',
                'building' => $r->apartment?->building?->name,
                'detail' => $r->resident?->full_name ?? '—',
                'meta' => match ($r->role) { 'owner' => 'Chủ sở hữu', 'tenant' => 'Người thuê', default => 'Thành viên' },
                'by' => '—',
            ]);

        // Apartment status transitions.
        $statusChanges = ApartmentStatusHistory::query()
            ->whereHas('apartment', fn (Builder $q) => $q->whereIn('building_id', $bids))
            ->with(['apartment.building', 'changedBy'])
            ->get()
            ->map(fn (ApartmentStatusHistory $h) => [
                'type' => $this->isMoveOut($h->to_status) ? 'out' : 'status',
                'date' => $h->changed_at ?? $h->created_at,
                'apartment' => $h->apartment?->code ?? '—',
                'building' => $h->apartment?->building?->name,
                'detail' => trim(($h->from_status ?? '?').' → '.($h->to_status ?? '?')).($h->reason ? ' · '.$h->reason : ''),
                'meta' => 'Đổi trạng thái',
                'by' => $h->changedBy?->name ?? '—',
            ]);

        $events = $moveIns->concat($statusChanges)
            ->filter(fn ($e) => $e['date'])
            ->when($this->typeFilter !== 'all', fn ($c) => $c->where('type', $this->typeFilter))
            ->sortByDesc(fn ($e) => $e['date']->timestamp ?? 0)
            ->take(120)->values();

        $now = now();
        $inThisMonth = $moveIns->filter(fn ($e) => $e['date'] && $e['date']->isSameMonth($now))->count();
        $statusThisMonth = $statusChanges->filter(fn ($e) => $e['date'] && $e['date']->isSameMonth($now))->count();
        $occupiedApts = ResidentApartmentRelation::query()
            ->whereHas('apartment', fn (Builder $q) => $q->whereIn('building_id', $bids))
            ->distinct('apartment_id')->count('apartment_id');

        return [
            'events' => $events,
            'kpis' => [
                ['label' => 'Chuyển đến (tháng này)', 'value' => $inThisMonth, 'accent' => 'green'],
                ['label' => 'Đổi trạng thái (tháng này)', 'value' => $statusThisMonth, 'accent' => 'amber'],
                ['label' => 'Tổng sự kiện', 'value' => $moveIns->count() + $statusChanges->count(), 'accent' => 'blue'],
                ['label' => 'Căn có cư dân', 'value' => $occupiedApts, 'accent' => 'teal'],
            ],
        ];
    }

    private function isMoveOut(?string $status): bool
    {
        return in_array(mb_strtolower((string) $status), ['vacant', 'available', 'trống', 'moved_out', 'empty'], true);
    }
}
