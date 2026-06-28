<?php

namespace App\Livewire;

use App\Models\AuditLog;
use App\Models\Resident;
use App\Models\ResidentApartmentRelation;
use App\Models\ResidentApprovalRequest;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * WEB-02-04 — Duyệt cư dân. Approval queue with working decisions.
 * Approve creates a Resident + apartment relation and writes an audit log.
 */
#[Layout('components.layouts.x2-app')]
class ResidentApprovalQueue extends Component
{
    public function approve(int $id): void
    {
        $req = ResidentApprovalRequest::findOrFail($id);
        $user = auth()->user();

        $resident = Resident::create([
            'building_id' => $user->building_id,
            'code' => 'CD-'.str_pad((string) (Resident::max('id') + 1), 4, '0', STR_PAD_LEFT),
            'full_name' => $req->full_name,
            'phone' => $req->phone,
            'email' => $req->email,
            'status' => 'active',
        ]);

        if ($req->apartment_id) {
            ResidentApartmentRelation::create([
                'resident_id' => $resident->id,
                'apartment_id' => $req->apartment_id,
                'role' => $req->requested_role,
                'is_primary' => $req->requested_role === 'owner',
                'start_date' => now(),
            ]);
        }

        $req->update(['status' => 'approved']);
        $this->audit('resident.approve', "Duyệt hồ sơ cư dân: {$req->full_name}");
    }

    public function reject(int $id): void
    {
        ResidentApprovalRequest::whereKey($id)->update(['status' => 'rejected']);
        $this->audit('resident.reject', 'Từ chối hồ sơ cư dân #'.$id);
    }

    public function needMore(int $id): void
    {
        ResidentApprovalRequest::whereKey($id)->update(['status' => 'need_more']);
        $this->audit('resident.need_more', 'Yêu cầu bổ sung hồ sơ #'.$id);
    }

    private function audit(string $action, string $description): void
    {
        $user = auth()->user();
        AuditLog::create([
            'tenant_id' => $user->tenant_id,
            'building_id' => $user->building_id,
            'user_id' => $user->id,
            'actor_name' => $user->name,
            'action' => $action,
            'description' => $description,
        ]);
    }

    public function render()
    {
        $pending = ResidentApprovalRequest::where('status', 'pending');

        $kpis = [
            ['label' => 'Chờ duyệt', 'value' => (clone $pending)->count(), 'accent' => 'amber'],
            ['label' => 'Độ khớp TB', 'value' => (int) round((clone $pending)->avg('match_score') ?? 0).'%', 'accent' => 'blue'],
            ['label' => 'Đã duyệt', 'value' => ResidentApprovalRequest::where('status', 'approved')->count(), 'accent' => 'green'],
            ['label' => 'Cần bổ sung', 'value' => ResidentApprovalRequest::where('status', 'need_more')->count(), 'accent' => 'red'],
        ];

        $requests = (clone $pending)->with('apartment')->orderByDesc('match_score')->get();

        return view('livewire.resident-approval-queue', [
            'kpis' => $kpis,
            'requests' => $requests,
        ]);
    }
}
