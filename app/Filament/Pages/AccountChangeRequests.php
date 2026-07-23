<?php

namespace App\Filament\Pages;

use App\Models\AuditLog;
use App\Models\DataFixRequest;
use App\Models\Resident;
use App\Support\Context\CurrentContext;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Builder;
use Livewire\WithPagination;

/**
 * BQL-02-07 — Yêu cầu đổi thông tin (Account/Resident Change Requests).
 * Tái dùng bảng data_fix_requests (entity='residents'): before/after + duyệt/từ chối/áp dụng.
 * "Áp dụng" ghi giá trị mới vào resident (whitelist field), chụp before_snapshot để đối chiếu.
 */
class AccountChangeRequests extends Page
{
    use WithPagination;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-pencil-square';

    protected static string|\UnitEnum|null $navigationGroup = 'Cư dân & Căn hộ';

    protected static ?string $navigationLabel = 'Yêu cầu đổi thông tin';

    protected static ?int $navigationSort = 16;

    protected static ?string $title = 'Yêu cầu đổi thông tin cư dân';

    protected static ?string $slug = 'account-change-requests';

    protected string $view = 'filament.pages.account-change-requests';

    public string $statusFilter = 'pending';

    /** Field cư dân được phép sửa qua yêu cầu (whitelist an toàn). */
    private const EDITABLE = [
        'full_name' => 'Họ và tên', 'phone' => 'Số điện thoại', 'email' => 'Email',
        'dob' => 'Ngày sinh', 'id_no' => 'Số CCCD', 'contact_phone' => 'SĐT liên hệ', 'contact_email' => 'Email liên hệ',
    ];

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    /** Yêu cầu đổi thông tin cư dân trong phạm vi (entity='residents', target là cư dân của tòa). */
    private function scoped(): Builder
    {
        $residentIds = Resident::query()->whereIn('building_id', app(CurrentContext::class)->buildingIds() ?: [0])->pluck('id');

        return DataFixRequest::query()->where('entity', 'residents')->whereIn('target_id', $residentIds);
    }

    public function approve(int $id): void
    {
        $r = $this->find($id);
        if (! $r || $r->status !== 'pending') {
            return;
        }
        $r->update(['status' => 'approved', 'approved_by_id' => auth()->id()]);
        $this->audit('change_request.approve', 'Duyệt yêu cầu đổi thông tin #'.$r->id, $r->id);
        Notification::make()->title('Đã duyệt — bấm Áp dụng để ghi thay đổi')->success()->send();
    }

    public function reject(int $id): void
    {
        $r = $this->find($id);
        if (! $r || ! in_array($r->status, ['pending', 'approved'], true)) {
            return;
        }
        $r->update(['status' => 'rejected', 'approved_by_id' => auth()->id()]);
        $this->audit('change_request.reject', 'Từ chối yêu cầu đổi thông tin #'.$r->id, $r->id);
        Notification::make()->title('Đã từ chối')->warning()->send();
    }

    public function apply(int $id): void
    {
        $r = $this->find($id);
        if (! $r || $r->status !== 'approved') {
            Notification::make()->title('Chỉ áp dụng yêu cầu đã duyệt')->danger()->send();

            return;
        }
        $resident = Resident::find($r->target_id);
        if (! $resident) {
            Notification::make()->title('Không tìm thấy cư dân đích')->danger()->send();

            return;
        }

        $changes = collect($r->requested_change ?? [])->only(array_keys(self::EDITABLE));
        if ($changes->isEmpty()) {
            Notification::make()->title('Không có trường hợp lệ để áp dụng')->danger()->send();

            return;
        }

        $before = [];
        foreach ($changes as $field => $newVal) {
            $before[$field] = $resident->{$field};
            $resident->{$field} = $newVal;
        }
        $resident->save();

        $r->update(['status' => 'applied', 'before_snapshot' => $before, 'applied_at' => now()]);
        $this->audit('change_request.apply', 'Áp dụng đổi thông tin cư dân '.$resident->full_name.' ('.implode(', ', array_keys($before)).')', $r->id);
        Notification::make()->title('Đã áp dụng thay đổi')->success()->send();
    }

    private function find(int $id): ?DataFixRequest
    {
        return $this->scoped()->whereKey($id)->first();
    }

    private function audit(string $action, string $description, ?int $subjectId = null): void
    {
        $user = auth()->user();
        AuditLog::create([
            'tenant_id' => $user->tenant_id, 'building_id' => $user->building_id,
            'user_id' => $user->id, 'actor_name' => $user->name,
            'action' => $action, 'subject_type' => DataFixRequest::class, 'subject_id' => $subjectId,
            'description' => $description,
        ]);
    }

    protected function getViewData(): array
    {
        $base = $this->scoped();
        $count = fn (string $s) => (clone $base)->where('status', $s)->count();

        $residentNames = Resident::query()
            ->whereIn('id', (clone $base)->pluck('target_id'))
            ->pluck('full_name', 'id');

        $page = (clone $base)
            ->when($this->statusFilter !== 'all', fn (Builder $q) => $q->where('status', $this->statusFilter))
            ->latest()->paginate(20);

        $rows = $page->getCollection()->map(function (DataFixRequest $r) use ($residentNames) {
            $resident = Resident::find($r->target_id);
            $diffs = collect($r->requested_change ?? [])->only(array_keys(self::EDITABLE))
                ->map(fn ($newVal, $field) => [
                    'label' => self::EDITABLE[$field] ?? $field,
                    'old' => $r->before_snapshot[$field] ?? ($resident?->{$field}),
                    'new' => $newVal,
                ])->values()->all();

            return [
                'id' => $r->id,
                'resident' => $residentNames[$r->target_id] ?? ('#'.$r->target_id),
                'reason' => $r->reason,
                'status' => $r->status,
                'diffs' => $diffs,
                'applied_at' => $r->applied_at,
            ];
        })->all();

        return [
            'kpis' => [
                ['label' => 'Chờ duyệt', 'value' => $count('pending'), 'accent' => 'amber'],
                ['label' => 'Đã duyệt', 'value' => $count('approved'), 'accent' => 'blue'],
                ['label' => 'Đã áp dụng', 'value' => $count('applied'), 'accent' => 'green'],
                ['label' => 'Từ chối', 'value' => $count('rejected'), 'accent' => 'red'],
            ],
            'rows' => $rows,
            'page' => $page,
        ];
    }
}
