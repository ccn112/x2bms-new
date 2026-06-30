<?php

namespace App\Filament\Pages;

use App\Models\AccessCard;
use App\Models\Apartment;
use App\Models\Debt;
use App\Models\ResidentApartmentRelation;
use App\Models\Statement;
use App\Models\Vehicle;
use Filament\Pages\Page;

/**
 * WEB-02-02 — Hồ sơ căn hộ (apartment detail). Sections + collection donut.
 * Parameterized custom page: /admin/apartments/{apartment}/profile.
 */
class ApartmentProfile extends Page
{
    protected static ?string $slug = 'apartments/{apartment}/profile';

    protected static bool $shouldRegisterNavigation = false;

    protected string $view = 'filament.pages.apartment-profile';

    public Apartment $apartment;

    public function mount(Apartment $apartment): void
    {
        $this->apartment = $apartment->load('floor', 'building');
    }

    public function getTitle(): string
    {
        return 'Căn hộ '.$this->apartment->code;
    }

    protected function getViewData(): array
    {
        $roleMeta = [
            'owner' => ['Chủ sở hữu', 'blue'],
            'tenant' => ['Người thuê', 'teal'],
            'member' => ['Thành viên', 'slate'],
        ];

        $relations = ResidentApartmentRelation::where('apartment_id', $this->apartment->id)
            ->with('resident')->get();
        $owner = $relations->firstWhere('role', 'owner')?->resident;

        $members = $relations->map(function ($rel) use ($roleMeta) {
            [$label, $tone] = $roleMeta[$rel->role] ?? ['—', 'slate'];

            return [
                'name' => e($rel->resident->full_name),
                'phone' => e($rel->resident->phone),
                'role' => view('components.x2.status-badge', ['label' => $label, 'tone' => $tone])->render(),
            ];
        })->all();

        $vehicles = Vehicle::where('apartment_id', $this->apartment->id)->get()->map(fn (Vehicle $v) => [
            'plate' => '<span class="font-medium text-slate-800">'.e($v->plate_no).'</span>',
            'type' => view('components.x2.status-badge', ['label' => $v->type->label(), 'tone' => $v->type->tone()])->render(),
            'card' => e($v->parking_card_no ?? '—'),
            'fee' => $v->monthly_fee > 0 ? number_format((float) $v->monthly_fee, 0, ',', '.').' đ' : '—',
        ])->all();

        $cards = AccessCard::where('apartment_id', $this->apartment->id)->get()->map(fn (AccessCard $c) => [
            'no' => '<span class="font-medium text-slate-800">'.e($c->card_no).'</span>',
            'type' => $c->is_biometric ? 'Sinh trắc' : 'RFID',
            'valid' => $c->valid_to?->format('d/m/Y') ?? '—',
            'status' => view('components.x2.status-badge', [
                'label' => $c->status === 'active' ? 'Hiệu lực' : ($c->status === 'revoked' ? 'Thu hồi' : 'Hết hạn'),
                'tone' => $c->status === 'active' ? 'green' : 'red',
            ])->render(),
        ])->all();

        // Collection rate for this apartment (from its statements).
        $stTotal = Statement::where('apartment_id', $this->apartment->id)->sum('total_amount');
        $stPaid = Statement::where('apartment_id', $this->apartment->id)->sum('paid_amount');
        $collectionRate = $stTotal > 0 ? (int) round($stPaid / $stTotal * 100) : null;
        $overdueDebt = Debt::where('apartment_id', $this->apartment->id)->where('is_overdue', true)->sum('amount');

        return [
            'apartment' => $this->apartment,
            'owner' => $owner,
            'members' => $members,
            'vehicles' => $vehicles,
            'cards' => $cards,
            'collectionRate' => $collectionRate,
            'overdueDebt' => $overdueDebt,
            'aiSuggestions' => [
                ['title' => 'Nhắc thanh toán công nợ căn '.$this->apartment->code, 'detail' => $overdueDebt > 0 ? 'Còn '.number_format($overdueDebt / 1e6, 0).' triệu quá hạn' : 'Không có công nợ quá hạn'],
                ['title' => 'Rà soát hiệu lực thẻ ra vào', 'detail' => 'Có thẻ sắp hết hạn trong năm'],
            ],
        ];
    }
}
