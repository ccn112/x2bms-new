<?php

namespace App\Filament\Pages;

use App\Models\AccessCard;
use App\Models\AuditLog;
use App\Models\Debt;
use App\Models\FeedbackRequest;
use App\Models\Resident;
use App\Models\ResidentApartmentRelation;
use App\Models\Statement;
use App\Models\Vehicle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

/**
 * RES-DETAIL-01 — Full resident detail page (header summary, overview cards, tabs).
 * /admin/residents/{resident}/detail. Shares its data source with the quick drawer.
 */
class ResidentDetail extends Page
{
    protected static ?string $slug = 'residents/{resident}/detail';

    protected static bool $shouldRegisterNavigation = false;

    protected string $view = 'filament.pages.resident-detail';

    public Resident $resident;

    public function mount(Resident $resident): void
    {
        $this->resident = $resident->load(['apartmentRelations.apartment.floor', 'building', 'emergencyContacts']);
    }

    public function getTitle(): string
    {
        return 'Cư dân · '.$this->resident->full_name;
    }

    public function lock(): void
    {
        $this->resident->update(['status' => 'inactive']);
        $this->audit('resident.lock', "Khóa tài khoản {$this->resident->full_name}");
        Notification::make()->title('Đã khóa tài khoản cư dân')->warning()->send();
        $this->resident->refresh();
    }

    public function unlock(): void
    {
        $this->resident->update(['status' => 'active']);
        $this->audit('resident.unlock', "Mở khóa tài khoản {$this->resident->full_name}");
        Notification::make()->title('Đã mở khóa tài khoản')->success()->send();
        $this->resident->refresh();
    }

    protected function getViewData(): array
    {
        $r = $this->resident;
        $apartmentIds = $r->apartmentRelations->pluck('apartment_id')->all();
        $primary = $r->primaryRelation();

        $roles = ['owner' => 'Chủ căn hộ', 'tenant' => 'Người thuê', 'member' => 'Thành viên'];
        $statuses = ['active' => ['Hoạt động', 'green'], 'pending' => ['Chờ duyệt', 'amber'], 'inactive' => ['Đã khóa', 'red']];

        $ids = $apartmentIds ?: [0];
        $vehicles = Vehicle::where('resident_id', $r->id)->get();
        $cards = AccessCard::where('resident_id', $r->id)->get();
        $overdueDebt = Debt::whereIn('apartment_id', $ids)->where('is_overdue', true)->sum('amount');
        $billedTotal = Statement::whereIn('apartment_id', $ids)->sum('total_amount');
        $feedbackCount = FeedbackRequest::whereIn('apartment_id', $ids)->count();
        $invoiceCount = Statement::whereIn('apartment_id', $ids)->count();

        $members = ResidentApartmentRelation::whereIn('apartment_id', $apartmentIds ?: [0])
            ->where('resident_id', '!=', $r->id)
            ->with('resident')
            ->get()
            ->pluck('resident')
            ->filter()
            ->unique('id')
            ->values();

        $audits = AuditLog::where('building_id', $r->building_id)->latest()->take(8)->get();

        return [
            'r' => $r,
            'primary' => $primary,
            'apartment' => $primary?->apartment,
            'roleLabel' => $roles[$primary?->role] ?? '—',
            'status' => $statuses[$r->status] ?? [$r->status, 'slate'],
            'apartments' => $r->apartmentRelations,
            'vehicles' => $vehicles,
            'cards' => $cards,
            'members' => $members,
            'emergencyContacts' => $r->emergencyContacts,
            'audits' => $audits,
            'overview' => [
                'members' => $members->count() + 1,
                'vehicles' => $vehicles->count(),
                'cards' => $cards->count(),
                'feedback' => $feedbackCount,
                'invoices' => $invoiceCount,
                'overdueDebt' => $overdueDebt,
                'billed' => $billedTotal,
            ],
            'roles' => $roles,
            'statuses' => $statuses,
        ];
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
}
