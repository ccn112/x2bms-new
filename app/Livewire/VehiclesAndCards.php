<?php

namespace App\Livewire;

use App\Models\AccessCard;
use App\Models\Vehicle;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * WEB-02-03 — Phương tiện & thẻ. Vehicles + access cards with KPIs.
 */
#[Layout('components.layouts.x2-app')]
class VehiclesAndCards extends Component
{
    public function render()
    {
        $kpis = [
            ['label' => 'Tổng phương tiện', 'value' => Vehicle::count(), 'accent' => 'blue'],
            ['label' => 'Ô tô', 'value' => Vehicle::where('type', 'car')->count(), 'accent' => 'teal'],
            ['label' => 'Xe máy', 'value' => Vehicle::where('type', 'motorbike')->count(), 'accent' => 'amber'],
            ['label' => 'Phí gửi xe / tháng', 'value' => number_format((float) Vehicle::sum('monthly_fee') / 1e6, 1).' tr', 'accent' => 'green'],
        ];

        $vehicles = Vehicle::with('apartment')->orderBy('type')->take(15)->get()->map(fn (Vehicle $v) => [
            'plate' => '<span class="font-medium text-slate-800">'.e($v->plate_no).'</span>',
            'type' => view('components.x2.status-badge', ['label' => $v->type->label(), 'tone' => $v->type->tone()])->render(),
            'apartment' => '<span class="text-slate-600">'.e($v->apartment?->code ?? '—').'</span>',
            'card' => e($v->parking_card_no ?? '—'),
            'fee' => $v->monthly_fee > 0 ? number_format((float) $v->monthly_fee, 0, ',', '.').' đ' : '—',
            'valid' => $v->valid_to?->format('d/m/Y') ?? '—',
        ])->all();

        $cards = AccessCard::with(['apartment', 'resident'])->orderByDesc('is_biometric')->take(15)->get()->map(fn (AccessCard $c) => [
            'no' => '<span class="font-medium text-slate-800">'.e($c->card_no).'</span>',
            'holder' => '<span class="text-slate-600">'.e($c->resident?->full_name ?? '—').'</span>',
            'apartment' => e($c->apartment?->code ?? '—'),
            'type' => $c->is_biometric
                ? '<span class="inline-flex items-center gap-1 text-x2-primary">● Sinh trắc</span>'
                : 'RFID',
            'valid' => $c->valid_to?->format('d/m/Y') ?? '—',
            'status' => view('components.x2.status-badge', [
                'label' => $c->status === 'active' ? 'Hiệu lực' : ($c->status === 'revoked' ? 'Thu hồi' : 'Hết hạn'),
                'tone' => $c->status === 'active' ? 'green' : 'red',
            ])->render(),
        ])->all();

        return view('livewire.vehicles-and-cards', [
            'kpis' => $kpis,
            'vehicles' => $vehicles,
            'cards' => $cards,
            'vehicleTotal' => Vehicle::count(),
            'cardTotal' => AccessCard::count(),
        ]);
    }
}
