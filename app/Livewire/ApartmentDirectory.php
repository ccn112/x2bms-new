<?php

namespace App\Livewire;

use App\Models\Apartment;
use App\Models\Debt;
use App\Models\ResidentApartmentRelation;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * WEB-02-02 (list) — Danh sách căn hộ, mỗi dòng mở Hồ sơ căn hộ.
 */
#[Layout('components.layouts.x2-app')]
class ApartmentDirectory extends Component
{
    public string $search = '';

    public function render()
    {
        $kpis = [
            ['label' => 'Tổng số căn', 'value' => Apartment::count(), 'accent' => 'blue'],
            ['label' => 'Đang ở', 'value' => Apartment::where('status', 'occupied')->count(), 'accent' => 'teal'],
            ['label' => 'Có công nợ', 'value' => Debt::where('is_overdue', true)->distinct('apartment_id')->count('apartment_id'), 'accent' => 'amber'],
            ['label' => 'Cư dân', 'value' => \App\Models\Resident::count(), 'accent' => 'slate'],
        ];

        $query = Apartment::query()->with('floor')
            ->when($this->search !== '', fn ($q) => $q->where('code', 'like', "%{$this->search}%"));
        $total = (clone $query)->count();

        $rows = $query->orderBy('code')->take(15)->get()->map(function (Apartment $apt) {
            $owner = ResidentApartmentRelation::where('apartment_id', $apt->id)->where('role', 'owner')->with('resident')->first();
            $residentCount = ResidentApartmentRelation::where('apartment_id', $apt->id)->count();
            $debt = Debt::where('apartment_id', $apt->id)->where('is_overdue', true)->sum('amount');

            return [
                'code' => '<span class="font-medium text-slate-800">'.e($apt->code).'</span>',
                'floor' => '<span class="text-slate-600">'.e($apt->floor?->name ?? '—').'</span>',
                'area' => '<span class="text-slate-600">'.number_format((float) $apt->area_sqm, 0).' m²</span>',
                'owner' => '<span class="text-slate-700">'.e($owner?->resident?->full_name ?? '—').'</span>',
                'residents' => '<span class="text-slate-600">'.$residentCount.'</span>',
                'debt' => $debt > 0
                    ? '<span class="font-medium text-x2-red">'.number_format($debt / 1e6, 1).' tr</span>'
                    : '<span class="text-x2-green">—</span>',
                'action' => '<a href="'.url('/apartments/'.$apt->id.'/profile').'" class="text-x2-primary hover:underline">Hồ sơ →</a>',
            ];
        })->all();

        return view('livewire.apartment-directory', [
            'kpis' => $kpis,
            'rows' => $rows,
            'total' => $total,
            'shown' => count($rows),
        ]);
    }
}
