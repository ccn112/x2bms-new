<?php

namespace App\Livewire;

use App\Models\Resident;
use App\Models\ResidentApartmentRelation;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * WEB-02-01 — Danh sách cư dân. Resident list with KPIs.
 * Custom page (Livewire + Blade + X2 components). Data from seeded DB.
 */
#[Layout('components.layouts.x2-app')]
class ResidentDirectory extends Component
{
    public string $search = '';

    public function render()
    {
        $roleMeta = [
            'owner' => ['Chủ sở hữu', 'blue'],
            'tenant' => ['Người thuê', 'teal'],
            'member' => ['Thành viên', 'slate'],
        ];

        $kpis = [
            ['label' => 'Tổng cư dân', 'value' => Resident::count(), 'accent' => 'blue', 'sub' => 'Đang cư trú'],
            ['label' => 'Chủ sở hữu', 'value' => ResidentApartmentRelation::where('role', 'owner')->count(), 'accent' => 'teal'],
            ['label' => 'Người thuê', 'value' => ResidentApartmentRelation::where('role', 'tenant')->count(), 'accent' => 'amber'],
            ['label' => 'Chờ duyệt', 'value' => \App\Models\ResidentApprovalRequest::where('status', 'pending')->count(), 'accent' => 'red', 'sub' => 'Hồ sơ mới'],
        ];

        $query = ResidentApartmentRelation::query()
            ->where('is_primary', true)
            ->with(['resident', 'apartment.floor'])
            ->when($this->search !== '', fn ($q) => $q->whereHas('resident',
                fn ($r) => $r->where('full_name', 'like', "%{$this->search}%")->orWhere('phone', 'like', "%{$this->search}%")));

        $total = (clone $query)->count();

        $rows = $query->take(15)->get()->map(function (ResidentApartmentRelation $rel) use ($roleMeta) {
            $r = $rel->resident;
            [$roleLabel, $roleTone] = $roleMeta[$rel->role] ?? ['—', 'slate'];
            $initials = Str::of($r->full_name)->explode(' ')->map(fn ($w) => mb_substr($w, 0, 1))->take(-2)->implode('');
            [$statusLabel, $statusTone] = $r->status === 'active' ? ['Đã xác thực', 'green'] : ['Chờ xác thực', 'amber'];

            return [
                'name' => '<div class="flex items-center gap-2"><span class="grid h-8 w-8 place-items-center rounded-full bg-x2-navy text-[11px] font-semibold text-white">'.e($initials).'</span><div><div class="font-medium text-slate-800">'.e($r->full_name).'</div><div class="text-xs text-slate-400">'.e($r->code).'</div></div></div>',
                'phone' => e($r->phone),
                'email' => '<span class="text-slate-500">'.e($r->email).'</span>',
                'role' => view('components.x2.status-badge', ['label' => $roleLabel, 'tone' => $roleTone])->render(),
                'status' => view('components.x2.status-badge', ['label' => $statusLabel, 'tone' => $statusTone])->render(),
                'location' => '<span class="text-slate-600">'.e($rel->apartment->code).'</span> <span class="text-xs text-slate-400">· '.e($rel->apartment->floor?->name ?? '—').'</span>',
                'action' => '<a href="'.url('/apartments/'.$rel->apartment_id.'/profile').'" class="text-x2-primary hover:underline">Hồ sơ căn</a>',
            ];
        })->all();

        return view('livewire.resident-directory', [
            'kpis' => $kpis,
            'rows' => $rows,
            'total' => $total,
            'shown' => count($rows),
        ]);
    }
}
