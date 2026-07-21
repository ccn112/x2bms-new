<?php

namespace App\Filament\Pages;

use App\Models\FeedbackRequest;
use App\Models\Resident;
use App\Models\ResidentApartmentRelation;
use App\Support\Context\CurrentContext;
use BackedEnum;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

/**
 * BQL-01-08 — Dòng thời gian cư dân (Resident Timeline).
 * A chronological activity feed across residents in the project: profile creation,
 * apartment binding and feedback submitted. Searchable by resident. Read-only,
 * aggregated live from residents / relations / feedback.
 */
class ResidentTimeline extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-clock';

    protected static string|\UnitEnum|null $navigationGroup = 'Cư dân & Căn hộ';

    protected static ?string $navigationParentItem = 'Cư dân';

    protected static ?string $navigationLabel = 'Dòng thời gian cư dân';

    protected static ?int $navigationSort = 8;

    protected static ?string $title = 'Dòng thời gian cư dân';

    protected static ?string $slug = 'resident-timeline';

    protected string $view = 'filament.pages.resident-timeline';

    public string $search = '';

    public string $typeFilter = 'all';

    /** @return array<int> */
    private function buildingIds(): array
    {
        return app(CurrentContext::class)->buildingIds() ?: [0];
    }

    protected function getViewData(): array
    {
        $bids = $this->buildingIds();
        $s = trim($this->search);
        $events = collect();

        // Profile creation.
        Resident::query()->whereIn('building_id', $bids)
            ->when($s !== '', fn (Builder $q) => $q->where('full_name', 'like', '%'.$s.'%'))
            ->latest('created_at')->limit(200)->get()
            ->each(fn (Resident $r) => $events->push([
                'type' => 'profile', 'icon' => 'user', 'color' => 'blue',
                'date' => Carbon::parse($r->join_date ?? $r->created_at),
                'resident' => $r->full_name, 'resident_id' => $r->id,
                'title' => 'Tạo hồ sơ cư dân',
                'meta' => $r->code ? 'Mã '.$r->code : ($r->phone ?? ''),
            ]));

        // Apartment binding.
        ResidentApartmentRelation::query()
            ->whereHas('apartment', fn (Builder $q) => $q->whereIn('building_id', $bids))
            ->whereNotNull('start_date')
            ->when($s !== '', fn (Builder $q) => $q->whereHas('resident', fn (Builder $r) => $r->where('full_name', 'like', '%'.$s.'%')))
            ->with(['resident', 'apartment'])->limit(200)->get()
            ->each(fn (ResidentApartmentRelation $rel) => $events->push([
                'type' => 'binding', 'icon' => 'home', 'color' => 'green',
                'date' => Carbon::parse($rel->start_date),
                'resident' => $rel->resident?->full_name ?? '—', 'resident_id' => $rel->resident_id,
                'title' => 'Gắn vào căn hộ '.($rel->apartment?->code ?? '—'),
                'meta' => match ($rel->role) { 'owner' => 'Chủ sở hữu', 'tenant' => 'Người thuê', default => 'Thành viên' },
            ]));

        // Feedback submitted.
        FeedbackRequest::query()->whereIn('building_id', $bids)->whereNotNull('resident_id')
            ->when($s !== '', fn (Builder $q) => $q->whereHas('resident', fn (Builder $r) => $r->where('full_name', 'like', '%'.$s.'%')))
            ->with('resident')->latest()->limit(200)->get()
            ->each(fn (FeedbackRequest $f) => $events->push([
                'type' => 'feedback', 'icon' => 'chat', 'color' => 'amber',
                'date' => Carbon::parse($f->created_at),
                'resident' => $f->resident?->full_name ?? '—', 'resident_id' => $f->resident_id,
                'title' => 'Gửi phản ánh: '.$f->title,
                'meta' => $f->code,
            ]));

        $events = $events
            ->when($this->typeFilter !== 'all', fn ($c) => $c->where('type', $this->typeFilter))
            ->sortByDesc(fn ($e) => $e['date']->timestamp)
            ->take(80)->values();

        // Group by day for the timeline.
        $grouped = $events->groupBy(fn ($e) => $e['date']->format('Y-m-d'));

        $now = now();

        return [
            'grouped' => $grouped,
            'kpis' => [
                ['label' => 'Cư dân mới (tháng này)', 'value' => Resident::whereIn('building_id', $bids)->whereBetween('created_at', [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()])->count(), 'accent' => 'blue'],
                ['label' => 'Tổng sự kiện', 'value' => $events->count(), 'accent' => 'teal'],
                ['label' => 'Phản ánh', 'value' => $events->where('type', 'feedback')->count(), 'accent' => 'amber'],
                ['label' => 'Lượt gắn căn', 'value' => $events->where('type', 'binding')->count(), 'accent' => 'green'],
            ],
        ];
    }
}
