<?php

namespace App\Filament\Pages;

use App\Models\AuditLog;
use App\Models\Resident;
use App\Models\ResidentApprovalRequest;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

/**
 * BQL-02-02 — Chi tiết duyệt tài khoản cư dân (Account Approval Detail).
 * Reconciliation of a resident's declared info vs the system's matched record, plus a
 * decision panel (Phê duyệt / Yêu cầu bổ sung / Từ chối). Transitions the request +
 * writes audit. Reached from the approval queue. UI follows BQL-02-02.
 */
class AccountApprovalDetail extends Page
{
    protected static ?string $slug = 'residents/approvals/{record}';

    protected static bool $shouldRegisterNavigation = false;

    protected string $view = 'filament.pages.account-approval-detail';

    public ResidentApprovalRequest $record;

    public string $note = '';

    public function mount(ResidentApprovalRequest $record): void
    {
        $this->record = $record;
    }

    public function getTitle(): string
    {
        return 'Duyệt tài khoản · '.$this->record->full_name;
    }

    protected function getViewData(): array
    {
        $r = $this->record;

        // System-side match: an existing resident with the same phone/email in scope.
        $match = Resident::query()
            ->where(fn ($q) => $q->where('phone', $r->phone)->when($r->email, fn ($w) => $w->orWhere('email', $r->email)))
            ->first();

        $rows = [
            ['Họ và tên', $r->full_name, $match?->full_name],
            ['Số điện thoại', $r->phone, $match?->phone],
            ['Email', $r->email, $match?->email],
            ['Vai trò đề nghị', $this->roleLabel($this->enumVal($r->requested_role)), $match ? $this->roleLabel($this->enumVal($match->requested_role)) : null],
            ['Căn hộ', optional($r->apartment)->code, optional($match?->apartmentRelations?->first()?->apartment)->code],
        ];

        return [
            'r' => $r,
            'match' => $match,
            'rows' => collect($rows)->map(fn ($row) => [
                'label' => $row[0],
                'declared' => $row[1] ?: '—',
                'system' => $row[2] ?: '—',
                'ok' => $row[1] && $row[2] && mb_strtolower(trim((string) $row[1])) === mb_strtolower(trim((string) $row[2])),
                'hasSystem' => ! is_null($row[2]) && $row[2] !== '',
            ])->all(),
            'statusMeta' => $this->statusMeta($this->enumVal($r->status)),
        ];
    }

    /** Normalise a BackedEnum (Filament cast) to its scalar value. */
    private function enumVal(mixed $x): ?string
    {
        return $x instanceof \BackedEnum ? (string) $x->value : ($x === null ? null : (string) $x);
    }

    public function decide(string $decision): void
    {
        if (in_array($decision, ['reject', 'need_more'], true) && trim($this->note) === '') {
            Notification::make()->title('Vui lòng nhập lý do / nội dung cần bổ sung')->danger()->send();

            return;
        }

        $map = ['approve' => 'approved', 'need_more' => 'need_more', 'reject' => 'rejected'];
        $verb = ['approve' => 'Phê duyệt', 'need_more' => 'Yêu cầu bổ sung', 'reject' => 'Từ chối'][$decision];

        $this->record->update(['status' => $map[$decision], 'note' => $this->note ?: $this->record->note]);

        $user = auth()->user();
        AuditLog::create([
            'tenant_id' => $user->tenant_id, 'building_id' => $user->building_id,
            'user_id' => $user->id, 'actor_name' => $user->name,
            'action' => 'account.'.$decision, 'subject_type' => ResidentApprovalRequest::class, 'subject_id' => $this->record->id,
            'description' => $verb.' hồ sơ cư dân '.$this->record->full_name.($this->note ? ': '.$this->note : ''),
        ]);

        Notification::make()->title($verb.' thành công')->{$decision === 'approve' ? 'success' : 'warning'}()->send();
        $this->record->refresh();
    }

    private function roleLabel(?string $role): ?string
    {
        return $role ? (['owner' => 'Chủ sở hữu', 'tenant' => 'Người thuê', 'member' => 'Thành viên'][$role] ?? $role) : null;
    }

    /** @return array{0:string,1:string} */
    private function statusMeta(?string $status): array
    {
        return [
            'pending' => ['Chờ duyệt', 'amber'], 'reviewing' => ['Đang rà soát', 'blue'],
            'approved' => ['Đã duyệt', 'green'], 'rejected' => ['Từ chối', 'red'], 'need_more' => ['Cần bổ sung', 'amber'],
        ][$status] ?? [$status ?? '—', 'slate'];
    }
}
