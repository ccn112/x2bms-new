<?php

namespace App\Filament\Pages;

use App\Models\AccessCard;
use App\Models\AccessLog;
use App\Models\Resident;
use App\Models\ResidentApartmentRelation;
use App\Models\Vehicle;
use App\Support\Context\CurrentContext;
use BackedEnum;
use Filament\Pages\Page;
use Illuminate\Support\Carbon;

/**
 * BQL-02-10 — Hồ sơ truy cập cư dân (Resident Access Profile).
 * One resident's full access picture: account, linked vehicles, active cards, access
 * zones, recent access activity and security warnings. Resident picker at top. All
 * data live-scoped to the project. UI follows BQL-02-10.
 */
class ResidentAccessProfile extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-identification';

    protected static string|\UnitEnum|null $navigationGroup = 'An ninh & Kiểm soát';

    protected static ?string $navigationLabel = 'Hồ sơ truy cập cư dân';

    protected static ?int $navigationSort = 22;

    protected static ?string $title = 'Hồ sơ truy cập cư dân';

    protected static ?string $slug = 'access/resident-profile';

    protected string $view = 'filament.pages.resident-access-profile';

    public ?int $residentId = null;

    /** @return array<int> */
    private function buildingIds(): array
    {
        return app(CurrentContext::class)->buildingIds() ?: [0];
    }

    public function mount(): void
    {
        // Default to a resident that actually has access data (a card), else the first resident.
        $bids = $this->buildingIds();
        $this->residentId = AccessCard::whereIn('building_id', $bids)->value('resident_id')
            ?? Resident::whereIn('building_id', $bids)->value('id');
    }

    protected function getViewData(): array
    {
        $bids = $this->buildingIds();

        $residentOptions = Resident::whereIn('building_id', $bids)->orderBy('full_name')
            ->limit(500)->pluck('full_name', 'id')->all();

        $resident = $this->residentId ? Resident::find($this->residentId) : null;

        if (! $resident) {
            return ['resident' => null, 'residentOptions' => $residentOptions];
        }

        $vehicleModels = Vehicle::where('resident_id', $resident->id)->get();
        $cardModels = AccessCard::where('resident_id', $resident->id)->get();

        $typeLabel = VehicleRequests::TYPE;
        $cardTypeLabel = AccessCards::TYPE;

        $vehicles = $vehicleModels->map(fn (Vehicle $v) => [
            'plate_no' => $v->plate_no,
            'type' => $typeLabel[$this->enumVal($v->type)] ?? ($this->enumVal($v->type) ?: '—'),
            'parking' => $v->parking_card_no,
            'status' => $this->enumVal($v->status),
        ])->all();

        $cards = $cardModels->map(fn (AccessCard $c) => [
            'card_no' => $c->card_no,
            'type' => $cardTypeLabel[$this->enumVal($c->type)] ?? ($this->enumVal($c->type) ?: '—'),
            'is_biometric' => (bool) $c->is_biometric,
            'valid_to' => $c->valid_to?->format('d/m/Y'),
            'status' => $this->enumVal($c->status),
        ])->all();
        $logs = AccessLog::where('resident_id', $resident->id)->latest('event_at')->limit(12)->get()
            ->map(fn (AccessLog $l) => [
                'time' => $l->event_at ? Carbon::parse($l->event_at)->format('d/m H:i') : '—',
                'gate' => $l->gate ?: $l->device_name ?: '—',
                'direction' => $l->direction === 'out' ? ['Ra', 'red'] : ['Vào', 'green'],
                'method' => $l->method ?: '—',
            ])->all();

        // Primary apartment via relation.
        $rel = ResidentApartmentRelation::where('resident_id', $resident->id)
            ->orderByDesc('is_primary')->with('apartment.building')->first();
        $apartment = $rel?->apartment;

        $soon = now()->addDays(30);
        $activeCardCount = $cardModels->filter(fn (AccessCard $c) => $this->enumVal($c->status) === 'active')->count();
        $activeVehicleCount = $vehicleModels->filter(fn (Vehicle $v) => $this->enumVal($v->status) === 'active')->count();

        return [
            'resident' => $resident,
            'residentOptions' => $residentOptions,
            'apartment' => $apartment,
            'vehicles' => $vehicles,
            'cards' => $cards,
            'logs' => $logs,
            'summary' => [
                ['label' => 'Xe liên kết', 'value' => $vehicleModels->count(), 'sub' => $activeVehicleCount.' đang hoạt động', 'accent' => 'blue'],
                ['label' => 'Thẻ đang hoạt động', 'value' => $activeCardCount, 'sub' => $cardModels->count().' tổng thẻ', 'accent' => 'green'],
                ['label' => 'Sinh trắc học', 'value' => $cardModels->where('is_biometric', true)->count(), 'sub' => 'thẻ có sinh trắc', 'accent' => 'teal'],
                ['label' => 'Sự kiện gần đây', 'value' => AccessLog::where('resident_id', $resident->id)->count(), 'sub' => 'lượt ra/vào', 'accent' => 'amber'],
            ],
            'warnings' => $cardModels->filter(fn (AccessCard $c) => $c->valid_to && $c->valid_to->between(now(), $soon))
                ->map(fn (AccessCard $c) => ['type' => 'card', 'text' => 'Thẻ '.$c->card_no.' sắp hết hạn', 'detail' => 'Hết hạn '.$c->valid_to->format('d/m/Y')])->values()->all(),
        ];
    }

    /** Normalise a BackedEnum (Filament cast) to its scalar value. */
    private function enumVal(mixed $x): ?string
    {
        return $x instanceof \BackedEnum ? (string) $x->value : ($x === null ? null : (string) $x);
    }
}
